<?php

namespace App\Http\Controllers\Admin;

use App\AiApiKey;
use App\CompanySetting;
use App\Currency;
use Illuminate\Http\Request;
use Froiden\Envato\Helpers\Reply;
use App\Http\Requests\StoreCurrency;
use App\Http\Controllers\Admin\AdminBaseController;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AdminCurrencyController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Currency Setting';
        $this->pageIcon = __('ti-settings');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->currencies = Currency::all();
        $countriesPath = public_path('country-state-city/countries.json');
        $countries = [];

        if (is_file($countriesPath)) {
            $decoded = json_decode((string) file_get_contents($countriesPath), true);
            $countries = collect((array) data_get($decoded, 'countries', []))
                ->map(fn ($item) => trim((string) data_get($item, 'name', '')))
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        $this->countryListForAiGenerator = $countries;

        return view('admin.Currency.index', $this->data);
    }


    public function create()
    {
        return redirect()->route('admin.currency-settings.index', ['open' => 'create']);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCurrency $request)
    {
        $currency = new Currency();
        $currency->currency_symbol = $request->currency_symbol;
        $currency->currency_code = $request->currency_code;
        $currency->currency_name = $request->currency_name;
        $currency->save();

        if ($request->input('set_as_default') === '1') {
            $setting = CompanySetting::first();
            if ($setting) {
                $setting->currency_id = $currency->id;
                $setting->save();
            }
        }

        return Reply::redirect(route('admin.currency-settings.index'), __('messages.createdSuccessfully'));
    }

    public function edit($id)
    {
        return redirect()->route('admin.currency-settings.index', [
            'open' => 'edit',
            'id' => $id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreCurrency $request, $id)
    {
        $currency = Currency::findOrFail($id);
        $currency->currency_symbol = $request->currency_symbol;
        $currency->currency_code = $request->currency_code;
        $currency->currency_name = $request->currency_name;
        $currency->save();

        if ($request->input('set_as_default') === '1') {
            $setting = CompanySetting::first();
            if ($setting) {
                $setting->currency_id = $currency->id;
                $setting->save();
            }
        }

        return Reply::redirect(route('admin.currency-settings.index'), __('messages.updatedSuccessfully'));
    }

    public function setDefault(Request $request)
    {
        $request->validate([
            'currency_id' => 'required|integer|exists:currencies,id',
        ]);

        $setting = CompanySetting::first();
        if (! $setting) {
            return Reply::error(__('messages.notFound'));
        }

        $setting->currency_id = (int) $request->currency_id;
        $setting->save();

        return Reply::success(__('messages.updatedSuccessfully'));
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids)) {
            $ids = [];
        }

        $ids = array_unique(array_filter(array_map('intval', $ids)));
        $defaultId = (int) (CompanySetting::first()->currency_id ?? 0);

        foreach ($ids as $id) {
            if ($id > 0 && $id !== $defaultId) {
                Currency::destroy($id);
            }
        }

        return Reply::success(__('messages.recordDeleted'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Currency::destroy($id);
        return Reply::success(__('messages.recordDeleted'));
    }

    public function aiGenerateCurrencies(Request $request)
    {
        abort_if(! $this->user?->cans('manage_settings'), 403);

        $validated = $request->validate([
            'country_names' => 'required|array|min:1',
            'country_names.*' => 'required|string|max:191',
        ]);
        $countryNames = collect((array) $validated['country_names'])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $countryNamesText = implode(', ', $countryNames);

        $existing = Currency::query()
            ->get(['currency_name', 'currency_code', 'currency_symbol'])
            ->map(function ($row) {
                return [
                    'name' => trim((string) $row->currency_name),
                    'code' => strtoupper(trim((string) $row->currency_code)),
                    'symbol' => trim((string) $row->currency_symbol),
                ];
            })
            ->values()
            ->all();

        $existingCodes = collect($existing)->pluck('code')->map(fn ($v) => strtoupper((string) $v))->flip()->all();
        $existingNames = collect($existing)->pluck('name')->map(fn ($v) => mb_strtolower((string) $v))->flip()->all();
        $existingFamilyKeys = collect($existing)
            ->map(function (array $row) {
                return $this->currencyFamilyKey((string) ($row['name'] ?? ''), (string) ($row['symbol'] ?? ''));
            })
            ->filter()
            ->flip()
            ->all();

        $apiCandidates = $this->getAiCandidates();
        if (empty($apiCandidates)) {
            return Reply::error(__('messages.aiApiKeyRequired'));
        }

        $systemPrompt = 'You are a finance data assistant. '
            .'Return ONLY valid minified JSON with key "currencies". '
            .'"currencies" must be an array of objects with keys: name, symbol, code. '
            .'Rules: '
            .'(0) Generate currencies commonly used in the specified countries: '.$countryNamesText.'. '
            .'(1) code must be uppercase ISO-like code, 3 to 5 letters. '
            .'(2) symbol must be short (1 to 5 chars). '
            .'(3) Do not include any currency code from existing_codes list. '
            .'(4) Do not include any currency name from existing_names list. '
            .'(6) No extra keys, no markdown.';

        $userPrompt = json_encode([
            'country_names' => $countryNames,
            'existing_codes' => array_values(array_keys($existingCodes)),
            'existing_names' => array_values(array_map('strval', array_keys($existingNames))),
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

                $generated = $this->extractCurrenciesFromAiContent($content);
                if (empty($generated)) {
                    throw new \RuntimeException('AI returned empty currency list.');
                }
                $lastError = '';
                break;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        if (empty($generated)) {
            return Reply::error(__('messages.aiCurrenciesGenerationFailed').' '.($lastError ? '('.$lastError.')' : ''));
        }

        $created = 0;
        $skipped = 0;

        foreach ($generated as $item) {
            $name = trim((string) data_get($item, 'name', ''));
            $symbol = trim((string) data_get($item, 'symbol', ''));
            $code = strtoupper(trim((string) data_get($item, 'code', '')));
            $familyKey = $this->currencyFamilyKey($name, $symbol);

            $code = preg_replace('/[^A-Z]/', '', $code) ?? '';
            if (mb_strlen($code) < 3 || mb_strlen($code) > 5 || $name === '' || $symbol === '') {
                $skipped++;
                continue;
            }

            if (isset($existingCodes[$code]) || isset($existingNames[mb_strtolower($name)]) || ($familyKey !== '' && isset($existingFamilyKeys[$familyKey]))) {
                $skipped++;
                continue;
            }

            $currency = new Currency;
            $currency->currency_name = Str::limit($name, 191, '');
            $currency->currency_symbol = Str::limit($symbol, 8, '');
            $currency->currency_code = $code;
            $currency->save();

            $existingCodes[$code] = true;
            $existingNames[mb_strtolower($name)] = true;
            if ($familyKey !== '') {
                $existingFamilyKeys[$familyKey] = true;
            }
            $created++;

        }

        if ($created === 0) {
            return Reply::error(__('messages.aiCurrenciesGenerationFailed').' (AI results were duplicates or invalid.)');
        }

        return Reply::successWithData(__('messages.aiCurrenciesGeneratedSuccessfully'), [
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }

    private function getAiCandidates(): array
    {
        $configuredKeys = AiApiKey::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

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

    private function extractCurrenciesFromAiContent(string $content): array
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
            return [];
        }

        $currencies = data_get($data, 'currencies', []);

        return is_array($currencies) ? $currencies : [];
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

    private function normalizeProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));
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

    private function currencyFamilyKey(string $name, string $symbol): string
    {
        $name = trim(mb_strtolower($name));
        $symbol = trim($symbol);

        if ($name === '' || $symbol === '') {
            return '';
        }

        // Use the last word as the base currency unit (e.g. dollar, rupee, euro).
        preg_match('/([a-z]+)\s*$/u', $name, $matches);
        $unit = trim((string) ($matches[1] ?? ''));

        if ($unit === '') {
            return '';
        }

        return $symbol.'|'.$unit;
    }

    private function generateChatByProvider(string $provider, string $apiKey, string $systemPrompt, array $conversation): string
    {
        $provider = $this->normalizeProvider($provider);

        if ($provider === 'openai' || $provider === 'groq') {
            $url = $provider === 'groq'
                ? 'https://api.groq.com/openai/v1/chat/completions'
                : 'https://api.openai.com/v1/chat/completions';

            $model = $provider === 'groq'
                ? env('AI_JOB_GENERATOR_GROQ_MODEL', 'llama-3.1-8b-instant')
                : env('AI_JOB_GENERATOR_MODEL', 'gpt-5-nano');

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
                $payload['temperature'] = 0.25;
                $payload['max_tokens'] = 700;
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
                'max_tokens' => 700,
                'temperature' => 0.25,
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
                ->map(fn (array $m) => ucfirst((string) $m['role']).': '.trim((string) $m['content']))
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
                    'temperature' => 0.25,
                    'maxOutputTokens' => 700,
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