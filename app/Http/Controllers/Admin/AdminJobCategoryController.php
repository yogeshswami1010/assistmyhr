<?php

namespace App\Http\Controllers\Admin;

use App\AiApiKey;
use App\Helper\Reply;
use App\Http\Requests\StoreJobCategory;
use App\Job;
use App\JobApplication;
use App\JobCategory;
use App\Question;
use App\Skill;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\Admin\JobCategory\GenerateJobCategoriesRequest;
use Illuminate\Support\Str;

class AdminJobCategoryController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.jobCategories');
        $this->pageIcon = 'icon-grid';
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        abort_if(! $this->user->cans('view_category'), 403);

        $this->categories = JobCategory::withCount('jobs')->orderBy('name')->get();
        $this->jcStatActiveJobs = Job::activeJobsCount();
        $this->jcStatApplications = JobApplication::count();

        return view('admin.job-category.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        abort_if(! $this->user->cans('add_category'), 403);

        return redirect()->route('admin.job-categories.index', ['open' => 'create']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        abort_if(! $this->user->cans('add_category'), 403);

        $names = $request->name;

        if (trim($names[0]) == '') {
            return Reply::error(__('errors.addCategory'));
        }

        foreach ($names as $name) {
            if (is_null($name)) {
                return Reply::error(__('errors.addCategory'));
            }
        }

        foreach ($names as $key => $name) {
            if (! is_null($name)) {
                JobCategory::create(['name' => $name]);
            }
        }

        return Reply::redirect(route('admin.job-categories.index'), __('menu.jobCategories').' '.__('messages.createdSuccessfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        abort_if(! $this->user->cans('edit_category'), 403);

        JobCategory::findOrFail($id);

        return redirect()->route('admin.job-categories.index', ['open' => 'edit', 'id' => $id]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(StoreJobCategory $request, $id)
    {
        abort_if(! $this->user->cans('edit_category'), 403);

        $category = JobCategory::find($id);
        $category->name = $request->name;
        $category->save();

        return Reply::redirect(route('admin.job-categories.index'), __('menu.jobCategories').' '.__('messages.updatedSuccessfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        abort_if(! $this->user->cans('delete_category'), 403);

        JobCategory::destroy($id);

        return Reply::success(__('messages.recordDeleted'));
    }

    public function aiGenerateCategories(GenerateJobCategoriesRequest $request)
    {
        abort_if(! ($this->user?->cans('add_category') || $this->user?->cans('edit_category')), 403);
        $validated = $request->validated();

        $existingCategories = JobCategory::query()
            ->pluck('name')
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->values()
            ->all();

        $existingLower = collect($existingCategories)
            ->map(fn ($n) => mb_strtolower((string) $n))
            ->flip()
            ->all();

        $deepseekKey = trim((string) config('services.deepseek.key'));
        $configuredKeys = $deepseekKey !== '' ? collect() : AiApiKey::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $apiCandidates = $deepseekKey !== '' ? [[
            'provider' => 'deepseek',
            'api_key' => $deepseekKey,
        ]] : [];
        foreach ($configuredKeys as $row) {
            $plainKey = $this->resolvePlainApiKey((string) $row->api_key);
            if ($plainKey === '') {
                continue;
            }

            $apiCandidates[] = [
                'provider' => $this->normalizeProvider((string) $row->provider),
                'api_key' => $plainKey,
            ];
        }

        if (empty($apiCandidates)) {
            return Reply::error(__('messages.aiApiKeyRequired'));
        }

        $companyName = (string) data_get($this->global, 'company_name', '');
        $website = (string) data_get($this->global, 'website', '');
        $address = (string) data_get($this->global, 'address', '');
        $companyDescription = trim((string) ($validated['company_description'] ?? ''));
        if ($companyDescription === '') {
            return Reply::error(__('messages.companyDescriptionRequired'));
        }

        $companyProfileText = 'Company name: '.$companyName."\n"
            .'Website: '.($website !== '' ? $website : '—')."\n"
            .'Address: '.($address !== '' ? $address : '—')."\n"
            .'Company description: '.$companyDescription;

        $systemPrompt = 'You are a senior recruitment taxonomy designer for job boards. '
            .'Return ONLY valid minified JSON with key "categories": an array of unique job category names. '
            .'Rules: '
            .'(1) Each category should be 1 to 3 words. '
            .'(2) Categories must match the company industry. Infer whether the company is IT/software, service-based, consulting, retail, healthcare, education, etc from the provided company description. '
            .'(3) Avoid categories that already exist in the provided existing_categories list. '
            .'(4) No numbering, no duplicates, no extra keys. '
            .'(5) Generate exactly the requested number of categories.';

        $userPrompt = 'Requested category count: '.(int) $validated['count']."\n\n"
            .'Company profile:\n'.$companyProfileText."\n\n"
            .'Existing categories:\n'.json_encode(array_values($existingCategories), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $totalCreated = 0;
        $totalSkipped = 0;
        $categoryErrors = [];

        $generatedCategories = [];
        $lastError = 'AI generation failed.';

        foreach ($apiCandidates as $candidate) {
            try {
                $content = $this->generateChatByProvider(
                    (string) $candidate['provider'],
                    (string) $candidate['api_key'],
                    $systemPrompt,
                    [['role' => 'user', 'content' => $userPrompt]]
                );

                $generatedCategories = $this->extractCategoriesFromAiContent($content);
                if (empty($generatedCategories)) {
                    throw new \RuntimeException('Empty categories array returned from AI.');
                }

                $lastError = '';
                break;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        if (empty($generatedCategories)) {
            return Reply::error(__('messages.aiCategoriesGenerationFailed').' '.($lastError ? '('.$lastError.')' : ''));
        }

        $seenLower = [];

        foreach ($generatedCategories as $name) {
            $catName = trim((string) $name);
            if ($catName === '') {
                continue;
            }

            $catName = preg_replace('/\\s+/', ' ', $catName);
            if (mb_strlen($catName) > 60) {
                $catName = mb_substr($catName, 0, 60);
            }

            // Preserve word-casing style (title-ish) while still doing case-insensitive de-dupe.
            $catNameForDisplay = ucwords(mb_strtolower($catName));
            $key = mb_strtolower($catNameForDisplay);

            if (isset($seenLower[$key])) {
                $totalSkipped++;

                continue;
            }
            $seenLower[$key] = true;

            if (isset($existingLower[$key])) {
                $totalSkipped++;

                continue;
            }

            JobCategory::create(['name' => $catNameForDisplay]);
            $existingLower[$key] = true;
            $totalCreated++;

            if ($totalCreated >= (int) $validated['count']) {
                break;
            }
        }

        if ($totalCreated === 0) {
            $categoryErrors[] = ['error' => 'AI returned categories but none were new'];
        }

        return Reply::successWithData(__('messages.aiJobCategoriesGeneratedSuccessfully'), [
            'created' => $totalCreated,
            'skipped' => $totalSkipped,
            'errors' => $categoryErrors,
        ]);
    }

    public function getSkills($categoryId)
    {
        $jobSkills = '';

        $skills = Skill::where('category_id', $categoryId)->get();

        foreach ($skills as $skill) {
            $jobSkills .= '<option selected value="'.$skill->id.'">'.ucwords($skill->name).'</option>';
        }
        $selectedQuestions = request()->input('selected_questions', []);
        if (! is_array($selectedQuestions)) {
            $selectedQuestions = [];
        }
        $selectedQuestions = collect($selectedQuestions)->map(fn ($id) => (int) $id)->all();

        $questions = Question::query()
            ->where(function ($query) use ($categoryId) {
                $query->whereNull('job_category_id')
                    ->orWhere('job_category_id', (int) $categoryId);
            })
            ->orderBy('id', 'desc')
            ->get();

        $questionsHtml = view('admin.jobs.partials.question-options', [
            'questions' => $questions,
            'jobQuestion' => $selectedQuestions,
        ])->render();

        return Reply::dataOnly([
            'status' => 'success',
            'data' => $jobSkills,
            'questions_html' => $questionsHtml,
        ]);
    }

    private function extractCategoriesFromAiContent(string $content): array
    {
        $content = trim($content);
        if ($content === '') {
            return [];
        }

        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        $jsonCandidate = $content;

        if ($start !== false && $end !== false && $end > $start) {
            $jsonCandidate = substr($content, $start, $end - $start + 1);
        }

        $data = json_decode($jsonCandidate, true);
        if (! is_array($data)) {
            $startArr = strpos($content, '[');
            $endArr = strrpos($content, ']');
            if ($startArr !== false && $endArr !== false && $endArr > $startArr) {
                $arrCandidate = substr($content, $startArr, $endArr - $startArr + 1);
                $arrData = json_decode($arrCandidate, true);

                return is_array($arrData) ? $arrData : [];
            }

            return [];
        }

        if (isset($data['categories']) && is_array($data['categories'])) {
            return $data['categories'];
        }

        // If the response is a JSON list without the wrapper key.
        if (array_values($data) === $data) {
            return $data;
        }

        return [];
    }

    private function resolvePlainApiKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }

        if (Str::startsWith($key, 'eyJpdiI6')) {
            try {
                $decrypted = Crypt::decryptString($key);
                if (is_string($decrypted) && trim($decrypted) !== '') {
                    return trim($decrypted);
                }
            } catch (\Throwable $e) {
                // Ignore and fall back to raw key.
            }
        }

        return $key;
    }

    private function extractOpenAiLikeContent(array $payload): string
    {
        $rawContent = data_get($payload, 'choices.0.message.content');

        if (is_string($rawContent) && trim($rawContent) !== '') {
            return trim($rawContent);
        }

        // Some models may return structured content parts.
        if (is_array($rawContent)) {
            $combined = collect($rawContent)
                ->map(function ($part) {
                    if (! is_array($part)) {
                        return '';
                    }

                    return (string) ($part['text'] ?? $part['content'] ?? '');
                })
                ->filter(fn ($text) => trim($text) !== '')
                ->implode("\n");

            if (trim($combined) !== '') {
                return trim($combined);
            }
        }

        // Fallbacks for other OpenAI-compatible response formats.
        $fallbacks = [
            data_get($payload, 'choices.0.text'),
            data_get($payload, 'output_text'),
            data_get($payload, 'output.0.content.0.text'),
        ];

        foreach ($fallbacks as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    private function normalizeProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));
        if (Str::contains($provider, 'deepseek')) {
            return 'deepseek';
        }
        if ($provider === '' || Str::contains($provider, 'openai') || Str::contains($provider, 'gpt')) {
            return 'openai';
        }
        if (Str::contains($provider, 'groq') || Str::contains($provider, 'llama')) {
            return 'groq';
        }
        if (Str::contains($provider, 'anthropic') || Str::contains($provider, 'claude')) {
            return 'anthropic';
        }
        if (Str::contains($provider, 'gemini') || Str::contains($provider, 'google')) {
            return 'gemini';
        }

        return 'openai';
    }

    private function generateChatByProvider(string $provider, string $apiKey, string $systemPrompt, array $conversation): string
    {
        $provider = $this->normalizeProvider($provider);

        if (in_array($provider, ['openai', 'groq', 'deepseek'], true)) {
            $url = match ($provider) {
                'deepseek' => 'https://api.deepseek.com/chat/completions',
                'groq' => 'https://api.groq.com/openai/v1/chat/completions',
                default => 'https://api.openai.com/v1/chat/completions',
            };

            $model = match ($provider) {
                'deepseek' => config('services.deepseek.model', 'deepseek-chat'),
                'groq' => env('AI_JOB_GENERATOR_GROQ_MODEL', 'llama-3.1-8b-instant'),
                default => env('AI_JOB_GENERATOR_MODEL', 'gpt-5-nano'),
            };

            $messages = array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                array_map(function (array $m) {
                    return [
                        'role' => $m['role'],
                        'content' => (string) $m['content'],
                    ];
                }, $conversation)
            );

            $payload = [
                'model' => $model,
                'messages' => $messages,
            ];

            if ($provider === 'openai') {
                $payload['max_completion_tokens'] = 1200;
            } else {
                $payload['temperature'] = 0.35;
                $payload['max_tokens'] = 800;
            }

            $response = Http::timeout(45)->withToken($apiKey)->post($url, $payload);

            if (! $response->successful()) {
                $message = (string) data_get($response->json(), 'error.message', '');
                throw new \RuntimeException('AI request failed'.($message ? ': '.$message : '.'));
            }

            $responsePayload = (array) $response->json();
            $this->recordAiUsage($provider, $model, $responsePayload);
            $content = $this->extractOpenAiLikeContent($responsePayload);
            $content = trim($content);

            // GPT-5 family can occasionally return an empty visible answer on first try.
            // Retry once with a higher completion budget before failing.
            if ($content === '' && $provider === 'openai') {
                $retryPayload = $payload;
                $retryPayload['max_completion_tokens'] = max((int) ($retryPayload['max_completion_tokens'] ?? 1200), 2200);

                $retryResponse = Http::timeout(45)->withToken($apiKey)->post($url, $retryPayload);
                if ($retryResponse->successful()) {
                    $retryPayloadData = (array) $retryResponse->json();
                    $this->recordAiUsage($provider, $model, $retryPayloadData);
                    $content = trim($this->extractOpenAiLikeContent($retryPayloadData));
                }
            }

            if ($content === '') {
                throw new \RuntimeException('Empty AI response.');
            }

            return $content;
        }

        if ($provider === 'anthropic') {
            $model = env('AI_JOB_GENERATOR_ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022');

            $messages = array_map(function (array $m) {
                return [
                    'role' => $m['role'],
                    'content' => (string) $m['content'],
                ];
            }, $conversation);

            $response = Http::timeout(45)->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 800,
                'temperature' => 0.35,
                'system' => $systemPrompt,
                'messages' => $messages,
            ]);

            if (! $response->successful()) {
                $message = (string) data_get($response->json(), 'error.message', '');
                throw new \RuntimeException('AI request failed'.($message ? ': '.$message : '.'));
            }

            $responsePayload = (array) $response->json();
            $this->recordAiUsage($provider, $model, $responsePayload);
            $content = collect((array) data_get($responsePayload, 'content', []))
                ->map(function ($part) {
                    if (! is_array($part) || ($part['type'] ?? '') !== 'text') {
                        return '';
                    }

                    return (string) ($part['text'] ?? '');
                })
                ->implode("\n")
                ->trim();

            if ($content === '') {
                throw new \RuntimeException('Empty AI response.');
            }

            return $content;
        }

        if ($provider === 'gemini') {
            $model = env('AI_JOB_GENERATOR_GEMINI_MODEL', 'gemini-2.0-flash');
            $history = collect($conversation)
                ->map(function (array $m) {
                    return ucfirst((string) $m['role']).': '.trim((string) $m['content']);
                })
                ->implode("\n");

            $prompt = $systemPrompt."\n\n".$history."\nAssistant:";
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent?key='.$apiKey;

            $response = Http::timeout(45)->post($url, [
                'contents' => [[
                    'parts' => [[
                        'text' => $prompt,
                    ]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.35,
                    'maxOutputTokens' => 800,
                ],
            ]);

            if (! $response->successful()) {
                $message = (string) data_get($response->json(), 'error.message', '');
                throw new \RuntimeException('AI request failed'.($message ? ': '.$message : '.'));
            }

            $responsePayload = (array) $response->json();
            $this->recordAiUsage($provider, $model, $responsePayload);
            $content = (string) data_get($responsePayload, 'candidates.0.content.parts.0.text', '');
            $content = trim($content);
            if ($content === '') {
                throw new \RuntimeException('Empty AI response.');
            }

            return $content;
        }

        throw new \RuntimeException('Unsupported AI provider: '.$provider);
    }
}
