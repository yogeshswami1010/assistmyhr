<?php
namespace Illuminate\Database\Eloquent\Factories { trait HasFactory {} }
namespace Illuminate\Contracts\Auth { interface MustVerifyEmail {} }
namespace Illuminate\Auth { trait MustVerifyEmail {} }
namespace Illuminate\Auth\Notifications { class VerifyEmail {} }
namespace Illuminate\Foundation\Auth { class User {} }
namespace Shanmuga\LaravelEntrust\Traits { trait LaravelEntrustUserTrait {} }
namespace Illuminate\Notifications { trait Notifiable { public function notify($notification) { ($GLOBALS['notify'])(); } } }
namespace {
    $GLOBALS['settings']=['saas.enabled'=>true,'mail.driver'=>'smtp'];
    function config($value) { if(is_array($value)){foreach($value as $key=>$item)$GLOBALS['settings'][$key]=$item;return;}return $GLOBALS['settings'][$value]??null; }
    function app($name) { return $GLOBALS['manager']; }
    $GLOBALS['manager']=new class { public int $purges=0;public function purge($name){if($name!=='platform')throw new \RuntimeException('Wrong mailer purged');$this->purges++;} };
    require dirname(__DIR__).'/app/User.php';
    $user=new \App\User;
    $GLOBALS['notify']=function(){if(config('mail.driver')!==null)throw new \RuntimeException('Legacy mail driver still overrides platform');};
    $user->sendEmailVerificationNotification();
    if(config('mail.driver')!=='smtp'||$GLOBALS['manager']->purges!==2)throw new \RuntimeException('Mail state was not restored');
    $GLOBALS['notify']=function(){throw new \RuntimeException('Simulated SMTP failure');};
    try{$user->sendEmailVerificationNotification();throw new \LogicException('Expected failure');}catch(\RuntimeException $error){}
    if(config('mail.driver')!=='smtp'||$GLOBALS['manager']->purges!==4)throw new \RuntimeException('Failure did not restore mail state');
    config(['saas.enabled'=>false]);
    $GLOBALS['notify']=function(){if(config('mail.driver')!=='smtp')throw new \RuntimeException('Standalone config changed');};
    $user->sendEmailVerificationNotification();
    echo "Verification mail isolation checks passed: success, failure cleanup, standalone behavior.\n";
}
