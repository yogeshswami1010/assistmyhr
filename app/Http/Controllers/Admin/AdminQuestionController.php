<?php

namespace App\Http\Controllers\Admin;

use App\AiApiKey;
use App\Helper\Reply;
use App\Http\Requests\Question\StoreRequest;
use App\Http\Requests\Question\UpdateRequest;
use App\JobCategory;
use App\Question;
use App\Support\RaDataTableHtml;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class AdminQuestionController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.question');
        $this->pageIcon = 'icon-grid';
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        abort_if(! $this->user->cans('view_question'), 403);
        $this->categories = JobCategory::query()->select('id', 'name')->orderBy('name')->get();

        return view('admin.question.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        abort_if(! $this->user->cans('add_question'), 403);
        $this->categories = JobCategory::query()->select('id', 'name')->orderBy('name')->get();

        return view('admin.question.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
public function store(StoreRequest $request)
{
    abort_if(! $this->user->cans('add_question'), 403);

    $question = new Question;
    $question->question        = $request->question;
    $question->required        = $request->required;
    $question->type            = $request->type;

    $question->answer_type = $request->type == 'radio'
        ? implode(',', array_filter(array_map('trim', $request->radio_options ?? [])))
        : null;

    // ✅ Knockout fields
    $question->is_knockout     = $request->type == 'radio' && $request->boolean('is_knockout');
    $question->knockout_answer = $question->is_knockout ? $request->input('knockout_answer') : null;

    $question->job_category_id = $request->job_category_id ?: null;
    $question->save();

    if ($request->boolean('inline_job')) {
        return response()->json(['question' => $question->only(['id', 'question', 'type', 'required', 'is_knockout'])]);
    }

    return Reply::redirect(
        route('admin.questions.index'),
        __('menu.question').' '.__('messages.createdSuccessfully')
    );
}
 /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
public function update(UpdateRequest $request, $id)
{
    abort_if(! $this->user->cans('edit_question'), 403);

    $question = Question::find($id);
    $question->question        = $request->question;
    $question->required        = $request->required;
    $question->type            = $request->type;

    $question->answer_type = $request->type == 'radio'
        ? implode(',', array_filter(array_map('trim', $request->radio_options ?? [])))
        : null;

    // ✅ Knockout fields
    $question->is_knockout     = $request->type == 'radio' && $request->boolean('is_knockout');
    $question->knockout_answer = $question->is_knockout ? $request->input('knockout_answer') : null;

    $question->job_category_id = $request->job_category_id ?: null;
    $question->save();

    return Reply::redirect(
        route('admin.questions.index'),
        __('menu.question').' '.__('messages.updatedSuccessfully')
    );
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
        abort_if(! $this->user->cans('edit_question'), 403);

        $this->question = Question::find($id);
        $this->categories = JobCategory::query()->select('id', 'name')->orderBy('name')->get();

        return view('admin.question.edit', $this->data);
    }

   


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        abort_if(! $this->user->cans('delete_question'), 403);

        Question::destroy($id);

        return Reply::success(__('messages.questionDeleted'));
    }

    public function data()
    {
        abort_if(! $this->user->cans('view_question'), 403);

        $questions = Question::with('category')->get();

        return DataTables::of($questions)
            ->addColumn('action', function ($row) {
                $parts = [];

                if ($this->user->cans('edit_question')) {
                    $parts[] = RaDataTableHtml::edit(route('admin.questions.edit', [$row->id]));
                }

                if ($this->user->cans('delete_question')) {
                    $parts[] = RaDataTableHtml::deleteSaParams($row->id);
                }

                return RaDataTableHtml::wrap(implode('', $parts));
            })
            ->editColumn('required', function ($row) {
                return ucfirst($row->required);
            })
            ->editColumn('requ', function ($row) {
                return ucfirst($row->question);
            })
            ->addColumn('category', function ($row) {
                return (string) optional($row->category)->name ?: __('app.all');
            })
            ->addIndexColumn()
            ->make(true);
    }

    public function aiGenerateQuestions(Request $request)
    {
        abort_if(! ($this->user->cans('add_question') || $this->user->cans('edit_question')), 403);

        $validated = $request->validate([
            'job_title' => 'required|string|max:191',
            'category_id' => 'required|integer|exists:job_categories,id',
            'skills' => 'nullable|string|max:4000',
        ]);

        $category = JobCategory::find((int) $validated['category_id']);
        if (! $category) {
            return Reply::error(__('messages.invalidCategorySelected'));
        }

        $skills = collect(explode(',', (string) ($validated['skills'] ?? '')))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->values()
            ->all();

        $apiCandidates = $this->collectAiApiCandidates();
        if ($apiCandidates === []) {
            return Reply::error(__('messages.aiApiKeyRequired'));
        }

        $existingLower = Question::query()
            ->pluck('question')
            ->map(fn ($q) => Str::lower(trim((string) $q)))
            ->filter()
            ->flip()
            ->all();

        $systemPrompt = 'You are an expert recruitment assistant that writes practical prescreening questions. '
            .'Return strict minified JSON only in this schema: '
            .'{"questions":[{"question":string,"required":"no","type":"text"}]}. '
            .'Rules: '
            .'(1) Generate 8 to 12 concise questions total. '
            .'(2) Include both groups: screening and salary/availability. '
            .'(3) Questions must be one sentence and specific to provided role/category/skills. '
            .'(4) type must always be "text" and required must be "no". '
            .'(5) No markdown, no numbering, no extra keys.';

        $userPrompt = 'Generate custom questions for: '
            .json_encode([
                'job_title' => (string) $validated['job_title'],
                'category' => (string) $category->name,
                'skills' => $skills,
                'must_cover' => [
                    'Screening questions',
                    'Salary & availability questions',
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $generated = [];
        $lastError = 'AI generation failed.';

        foreach ($apiCandidates as $candidate) {
            try {
                $content = $this->generateChatByProvider(
                    (string) $candidate['provider'],
                    (string) $candidate['api_key'],
                    $systemPrompt,
                    [['role' => 'user', 'content' => $userPrompt]]
                );
                $generated = $this->extractQuestionsFromAiContent($content);
                if ($generated === []) {
                    throw new \RuntimeException('Empty questions from AI.');
                }
                $lastError = '';
                break;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        if ($generated === []) {
            return Reply::error(__('messages.aiQuestionsGenerationFailed').' '.($lastError !== '' ? '('.$lastError.')' : ''));
        }

        $created = 0;
        $skipped = 0;
        $createdQuestions = [];

        foreach ($generated as $row) {
            $questionText = is_string($row) ? trim($row) : trim((string) data_get($row, 'question', ''));
            if ($questionText === '') {
                continue;
            }
            $key = Str::lower($questionText);
            if (isset($existingLower[$key])) {
                $skipped++;

                continue;
            }

            $q = new Question;
            $q->question = $questionText;
            $q->required = 'no';
            $q->type = 'text';
            $q->answer_type = 'text';
            $q->job_category_id = (int) $validated['category_id'];
            $q->save();

            $existingLower[$key] = true;
            $created++;
            $createdQuestions[] = $questionText;
        }

        if ($created === 0) {
            return Reply::error(__('messages.aiQuestionsAllDuplicates'));
        }

        return Reply::successWithData(__('messages.aiQuestionsGeneratedSuccessfully'), [
            'created' => $created,
            'skipped' => $skipped,
            'questions' => $createdQuestions,
        ]);
    }

    /**
     * @return array<int, array{provider: string, api_key: string}>
     */
    private function collectAiApiCandidates(): array
    {
        $configuredKeys = AiApiKey::query()->active()->orderBy('sort_order')->orderBy('id')->get();
        $apiCandidates = [];
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

        return $apiCandidates;
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
            }
        }

        return $key;
    }

    private function normalizeProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));
        if ($provider === '' || Str::contains($provider, 'openai') || Str::contains($provider, 'gpt')) {
            return 'openai';
        }
        if (Str::contains($provider, 'gemini') || Str::contains($provider, 'google')) {
            return 'gemini';
        }
        if (Str::contains($provider, 'anthropic') || Str::contains($provider, 'claude')) {
            return 'anthropic';
        }
        if (Str::contains($provider, 'groq') || Str::contains($provider, 'llama')) {
            return 'groq';
        }

        return 'openai';
    }

    private function generateChatByProvider(string $provider, string $apiKey, string $systemPrompt, array $messages): string
    {
        $provider = $this->normalizeProvider($provider);

        if ($provider === 'openai' || $provider === 'groq') {
            $url = $provider === 'groq' ? 'https://api.groq.com/openai/v1/chat/completions' : 'https://api.openai.com/v1/chat/completions';
            $model = $provider === 'groq' ? env('AI_JOB_GENERATOR_GROQ_MODEL', 'llama-3.1-8b-instant') : env('AI_JOB_GENERATOR_MODEL', 'gpt-5-nano');
            $payload = [
                'model' => $model,
                'messages' => array_merge([['role' => 'system', 'content' => $systemPrompt]], $messages),
            ];

            if ($provider === 'openai') {
                $payload['max_completion_tokens'] = 1400;
            } else {
                $payload['temperature'] = 0.3;
                $payload['max_tokens'] = 1200;
            }

            $response = Http::timeout(35)->withToken($apiKey)->post($url, $payload);
            if (! $response->successful()) {
                throw new \RuntimeException((string) data_get($response->json(), 'error.message', 'Provider request failed.'));
            }

            $responsePayload = (array) $response->json();
            $this->recordAiUsage($provider, $model, $responsePayload);
            $content = trim($this->extractOpenAiLikeContent($responsePayload));
            if ($content === '' && $provider === 'openai') {
                $retryPayload = $payload;
                $retryPayload['max_completion_tokens'] = max((int) ($retryPayload['max_completion_tokens'] ?? 1400), 2200);
                $retryResponse = Http::timeout(35)->withToken($apiKey)->post($url, $retryPayload);
                if ($retryResponse->successful()) {
                    $retryPayloadData = (array) $retryResponse->json();
                    $this->recordAiUsage($provider, $model, $retryPayloadData);
                    $content = trim($this->extractOpenAiLikeContent($retryPayloadData));
                }
            }

            return $content;
        }

        if ($provider === 'anthropic') {
            $response = Http::timeout(35)->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => env('AI_JOB_GENERATOR_ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
                'max_tokens' => 1200,
                'temperature' => 0.3,
                'system' => $systemPrompt,
                'messages' => $messages,
            ]);
            if (! $response->successful()) {
                throw new \RuntimeException((string) data_get($response->json(), 'error.message', 'Provider request failed.'));
            }

            $responsePayload = (array) $response->json();
            $this->recordAiUsage($provider, env('AI_JOB_GENERATOR_ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'), $responsePayload);

            return collect((array) data_get($responsePayload, 'content', []))
                ->map(fn ($part) => is_array($part) && ($part['type'] ?? '') === 'text' ? (string) ($part['text'] ?? '') : '')
                ->implode("\n");
        }

        $model = env('AI_JOB_GENERATOR_GEMINI_MODEL', 'gemini-2.0-flash');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent?key='.$apiKey;
        $response = Http::timeout(35)->post($url, [
            'contents' => [[
                'parts' => [[
                    'text' => $systemPrompt."\n\n".collect($messages)->pluck('content')->implode("\n\n"),
                ]],
            ]],
            'generationConfig' => ['temperature' => 0.3],
        ]);
        if (! $response->successful()) {
            throw new \RuntimeException((string) data_get($response->json(), 'error.message', 'Provider request failed.'));
        }

        $responsePayload = (array) $response->json();
        $this->recordAiUsage($provider, $model, $responsePayload);

        return (string) data_get($responsePayload, 'candidates.0.content.parts.0.text', '');
    }

    private function extractOpenAiLikeContent(array $payload): string
    {
        $content = data_get($payload, 'choices.0.message.content');
        if (is_string($content) && trim($content) !== '') {
            return trim($content);
        }

        if (is_array($content)) {
            $combined = collect($content)
                ->map(fn ($part) => is_array($part) ? (string) ($part['text'] ?? $part['content'] ?? '') : (string) $part)
                ->filter(fn ($chunk) => trim($chunk) !== '')
                ->implode("\n");
            if (trim($combined) !== '') {
                return trim($combined);
            }
        }

        foreach ([data_get($payload, 'choices.0.text'), data_get($payload, 'output_text'), data_get($payload, 'output.0.content.0.text')] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    private function extractQuestionsFromAiContent(string $content): array
    {
        $content = trim($content);
        if (Str::startsWith($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/', '', $content) ?? $content;
            $content = preg_replace('/\s*```$/', '', $content) ?? $content;
            $content = trim($content);
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded) && preg_match('/\{.*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
        }
        if (! is_array($decoded)) {
            // Fallback: parse plain text output (one question per line).
            $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
            $plainQuestions = collect($lines)
                ->map(function ($line) {
                    $line = trim((string) $line);
                    $line = preg_replace('/^\d+[\)\.\-:]\s*/', '', $line) ?? $line;
                    $line = preg_replace('/^[\-\*\u2022]\s*/u', '', $line) ?? $line;

                    return trim($line);
                })
                ->filter(fn ($line) => $line !== '' && mb_strlen($line) >= 12)
                ->filter(fn ($line) => Str::contains($line, '?'))
                ->values()
                ->all();

            return $plainQuestions;
        }

        $rows = $decoded['questions'] ?? $decoded['data'] ?? $decoded;
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, fn ($row) => is_array($row) || is_string($row)));
    }
}
