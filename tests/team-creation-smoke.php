<?php

// Exercise the real controller without a database or SMTP server.
namespace App\Http\Controllers\Admin {
    class AdminBaseController { public $user; }
}
namespace App\Http\Requests {
    #[\AllowDynamicProperties]
    class StoreTeam { public function hasFile($key) { return false; } }
}
namespace Illuminate\Support\Facades {
    class Hash { public static function make($value) { return 'hashed'; } }
}
namespace App\Notifications {
    class NewUser { public function __construct($password) {} }
}
namespace App {
    #[\AllowDynamicProperties]
    class User {
        public static array $saved = [];
        public static bool $failMail = false;
        public bool $roleAttached = false;
        public function save() { self::$saved[] = $this; }
        public function roles() { return $this; }
        public function attach($role) { $this->roleAttached = true; }
        public function notify($notification) {
            if (self::$failMail) { throw new \RuntimeException('Simulated SMTP failure'); }
        }
    }
}
namespace {
    function __($key) { return $key; }
    function trans($key) { return $key; }
    function route($key) { return '/team'; }
    function abort_if($condition, $code) { if ($condition) { throw new \RuntimeException('Forbidden'); } }
    function report($error) { $GLOBALS['reported'][] = $error; }
    require dirname(__DIR__).'/app/Helper/Reply.php';
    require dirname(__DIR__).'/app/Http/Controllers/Admin/AdminTeamController.php';
    $controller = (new \ReflectionClass(\App\Http\Controllers\Admin\AdminTeamController::class))->newInstanceWithoutConstructor();
    $controller->user = new class { public function cans($permission) { return true; } };
    $request = new \App\Http\Requests\StoreTeam;
    foreach (['name'=>'Member', 'email'=>'member@example.test', 'password'=>'secret', 'calling_code'=>1, 'mobile'=>'1234567890', 'role_id'=>1] as $key=>$value) { $request->$key = $value; }
    foreach ([false, true] as $failMail) {
        \App\User::$failMail = $failMail;
        \App\User::$saved = [];
        $GLOBALS['reported'] = [];
        $response = $controller->store($request);
        if ($response['status'] !== 'success' || $response['action'] !== 'redirect' || count(\App\User::$saved) !== 1 || !\App\User::$saved[0]->roleAttached) {
            throw new \RuntimeException('Member creation must succeed with a role and redirect');
        }
        if (count($GLOBALS['reported']) !== (int) $failMail || ($failMail && $response['message'] !== 'messages.teamCreatedWithoutWelcomeEmail')) {
            throw new \RuntimeException('Email failure must be reported and explained');
        }
    }
    echo "Team creation checks passed: welcome email success and failure.\n";
}
