<?php

namespace App\Http\Controllers\Admin;

use App\AiApiKey;
use App\AiUsageLog;
use App\Helper\Reply;
use App\Http\Requests\Admin\StoreAiApiKeyRequest;
use App\Http\Requests\Admin\UpdateAiApiKeyRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AdminAiSettingsController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.aiSettings');
        $this->pageIcon = 'icon-settings';
    }

    public function index()
    {
        abort_if(! $this->user->cans('manage_settings'), 403);

        $this->aiKeys = AiApiKey::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.ai-settings.index', $this->data);
    }

    public function usage()
    {
        abort_if(! $this->user->cans('manage_settings'), 403);

        $this->aiUsageTotals = AiUsageLog::query()
            ->selectRaw('COUNT(*) as total_requests')
            ->selectRaw('COALESCE(SUM(prompt_tokens), 0) as prompt_tokens')
            ->selectRaw('COALESCE(SUM(completion_tokens), 0) as completion_tokens')
            ->selectRaw('COALESCE(SUM(total_tokens), 0) as total_tokens')
            ->selectRaw('COALESCE(SUM(total_cost), 0) as total_cost')
            ->first();

        $this->aiUsageByUser = AiUsageLog::query()
            ->select('user_id')
            ->selectRaw('COUNT(*) as total_requests')
            ->selectRaw('COALESCE(SUM(prompt_tokens), 0) as prompt_tokens')
            ->selectRaw('COALESCE(SUM(completion_tokens), 0) as completion_tokens')
            ->selectRaw('COALESCE(SUM(total_tokens), 0) as total_tokens')
            ->selectRaw('COALESCE(SUM(total_cost), 0) as total_cost')
            ->groupBy('user_id')
            ->orderByDesc(DB::raw('SUM(total_tokens)'))
            ->get()
            ->map(function ($row) {
                $row->user_name = optional(User::find($row->user_id))->name ?? 'Unknown User';

                return $row;
            });

        return view('admin.ai-settings.usage', $this->data);
    }

    public function store(StoreAiApiKeyRequest $request)
    {
        $row = new AiApiKey;
        $row->name = $request->name;
        $row->provider = strtolower(trim((string) $request->provider));
        $row->model = $request->model ?: null;
        $row->api_key = $request->api_key;
        $row->is_active = $request->boolean('is_active');
        $row->sort_order = $request->sort_order ?? 0;
        $row->save();

        return Reply::redirect(route('admin.ai-settings.index'), 'messages.createdSuccessfully');
    }

    public function update(UpdateAiApiKeyRequest $request, AiApiKey $aiApiKey)
    {
        $aiApiKey->name = $request->name;
        $aiApiKey->provider = strtolower(trim((string) $request->provider));
        $aiApiKey->model = $request->model ?: null;
        if (! empty($request->api_key)) {
            $aiApiKey->api_key = $request->api_key;
        }
        $aiApiKey->is_active = $request->boolean('is_active');
        $aiApiKey->sort_order = $request->sort_order ?? $aiApiKey->sort_order;
        $aiApiKey->save();

        return Reply::redirect(route('admin.ai-settings.index'), 'messages.updatedSuccessfully');
    }

    public function toggleActive(Request $request, AiApiKey $aiApiKey)
    {
        abort_if(! $this->user->cans('manage_settings'), 403);

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $aiApiKey->is_active = $request->boolean('is_active');
        $aiApiKey->save();

        return Reply::success(__('messages.updatedSuccessfully'));
    }

    /**
     * Return plaintext key for authorized copy-to-clipboard (not embedded in listing HTML).
     */
    public function clipboardKey(AiApiKey $aiApiKey)
    {
        abort_if(! $this->user->cans('manage_settings'), 403);

        $plain = trim((string) $aiApiKey->api_key);
        if ($plain === '') {
            return Reply::error(__('messages.apiKeyCopyFailed'));
        }

        return Reply::successWithData(__('messages.apiKeyCopiedToClipboard'), [
            'api_key' => $plain,
        ]);
    }

    public function destroy(AiApiKey $aiApiKey)
    {
        abort_if(! $this->user->cans('manage_settings'), 403);

        $aiApiKey->delete();

        return Reply::redirect(route('admin.ai-settings.index'), 'messages.recordDeleted');
    }

    public function testKey(Request $request)
    {
        abort_if(! $this->user->cans('manage_settings'), 403);

        $validated = $request->validate([
            'provider' => ['nullable', 'string', 'max:191'],
            'api_key' => ['required', 'string', 'max:8192'],
        ]);

        $provider = $this->normalizeProvider((string) ($validated['provider'] ?? ''));
        $apiKey = trim((string) $validated['api_key']);

        if ($apiKey === '') {
            return Reply::error('API key is required.');
        }

        try {
            if ($provider === 'openai') {
                $response = Http::timeout(20)->withToken($apiKey)->get('https://api.openai.com/v1/models');
            } elseif ($provider === 'deepseek') {
                $response = Http::timeout(20)->withToken($apiKey)->get('https://api.deepseek.com/models');
            } elseif ($provider === 'groq') {
                $response = Http::timeout(20)->withToken($apiKey)->get('https://api.groq.com/openai/v1/models');
            } elseif ($provider === 'anthropic') {
                $response = Http::timeout(20)->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ])->get('https://api.anthropic.com/v1/models');
            } else {
                $response = Http::timeout(20)->get('https://generativelanguage.googleapis.com/v1beta/models?key='.$apiKey);
            }

            if (! $response->successful()) {
                $message = (string) data_get($response->json(), 'error.message', '');
                $message = $message !== '' ? $message : 'Provider request failed.';

                return Reply::error('API key test failed: '.$message);
            }
        } catch (\Throwable $e) {
            return Reply::error('API key test failed: '.$e->getMessage());
        }

        return Reply::success('API key is valid and working.');
    }

    private function normalizeProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));

        if (str_contains($provider, 'deepseek')) { return 'deepseek'; }
        if ($provider === '' || str_contains($provider, 'openai') || str_contains($provider, 'gpt')) {
            return 'openai';
        }
        if (str_contains($provider, 'groq') || str_contains($provider, 'llama')) {
            return 'groq';
        }
        if (str_contains($provider, 'anthropic') || str_contains($provider, 'claude')) {
            return 'anthropic';
        }
        if (str_contains($provider, 'gemini') || str_contains($provider, 'google')) {
            return 'gemini';
        }

        return 'openai';
    }
}
