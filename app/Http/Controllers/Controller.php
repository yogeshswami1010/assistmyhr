<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\SmsSetting;
use App\CompanySetting;
use Illuminate\Support\Arr;
use App\GoogleCaptchaSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct() {
        DB::connection()->getPdo();
        $this->checkMigrateStatus();
        $this->global = CompanySetting::first();
        $this->smsSettings = SmsSetting::first();
        $this->credentials = GoogleCaptchaSetting::first();
        $this->companySetting = CompanySetting::first();

        config(['app.name' => $this->global->company_name]);
        config(['app.url' => url('/')]);
        view()->share('smsSettings', $this->smsSettings);
        view()->share('credentials', $this->credentials);
        view()->share('companySetting', $this->companySetting);

        App::setLocale($this->global->locale);
        Carbon::setLocale($this->global->locale);
        setlocale(LC_TIME,$this->global->locale.'_'.strtoupper($this->global->locale));

        $this->middleware(function ($request, $next) {
            $this->user = auth()->user();
            if ($this->user) {
                config(['froiden_envato.allow_users_id' => true]);
            }
            return $next($request);
        });
    }
    public function checkMigrateStatus()
    {
        $status = Artisan::call('migrate:check');

        if ($status && !request()->ajax()) {
            Artisan::call('migrate', array('--force' => true)); //migrate database
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
        }
    }
    
    public function getCallingCodes()
    {
        $codes = [];
        foreach(config('calling_codes.codes') as $code) {
            $codes = Arr::add($codes, $code['code'], array('name' => $code['name'], 'dial_code' => $code['dial_code']));
        };
        return $codes;
    }
}
