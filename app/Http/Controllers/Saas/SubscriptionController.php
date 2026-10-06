<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Admin\AdminBaseController;

class SubscriptionController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Subscription';
        $this->pageIcon = 'icon-settings';
    }

    public function index()
    {
        return (new AccountController)->subscription()->with($this->data);
    }
}
