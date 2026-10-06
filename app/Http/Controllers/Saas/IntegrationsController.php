<?php
namespace App\Http\Controllers\Saas;
use App\Http\Controllers\Admin\AdminBaseController;
use Illuminate\Http\Request;
class IntegrationsController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Workspace integrations';
        $this->pageIcon = 'icon-settings';
    }
    public function index(Request $request)
    {
        return (new AccountController)->integrations($request)->with($this->data);
    }
}
