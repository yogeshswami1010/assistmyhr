<?php

namespace App\Http\Controllers\Admin;

use App\AiApiKey;
use App\Helper\Reply;
use App\Http\Requests\StoreTodo;
use App\TodoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AdminTodoItemController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageIcon = 'icon-speedometer';
        $this->pageTitle = __('menu.todoList');
    }

    public function index()
    {
        $this->teamMembers = \App\User::orderBy('name')->get(['id', 'name']);
        $this->boardTodos = $this->boardTodosPayload();

        return view('admin.todo-list.index', $this->data);
    }

    public function boardData()
    {
        return response()->json(['todos' => $this->boardTodosPayload()]);
    }

    protected function boardTodosPayload(): array
    {
        $todos = \App\TodoItem::visibleTo($this->user->id)->with(['assignee','user'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'completed' THEN 3 ELSE 4 END")
            ->orderBy('position')
            ->get();

        return $todos->map(fn (TodoItem $t) => $this->formatTodoForBoard($t))->values()->all();
    }

    protected function formatTodoForBoard(TodoItem $t): array
    {
        return [
            'id' => $t->id,
            'title' => $t->title,
            'assigned_to' => $t->assigned_to, 'assignee' => $t->assignee?->name ?? $t->user?->name ?? '',
            'desc' => $t->description ?? '',
            'priority' => $t->priority ?? 'medium',
            'tag' => $t->tag ?? '',
            'status' => $t->status,
            'due' => $t->due_date ? $t->due_date->format('Y-m-d') : '',
        ];
    }

    protected function isTodoBoardRequest(): bool
    {
        $ref = (string) request()->header('Referer', '');

        return str_contains($ref, '/admin/todo-items');
    }

    public function create(Request $request)
    {
        return view('admin.todo-list.todo-item-create', ['teamMembers' => \App\User::orderBy('name')->get(['id','name'])]);
    }

    public function edit(Request $request, $id)
    {
        $todoItem = \App\TodoItem::visibleTo($this->user->id)->where('id', $id)->firstOrFail();

        return view('admin.todo-list.todo-item-edit', ['todoItem' => $todoItem, 'teamMembers' => \App\User::orderBy('name')->get(['id','name'])]);
    }

    public function store(StoreTodo $request)
    {
        $status = $request->input('status', 'pending');
        if (! in_array($status, ['pending', 'in_progress', 'completed'], true)) {
            $status = 'pending';
        }

        $maxPos = (int) \App\TodoItem::visibleTo($this->user->id)->where('status', $status)->max('position');

        $todoItem = new TodoItem;
        $todoItem->user_id = $this->user->id;
        $todoItem->assigned_to = $request->input('assigned_to') ?: $this->user->id;
        $todoItem->title = $request->title;
        $todoItem->description = $request->input('description');
        $todoItem->priority = $request->input('priority', 'medium');
        $todoItem->tag = $request->input('tag') ?: null;
        $todoItem->due_date = $request->input('due_date') ?: null;
        $todoItem->status = $status;
        $todoItem->position = $maxPos + 1;

        $todoItem->save();

        return Reply::successWithData(__('messages.todos.todoCreatedSuccessfully'), [
            'todo' => $this->formatTodoForBoard($todoItem->fresh()), 'view' => $this->generateTodoView(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:500',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'status' => 'required|in:pending,in_progress,completed',
            'description' => 'sometimes|nullable|string',
            'priority' => 'sometimes|nullable|in:low,medium,high',
            'tag' => 'sometimes|nullable|string|max:64',
            'due_date' => 'sometimes|nullable|date',
        ]);

        if ($validator->fails()) {
            return Reply::formErrors($validator);
        }

        $todoItem = \App\TodoItem::visibleTo($this->user->id)->where('id', $id)->firstOrFail();

        if ($todoItem->status !== $request->status) {
            $todoItemsToUpdate = $this->todoItems->filter(function ($value, $key) use ($todoItem) {
                return $value->status == $todoItem->status && $value->position > $todoItem->position;
            });

            $lastTodoItem = $this->todoItems->last(function ($value, $key) use ($request) {
                return $value->status == $request->status;
            });

            $position = ! is_null($lastTodoItem) ? $lastTodoItem->position + 1 : 1;

            if ($todoItemsToUpdate->count() > 0) {
                foreach ($todoItemsToUpdate as $todoItemToUpdate) {
                    $todoItemToUpdate->position = $todoItemToUpdate->position - 1;
                    $todoItemToUpdate->save();
                }
            }

            $todoItem->position = $position;
            $todoItem->status = $request->status;
        }

        $todoItem->title = $request->title;
        if ($request->has('assigned_to')) { $todoItem->assigned_to = $request->input('assigned_to') ?: $todoItem->user_id; }

        if ($request->has('description')) {
            $todoItem->description = $request->input('description');
        }
        if ($request->has('priority')) {
            $todoItem->priority = $request->input('priority', 'medium');
        }
        if ($request->has('tag')) {
            $todoItem->tag = $request->input('tag') ?: null;
        }
        if ($request->has('due_date')) {
            $todoItem->due_date = $request->input('due_date') ?: null;
        }

        $todoItem->save();

        return Reply::successWithData(__('messages.todos.todoUpdatedSuccessfully'), [
            'todo' => $this->formatTodoForBoard($todoItem->fresh()), 'view' => $this->generateTodoView(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $todoItem = \App\TodoItem::visibleTo($this->user->id)->where('id', $id)->firstOrFail();

        $allAfterTodos = $this->todoItems->filter(function ($value, $key) use ($todoItem) {
            return $value->status == $todoItem->status && $value->position > $todoItem->position;
        });

        if ($allAfterTodos->count() > 0) {
            foreach ($allAfterTodos as $todo) {
                $todo->position = $todo->position - 1;
                $todo->save();
            }
        }

        $todoItem->delete();

        if (str_contains((string) request()->header('Referer', ''), '/admin/dashboard')) {
            $view = $this->generateTodoView();

            return Reply::successWithData(__('messages.todos.todoDeletedSuccessfully'), ['view' => $view]);
        }

        if ($this->isTodoBoardRequest()) {
            return Reply::success(__('messages.todos.todoDeletedSuccessfully'));
        }

        return Reply::success(__('messages.todos.todoDeletedSuccessfully'));
    }

    public function moveToColumn(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $todoItem = \App\TodoItem::visibleTo($this->user->id)->where('id', $request->id)->firstOrFail();
        $oldStatus = $todoItem->status;
        $newStatus = $request->status;

        if ($oldStatus === $newStatus) {
            return Reply::success(__('messages.todos.todoUpdatedSuccessfully'));
        }

        $after = \App\TodoItem::visibleTo($this->user->id)
            ->where('status', $oldStatus)
            ->where('position', '>', $todoItem->position)
            ->orderBy('position')
            ->get();

        foreach ($after as $t) {
            $t->position = $t->position - 1;
            $t->save();
        }

        $maxPos = (int) \App\TodoItem::visibleTo($this->user->id)->where('status', $newStatus)->max('position');
        $todoItem->status = $newStatus;
        $todoItem->position = $maxPos + 1;
        $todoItem->save();

        return Reply::success(__('messages.todos.todoUpdatedSuccessfully'));
    }

    public function toggleComplete(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $todoItem = \App\TodoItem::visibleTo($this->user->id)->where('id', $request->id)->firstOrFail();
        $newStatus = $todoItem->status === 'completed' ? 'pending' : 'completed';
        $request->merge(['status' => $newStatus]);

        return $this->moveToColumn($request);
    }

    public function aiGenerateDescription(Request $request)
    {
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            return Reply::error(__('messages.taskTitleRequired'));
        }

        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

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

        if (empty($apiCandidates)) {
            return Reply::error(__('messages.aiApiKeyRequired'));
        }

        $systemPrompt = 'You are an assistant that writes short, clear descriptions for a task management system. '
            .'Given a task title, produce a helpful description (min 2,max 6 short sentences). '
            .'Output ONLY plain text with no quotes, no bullets, no markdown, and no extra commentary.';

        $userPrompt = 'Task title: '.(string) $validated['title'];

        $lastError = '';
        foreach ($apiCandidates as $candidate) {
            try {
                $content = $this->generateChatByProvider(
                    (string) $candidate['provider'],
                    (string) $candidate['api_key'],
                    $systemPrompt,
                    [['role' => 'user', 'content' => $userPrompt]]
                );

                $desc = trim($content);
                if ($desc === '') {
                    throw new \RuntimeException('Empty AI response.');
                }

                return Reply::successWithData(__('messages.aiDescriptionGeneratedSuccessfully'), [
                    'description' => $desc,
                ]);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        return Reply::error(__('messages.aiDescriptionGenerationFailed').($lastError ? ' '.$lastError : ''));
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
                // Ignore and fall back to raw value.
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
                $payload['max_completion_tokens'] = 600;
            } else {
                $payload['temperature'] = 0.35;
                $payload['max_tokens'] = 200;
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

            // GPT-5 family can intermittently return empty visible text on first try.
            if ($content === '' && $provider === 'openai') {
                $retryPayload = $payload;
                $retryPayload['max_completion_tokens'] = max((int) ($retryPayload['max_completion_tokens'] ?? 600), 1200);

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
                'max_tokens' => 200,
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
                    'maxOutputTokens' => 200,
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

    public function updateTodoItem(Request $request)
    {
        $todoItem = \App\TodoItem::visibleTo($this->user->id)->where('id', $request->id)->firstOrFail();

        if ($request->position) {
            $oldPosition = $request->position['oldPosition'];
            $newPosition = $request->position['newPosition'];
        } else {
            $oldPosition = $todoItem->position;
            $lastTodoItem = $this->todoItems->last(function ($value, $key) use ($request) {
                return $value->status == $request->status;
            });

            $newPosition = ! is_null($lastTodoItem) ? $lastTodoItem->position + 1 : 1;
        }

        if ($request->exists('status')) {
            if ($request->status == 'completed') {
                $pendingTodoItemsGreaterThanOldPosition = $this->todoItems->filter(function ($value, $key) use ($todoItem, $oldPosition) {
                    return $value->status == $todoItem->status && $value->position > $oldPosition;
                });

                foreach ($pendingTodoItemsGreaterThanOldPosition as $pending) {
                    $pending->position = $oldPosition;
                    $pending->save();
                    $oldPosition++;
                }

                $completedTodoItemsGreaterThanOldPosition = $this->todoItems->filter(function ($value, $key) use ($newPosition) {
                    return $value->status == 'completed' && $value->position >= $newPosition;
                });

                foreach ($completedTodoItemsGreaterThanOldPosition as $completed) {
                    $newPosition++;
                    $completed->position = $newPosition;
                    $completed->save();
                }
            } else {
                $targetStatus = $request->status;
                $targetColumnItems = $this->todoItems->filter(function ($value, $key) use ($newPosition, $targetStatus) {
                    return $value->status == $targetStatus && $value->position >= $newPosition;
                });

                foreach ($targetColumnItems as $pending) {
                    $newPosition++;
                    $pending->position = $newPosition;
                    $pending->save();
                }

                $completedTodoItemsGreaterThanOldPosition = $this->todoItems->filter(function ($value, $key) use ($todoItem, $oldPosition) {
                    return $value->status == $todoItem->status && $value->position > $oldPosition;
                });

                foreach ($completedTodoItemsGreaterThanOldPosition as $completed) {
                    $completed->position = $oldPosition;
                    $completed->save();
                    $oldPosition++;
                }
            }
            $todoItem->status = $request->status;
            $todoItem->position = $request->position ? $request->position['newPosition'] : $newPosition;
            $todoItem->save();
        } else {
            // dd($oldPosition, $newPosition);
            $status = $todoItem->status;
            $todos = \App\TodoItem::visibleTo($this->user->id)->status($status)->orderBy('position')->get();
            $oldTodo = $todos->filter(function ($value, $key) use ($oldPosition) {
                return $value->position == $oldPosition;
            })->first();

            $betweenTodos = $todos->filter(function ($value, $key) use ($oldPosition, $newPosition) {
                if ($newPosition > $oldPosition) {
                    $todos = $value->position > $oldPosition && $value->position <= $newPosition;
                } else {
                    $todos = $value->position < $oldPosition && $value->position >= $newPosition;
                }

                return $todos;
            });

            if ($betweenTodos->count() > 0) {
                foreach ($betweenTodos as $todo) {
                    if ($newPosition > $oldPosition) {
                        $todo->position = $todo->position - 1;
                    } else {
                        $todo->position = $todo->position + 1;
                    }
                    $todo->save();
                }
            }

            if ($oldTodo->count() > 0) {
                $oldTodo->position = $newPosition;

                $oldTodo->save();
            }
        }

        $view = $this->generateTodoView();

        return Reply::dataOnly(['view' => $view]);
    }
}
