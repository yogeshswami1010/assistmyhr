<form class="client-access-form" method="post" action="{{ route('superadmin.tenants.login-as', $tenant) }}" onsubmit="return confirm('Access this client account as its owner? This access will be recorded.');">
    @csrf
    <button class="client-access-button" type="submit" @disabled(in_array($tenant->status, ['provisioning', 'failed'], true)) title="{{ in_array($tenant->status, ['provisioning', 'failed'], true) ? 'This workspace must be ready before you can access it.' : 'Sign in as the client owner to manage their account.' }}">Access Client Account <span aria-hidden="true">&rarr;</span></button>
</form>
