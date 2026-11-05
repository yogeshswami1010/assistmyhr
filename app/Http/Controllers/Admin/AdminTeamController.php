<?php

namespace App\Http\Controllers\Admin;

use App\EmailSetting;
use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\StoreTeam;
use App\Http\Requests\UpdateTeam;
use App\Notifications\NewUser;
use App\Role;
use App\RoleUser;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class AdminTeamController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.team');
        $this->pageIcon = 'icon-people';
        view()->share('calling_codes', $this->getCallingCodes());
    }

    public function index()
    {
        abort_if(! $this->user->cans('view_team'), 403);

        $this->users = User::all();
        $this->roles = Role::all();

        return view('admin.team.index', $this->data);
    }

    public function create()
    {
        abort_if(! $this->user->cans('add_team'), 403);

        $this->roles = Role::all();
        $this->smtpSetting = EmailSetting::first();

        return view('admin.team.create', $this->data);
    }

    public function store(StoreTeam $request)
    {
        abort_if(! $this->user->cans('add_team'), 403);

        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->calling_code = $request->calling_code;
        $user->mobile = $request->mobile;

        if ($request->hasFile('image')) {
            $user->image = Files::uploadLocalOrS3($request->image, 'profile');
        }
        $user->save();

        // attach role
        $user->roles()->attach($request->role_id);

        // Email delivery must not turn a completed creation into a failed request.
        try {
            $user->notify(new NewUser($request->password));
        } catch (\Throwable $exception) {
            report($exception);

            return Reply::redirect(route('admin.team.index'), __('messages.teamCreatedWithoutWelcomeEmail'));
        }

        return Reply::redirect(route('admin.team.index'), __('menu.team').' '.__('messages.createdSuccessfully'));
    }

    public function edit($id)
    {
        abort_if(! $this->user->cans('edit_team'), 403);

        if ($id == $this->user->id) {
            abort(403);
        }

        $this->roles = Role::all();
        $this->team = User::find($id);

        return view('admin.team.edit', $this->data);
    }

    public function update(UpdateTeam $request, $id)
    {
        abort_if(! $this->user->cans('edit_team'), 403);

        if ($id == $this->user->id) {
            abort(403);
        }

        $user = User::find($id);
        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->password != '') {
            $user->password = Hash::make($request->password);
        }

        if (($request->mobile != $user->mobile || $request->calling_code != $user->calling_code) && $user->mobile_verified == 1) {
            $user->mobile_verified = 0;
        }

        $user->mobile = $request->mobile;
        $user->calling_code = $request->calling_code;

        if ($request->hasFile('image')) {
            $user->image = Files::uploadLocalOrS3($request->image, 'profile');
        } else {
            Files::deleteFile($user->image, 'profile');
            $user->image = null;
        }
        $user->save();

        // attach role
        RoleUser::where('user_id', $id)->delete();
        $user->roles()->attach($request->role_id);

        return Reply::redirect(route('admin.team.index'), __('menu.team').' '.__('messages.updatedSuccessfully'));
    }

    public function data()
    {
        abort_if(! $this->user->cans('view_team'), 403);

        $users = User::all();
        $roles = Role::all();
        $admin = $users->filter(function ($user) {
            return $user->hasRole('admin');
        })->sortBy('created_at')->first();

        $svgEdit = '<svg class="h-3.5 w-3.5" fill="none" stroke="#2563EB" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
        $svgDel = '<svg class="h-3.5 w-3.5" fill="none" stroke="#EF4444" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';

        return DataTables::of($users)
            ->addColumn('action', function ($row) use ($admin, $svgEdit, $svgDel) {
                if ($row->id == $this->user->id) {
                    return '';
                }

                $parts = [];
                $canTarget = $admin === null || $row->id != $admin->id;

                if ($canTarget && $this->user->cans('edit_team')) {
                    $parts[] = '<a href="'.route('admin.team.edit', [$row->id]).'" class="jc-action-btn jc-btn-edit inline-flex" data-toggle="tooltip" data-original-title="'.e(__('app.edit')).'" onclick="this.blur()">'.$svgEdit.'</a>';
                }

                if ($canTarget && $this->user->cans('delete_team')) {
                    $parts[] = '<a href="javascript:;" class="jc-action-btn jc-btn-del sa-params inline-flex" data-toggle="tooltip" data-row-id="'.$row->id.'" data-original-title="'.e(__('app.delete')).'" onclick="this.blur()">'.$svgDel.'</a>';
                }

                if ($parts === []) {
                    return '';
                }

                return '<div class="flex flex-wrap items-center justify-end gap-2">'.implode('', $parts).'</div>';
            })
            ->addColumn('role_name', function ($row) use ($roles, $admin) {
                if ($admin && $row->id == $admin->id) {
                    return e($row->role->role->display_name);
                }
                if ($row->id == $this->user->id) {
                    return e($row->role->role->display_name);
                }

                if (! $this->user->cans('edit_team')) {
                    return e($row->role->role->display_name);
                }

                $roleOption = '<select name="role_id" class="role_id min-w-[140px] max-w-[220px] cursor-pointer rounded-xl border border-[#E2DED8] bg-white px-3 py-2 text-[12.5px] font-medium text-[#3D4A5C] outline-none transition hover:border-[#C4CBD4] focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/20" data-row-id="'.$row->id.'">';
                foreach ($roles as $role) {
                    $roleOption .= '<option value="'.$role->id.'"';
                    if ($row->role->role->id == $role->id) {
                        $roleOption .= ' selected';
                    }
                    $roleOption .= '>'.e(ucwords($role->display_name)).'</option>';
                }
                $roleOption .= '</select>';

                return $roleOption;
            })
            ->editColumn('name', function ($row) {
                $online = ($row->last_seen_at && \Carbon\Carbon::parse($row->last_seen_at)->gte(now()->subSeconds(75)))
                    || $row->id === $this->user->id;
                $dotClass = $online ? 'bg-emerald-500' : 'bg-red-500';
                $statusText = $online ? 'Online' : 'Offline';

                return '<div class="flex items-center gap-3">'
                    .'<span class="relative inline-flex shrink-0">'
                    .'<img src="'.e($row->profile_image_url).'" alt="" class="h-9 w-9 rounded-full object-cover ring-2 ring-[#EEF0F5]" width="36" height="36" loading="lazy" />'
                    .'<span data-ats-presence-user="'.$row->id.'" title="'.$statusText.'" aria-label="'.$statusText.'" class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-white '.$dotClass.'"></span>'
                    .'</span>'
                    .'<span class="text-[13.5px] font-semibold text-[#1A1E2E]">'.e(ucwords($row->name)).'</span>'
                    .'</div>';
            })
            ->editColumn('email', function ($row) {
                return '<span class="text-[13px] text-[#5A6478]">'.e($row->email).'</span>';
            })
            ->rawColumns(['name', 'email', 'action', 'role_name'])
            ->addIndexColumn()
            ->make(true);
    }

    public function destroy($id)
    {
        abort_if(! $this->user->cans('delete_team'), 403);

        User::destroy($id);

        return Reply::success(__('messages.recordDeleted'));
    }

    public function changeRole(Request $request)
    {
        // attach role
        $user = User::find($request->teamId);

        RoleUser::where('user_id', $request->teamId)->delete();
        $user->roles()->attach($request->roleId);

        return Reply::dataOnly(['status' => 'success']);
    }
}
