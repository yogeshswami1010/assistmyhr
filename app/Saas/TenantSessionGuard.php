<?php

namespace App\Saas;

use Illuminate\Auth\SessionGuard;

class TenantSessionGuard extends SessionGuard
{
    public function getName()
    {
        // Preserve existing sessions; InitializeSaasTenant clears this key on workspace changes.
        return 'login_'.$this->name.'_'.sha1(SessionGuard::class);
    }

    public function getRecallerName()
    {
        $name = 'remember_'.$this->name.'_'.sha1(SessionGuard::class);
        $tenant = app(TenantContext::class)->current();

        return $tenant ? $name.'_'.$tenant->uuid : $name;
    }

    protected function recaller()
    {
        // Never restore a client identity until its database has been selected.
        return app(TenantContext::class)->current() ? parent::recaller() : null;
    }

    protected function createRecaller($value)
    {
        return parent::createRecaller($value)->withSecure(
            $this->request->isSecure() || (bool) config('session.secure')
        );
    }
}
