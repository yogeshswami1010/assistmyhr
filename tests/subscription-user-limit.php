<?php
namespace App\Saas {
    #[\AllowDynamicProperties]
    class LandlordModel {}
}
namespace {
    require dirname(__DIR__).'/app/Saas/Subscription.php';
    $subscription = new \App\Saas\Subscription;
    $subscription->plan = (object) ['max_users' => 3];
    $subscription->status = 'trialing';
    if ($subscription->userLimit() !== 3) { throw new \RuntimeException('Trial must allow three users'); }
    $subscription->status = 'active';
    if ($subscription->userLimit() !== null) { throw new \RuntimeException('Upgrade must remove the user cap'); }
    echo "Subscription user limit checks passed: trial capped, active unlimited.\n";
}
