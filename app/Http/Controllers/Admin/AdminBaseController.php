<?php

namespace App\Http\Controllers\Admin;

use App\AiUsageLog;
use App\ApplicationSetting;
use App\CompanySetting;
use App\Currency;
use App\EmailNotificationSetting;
use App\LanguageSetting;
use App\Notification;
use App\ProjectActivity;
use App\Setting;
use App\StickyNote;
use App\Traits\FileSystemSettingTrait;
use App\UniversalSearch;
use App\UserActivity;
use App\UserChat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\LinkedInSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use SebastianBergmann\CodeCoverage\Report\Xml\Project;
use App\ThemeSetting;
use App\User;
use App\ZoomSetting;

class AdminBaseController extends Controller
{
    use FileSystemSettingTrait;
    /**
     * @var array
     */
    public $data = [];

    /**
     * Currently authenticated admin user.
     *
     * @var \App\User|null
     */
    protected $user;

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->data[$name];
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[ $name ]);
    }

    /**
     * UserBaseController constructor.
     */
    public function __construct()
    {
        // Inject currently logged in user object into every view of user dashboard
        parent::__construct();

        $this->global = CompanySetting::first();
    
        $this->companyName = $this->global->company_name;

        $this->adminTheme = ThemeSetting::first();

        $this->applicationSetting = ApplicationSetting::first();
        
        $this->languageSettings = LanguageSetting::where('status', 'enabled')->orderBy('language_name')->get();
        $this->currencySettings = Currency::all();
       
        $this->zoom_setting = ZoomSetting::first();

        App::setLocale($this->global->locale);
        Carbon::setLocale($this->global->locale);
        setlocale(LC_TIME,$this->global->locale.'_'.strtoupper($this->global->locale));

        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            // Ensure `$user` is available in blade views that expect it.
            // Other properties are added through __set, but `$user` is a real property.
            $this->data['user'] = $this->user;
            $this->todoItems = $this->user->todoItems()->groupBy('status', 'position')->get();
            $this->linkedinGlobal = LinkedInSetting::first();
            $this->stickyNotes = StickyNote::where('user_id', $this->user->id)
                ->orderBy('updated_at', 'desc')
                ->get();
                $this->setFileSystemConfigs();
            // `Role::permissions()` may return either pivot-like objects or Permission models directly.
            // Eager-load only the `permissions` relation to avoid calling a non-existent nested `permission` relation.
            $this->getPermissions = User::with('roles.permissions')->find($this->user->id);
            $userPermissions = array();
            foreach ($this->getPermissions->roles[0]->permissions as $key => $value) {
                // Depending on how `Role::permissions()` is defined, `$value` can be either:
                // 1) a pivot-like object that has `permission` relation, or
                // 2) a `Permission` model directly (has `name`).
                $userPermissions[] = isset($value->permission)
                    ? $value->permission->name
                    : $value->name;
            }
            $this->userPermissions = $userPermissions;
            
            view()->share('languages', $this->languageSettings);
            
            view()->share('global', $this->global);

            return $next($request);
        });
    }

    public function generateTodoView()
    {
        $pendingTodos = $this->user->todoItems()->status('pending')->orderBy('position', 'DESC')->limit(5)->get();
        $completedTodos = $this->user->todoItems()->status('completed')->orderBy('position', 'DESC')->limit(5)->get();

        $view = view('sections.todo_items_list_dashboard', compact('pendingTodos', 'completedTodos'))->render();

        return $view;
    }

    /**
     * Persist token and estimated cost usage for AI requests.
     */
    protected function recordAiUsage(string $provider, ?string $model, array $payload): void
    {
        if (! $this->user || ! isset($this->user->id)) {
            return;
        }

        $promptTokens = (int) (data_get($payload, 'usage.prompt_tokens')
            ?? data_get($payload, 'usage.input_tokens')
            ?? data_get($payload, 'usage.prompt_token_count')
            ?? data_get($payload, 'usageMetadata.promptTokenCount')
            ?? 0);

        $completionTokens = (int) (data_get($payload, 'usage.completion_tokens')
            ?? data_get($payload, 'usage.output_tokens')
            ?? data_get($payload, 'usage.completion_token_count')
            ?? data_get($payload, 'usageMetadata.candidatesTokenCount')
            ?? 0);

        $totalTokens = (int) (data_get($payload, 'usage.total_tokens')
            ?? data_get($payload, 'usage.total_token_count')
            ?? data_get($payload, 'usageMetadata.totalTokenCount')
            ?? ($promptTokens + $completionTokens));

        if ($totalTokens <= 0 && $promptTokens <= 0 && $completionTokens <= 0) {
            return;
        }

        $cost = $this->estimateAiCost($provider, (string) ($model ?? ''), $promptTokens, $completionTokens);

        AiUsageLog::query()->create([
            'user_id' => (int) $this->user->id,
            'provider' => strtolower(trim($provider)),
            'model' => $model ?: null,
            'prompt_tokens' => max(0, $promptTokens),
            'completion_tokens' => max(0, $completionTokens),
            'total_tokens' => max(0, $totalTokens),
            'input_cost' => $cost['input_cost'],
            'output_cost' => $cost['output_cost'],
            'total_cost' => $cost['total_cost'],
        ]);
    }

    protected function estimateAiCost(string $provider, string $model, int $promptTokens, int $completionTokens): array
    {
        $provider = strtolower(trim($provider));
        $model = strtolower(trim($model));
        $inputPerMillion = 0.0;
        $outputPerMillion = 0.0;

        if ($provider === 'openai') {
            if (str_contains($model, 'gpt-5-nano')) {
                $inputPerMillion = 0.05;
                $outputPerMillion = 0.40;
            } elseif (str_contains($model, 'gpt-4o-mini')) {
                $inputPerMillion = 0.15;
                $outputPerMillion = 0.60;
            }
        }

        $inputCost = ($promptTokens / 1000000) * $inputPerMillion;
        $outputCost = ($completionTokens / 1000000) * $outputPerMillion;

        return [
            'input_cost' => round($inputCost, 6),
            'output_cost' => round($outputCost, 6),
            'total_cost' => round($inputCost + $outputCost, 6),
        ];
    }
}
