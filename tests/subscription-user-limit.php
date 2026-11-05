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
    $subscription->seat_count = 3;
    if ($subscription->userLimit() !== 3) { throw new \RuntimeException('Active account must use approved seats'); }
    $subscription->seat_count = 5;
    if ($subscription->userLimit() !== 5) { throw new \RuntimeException('Approved extra seats must be available'); }
    $subscription->status = 'trialing';
    if ($subscription->userLimit() !== 3) { throw new \RuntimeException('Trial must remain capped despite stored seats'); }
    echo "Subscription user limit checks passed: trial and approved paid seats.\n";
}
