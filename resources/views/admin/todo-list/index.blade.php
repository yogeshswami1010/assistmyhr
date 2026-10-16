@extends('layouts.app')

@section('hide-ra-page-header')
<span class="sr-only">.</span>
@endsection

@push('head-script')
<style>
    .ra-todos-board { font-family: 'Plus Jakarta Sans', sans-serif; color: #1A1E2E; }
    .ra-todos-board .tab-bar { display: flex; background: #F1F3F7; border-radius: 12px; padding: 4px; gap: 3px; flex-wrap: wrap; }
    .ra-todos-board .tab { padding: 7px 16px; border-radius: 9px; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.18s; color: #8892A0; border: none; background: transparent; display: flex; align-items: center; gap: 6px; white-space: nowrap; }
    .ra-todos-board .tab.on { background: #fff; color: #1A1E2E; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
    .ra-todos-board .tab-cnt { font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 999px; background: rgba(0,0,0,0.07); }
    .ra-todos-board .tab.on .tab-cnt { background: #EFF6FF; color: #2563EB; }
    .ra-todos-board .todo-columns { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 20px; }
    @media (max-width: 1100px) {
        .ra-todos-board .todo-columns { grid-template-columns: 1fr; }
    }
    .ra-todos-board .todo-col { background: #E8EBF0; border-radius: 18px; overflow: hidden; display: flex; flex-direction: column; min-height: 200px; }
    .ra-todos-board .col-hd { padding: 14px 16px 12px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
    .ra-todos-board .col-title { display: flex; align-items: center; gap: 8px; }
    .ra-todos-board .col-name { font-size: 13px; font-weight: 800; color: #1A1E2E; letter-spacing: -0.01em; }
    .ra-todos-board .col-badge { font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 999px; color: #fff; }
    .ra-todos-board .col-body { flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden; padding: 6px 10px 12px; display: flex; flex-direction: column; gap: 8px; max-height: min(60vh, 520px); }
    .ra-todos-board .col-body::-webkit-scrollbar { width: 3px; }
    .ra-todos-board .col-body::-webkit-scrollbar-thumb { background: #C4CBD4; border-radius: 3px; }
    .ra-todos-board .col-add-btn { display: flex; align-items: center; gap: 5px; padding: 8px 12px; border-radius: 10px; border: 1.5px dashed #C4CBD4; background: transparent; font-size: 11.5px; font-weight: 600; color: #8892A0; cursor: pointer; transition: all 0.15s; margin: 0 10px 10px; flex-shrink: 0; }
    .ra-todos-board .col-add-btn:hover { border-color: #2563EB; color: #2563EB; background: #EFF6FF; }
    .ra-todos-board .todo-card { background: #fff; border-radius: 13px; padding: 14px 14px 16px; cursor: pointer; transition: transform 0.18s, box-shadow 0.18s, border-color 0.18s; position: relative; overflow: visible; border: 1.5px solid transparent; }
    .ra-todos-board .todo-card:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(15,31,61,0.09); border-color: #E2DED8; }
    .ra-todos-board .todo-card.done { opacity: 0.65; }
    .ra-todos-board .card-bar { position: absolute; left: 0; top: 0; bottom: 0; width: 3px; border-radius: 13px 0 0 13px; z-index: 0; pointer-events: none; }
    .ra-todos-board .card-body { position: relative; z-index: 1; padding-left: 10px; min-width: 0; }
    .ra-todos-board .card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
    .ra-todos-board .card-title { font-size: 13px; font-weight: 700; color: #1A1E2E; line-height: 1.4; flex: 1; }
    .ra-todos-board .card-title.done-text { text-decoration: line-through; color: #8892A0; }
    .ra-todos-board .card-check { width: 18px; height: 18px; border-radius: 50%; border: 2px solid #D1D9E8; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: all 0.18s; margin-top: 1px; }
    .ra-todos-board .card-check.checked { background: #059669; border-color: #059669; }
    .ra-todos-board .card-check:hover:not(.checked) { border-color: #2563EB; }
    .ra-todos-board .card-desc { font-size: 12px; color: #8892A0; line-height: 1.5; margin-bottom: 10px; }
    .ra-todos-board .card-foot { display: flex; align-items: center; justify-content: space-between; padding-top: 9px; border-top: 1px solid #F0EEE9; flex-wrap: wrap; gap: 6px; }
    .ra-todos-board .priority-pill { font-size: 10.5px; font-weight: 700; padding: 3px 9px; border-radius: 999px; letter-spacing: 0.02em; }
    .ra-todos-board .p-high { background: #FFF1F2; color: #E11D48; }
    .ra-todos-board .p-medium { background: #FFF7ED; color: #C2410C; }
    .ra-todos-board .p-low { background: #ECFDF5; color: #059669; }
    .ra-todos-board .card-date { font-size: 11px; color: #B0B8C4; font-weight: 600; display: flex; align-items: center; gap: 4px; }
    .ra-todos-board .card-tag { font-size: 10.5px; font-weight: 600; padding: 2px 8px; border-radius: 5px; }
    .ra-todos-board .card-actions { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 6px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #F0EEE9; }
    .ra-todos-board .card-actions .ra-card-btn { display: inline-flex; align-items: center; justify-content: center; gap: 4px; font-size: 11.5px; font-weight: 600; padding: 6px 10px; border-radius: 8px; border: none; background: transparent; cursor: pointer; font-family: inherit; line-height: 1.3; white-space: nowrap; }
    .ra-todos-board .card-actions .ra-card-btn--edit { color: #2563EB; }
    .ra-todos-board .card-actions .ra-card-btn--edit:hover { background: #EFF6FF; }
    .ra-todos-board .card-actions .ra-card-btn--del { color: #EF4444; }
    .ra-todos-board .card-actions .ra-card-btn--del:hover { background: #FFF1F2; }
    .ra-todos-board .card-actions .ra-card-btn svg { width: 14px; height: 14px; flex-shrink: 0; }
    .ra-todos-board .t-search { display: flex; align-items: center; gap: 8px; background: #fff; border: 1.5px solid #E2DED8; border-radius: 10px; padding: 7px 14px; transition: border-color 0.2s; }
    .ra-todos-board .t-search:focus-within { border-color: #2563EB; }
    .ra-todos-board .t-search input { border: none; outline: none; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px; color: #1A1E2E; width: 200px; max-width: 100%; background: transparent; }
    .ra-todos-board .t-search input::placeholder { color: #B0B8C4; }
    .ra-todos-board .todo-card.dragging { opacity: 0.35; transform: rotate(1.5deg) scale(0.97); }
    .ra-todos-board .todo-col.drag-over .col-body { background: rgba(37,99,235,0.05); border-radius: 10px; outline: 2px dashed rgba(37,99,235,0.25); }
    .ra-todos-board .modal-bg { display: none; position: fixed; inset: 0; background: rgba(15,31,61,0.5); z-index: 200; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
    .ra-todos-board .modal-bg.show { display: flex; animation: raTodoFi 0.2s ease; }
    @keyframes raTodoFi { from { opacity: 0; } to { opacity: 1; } }
    .ra-todos-board .modal-todo { display:flex;flex-direction:column; background: #fff; border-radius: 20px; width: 460px; max-width: 94vw; max-height: 90vh; overflow: hidden; animation: raTodoPi 0.28s cubic-bezier(0.22,1,0.36,1); }
    @keyframes raTodoPi { from { opacity: 0; transform: scale(0.94) translateY(8px); } to { opacity: 1; transform: scale(1) translateY(0); } }
    .ra-todos-board .modal-hd { flex-shrink:0; padding: 20px 24px 16px; border-bottom: 1px solid #F0EEE9; display: flex; align-items: center; justify-content: space-between; }
    .ra-todos-board .modal-body { min-height:0;flex:1 1 auto;overscroll-behavior:contain;scrollbar-width:thin; padding: 20px 24px; display: flex; flex-direction: column; gap: 14px; overflow-y: auto; }
    .ra-todos-board .modal-footer { flex-shrink:0; padding: 14px 24px; border-top: 1px solid #F0EEE9; display: flex; justify-content: flex-end; gap: 10px; }
    .ra-todos-board .fl { font-size: 11.5px; font-weight: 700; color: #5A6478; margin-bottom: 5px; }
    .ra-todos-board .fi { width: 100%; border: 1.5px solid #E2DED8; border-radius: 10px; padding: 10px 14px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px; color: #1A1E2E; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
    .ra-todos-board .fi:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,0.09); }
    .ra-todos-board .fi::placeholder { color: #C4CBD4; }
    .ra-todos-board .fs { width: 100%; border: 1.5px solid #E2DED8; border-radius: 10px; padding: 10px 32px 10px 14px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px; color: #1A1E2E; background: #fff; appearance: none; cursor: pointer; outline: none; transition: border-color 0.2s; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='7' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%239CA3AF' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; }
    .ra-todos-board .fs:focus { border-color: #2563EB; }
    .ra-todos-board .btn-c { background: #F1F3F7; color: #5A6478; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px; font-weight: 600; border: none; border-radius: 9px; padding: 10px 20px; cursor: pointer; }
    .ra-todos-board .btn-c:hover { background: #E5E7ED; }
    .ra-todos-board .btn-s { background: #2563EB; color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px; font-weight: 700; border: none; border-radius: 9px; padding: 10px 24px; cursor: pointer; transition: background 0.2s, box-shadow 0.2s; }
    .ra-todos-board .btn-s:hover { background: #1d4ed8; box-shadow: 0 4px 14px rgba(37,99,235,0.38); }
    .ra-todos-board .btn-danger { background: #EF4444; color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px; font-weight: 600; border: none; border-radius: 9px; padding: 10px 20px; cursor: pointer; }
    .ra-todos-board .del-modal { background: #fff; border-radius: 20px; width: 380px; max-width: 94vw; overflow: hidden; animation: raTodoPi 0.28s cubic-bezier(0.22,1,0.36,1); }
    .ra-todos-board .toast-todo { position: fixed; bottom: 24px; right: 24px; background: #1A1E2E; color: #fff; font-size: 13px; font-weight: 500; padding: 12px 18px; border-radius: 12px; display: flex; align-items: center; gap: 8px; z-index: 300; opacity: 0; transform: translateY(8px); transition: opacity 0.25s, transform 0.25s; pointer-events: none; }
    .ra-todos-board .toast-todo.show { opacity: 1; transform: translateY(0); }
    .ra-todos-board .tdot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    @keyframes raTodoFu { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .ra-todos-board .a1 { animation: raTodoFu 0.42s ease 0.04s both; }
    .ra-todos-board .a2 { animation: raTodoFu 0.42s ease 0.09s both; }
    .ra-todos-board .a3 { animation: raTodoFu 0.42s ease 0.14s both; }
</style>
@endpush

@section('content')
<div class="ra-todos-board -mx-4 px-4 sm:mx-0 sm:px-0 pb-8">
    <div class="flex flex-col gap-4 mb-5 a1 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 max-w-xl">
            <p class="text-[12.5px] text-[#8892A0] leading-relaxed">@lang('modules.module.todos.boardSubtitle')</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="bg-white border border-[#E8E6E1] rounded-xl px-4 py-2.5 flex items-center gap-3">
                <svg width="36" height="36" viewBox="0 0 36 36" aria-hidden="true">
                    <circle cx="18" cy="18" r="15" fill="none" stroke="#EEF0F5" stroke-width="3"/>
                    <circle id="raTodoProgressCircle" cx="18" cy="18" r="15" fill="none" stroke="#059669" stroke-width="3" stroke-dasharray="94.2" stroke-dashoffset="70" stroke-linecap="round" transform="rotate(-90 18 18)"/>
                </svg>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-widest text-[#8892A0]">@lang('modules.module.todos.progress')</p>
                    <p class="text-[14px] font-bold text-[#1A1E2E]"><span id="raTodoDoneCount">0</span>/<span id="raTodoTotalCount">0</span> @lang('modules.module.todos.doneLabel')</p>
                </div>
            </div>
            <button type="button" id="raTodoBtnNew" class="flex items-center gap-2 text-[13px] font-semibold px-5 py-2.5 rounded-xl bg-[#2563EB] text-white hover:bg-[#1d4ed8] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                @lang('modules.module.todos.newTask')
            </button>
        </div>
    </div>

    <div class="flex flex-col gap-4 mb-4 a2 lg:flex-row lg:items-center lg:justify-between">
        <div class="tab-bar" role="tablist">
            <button type="button" class="tab on" data-tab="all">@lang('modules.module.todos.allTasks') <span class="tab-cnt" id="raCntAll">0</span></button>
            <button type="button" class="tab" data-tab="pending">@lang('modules.module.todos.pendingTab') <span class="tab-cnt" id="raCntPending">0</span></button>
            <button type="button" class="tab" data-tab="done">@lang('modules.module.todos.completedTab') <span class="tab-cnt" id="raCntDone">0</span></button>
            <button type="button" class="tab" data-tab="high">
                <span class="w-[7px] h-[7px] bg-[#E11D48] rounded-full inline-block"></span>
                @lang('modules.module.todos.highPriorityTab') <span class="tab-cnt" id="raCntHigh">0</span>
            </button>
        </div>
        <div class="t-search">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="#9CA3AF" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="search" id="raTodoSearch" placeholder="@lang('modules.module.todos.searchTasks')" autocomplete="off"/>
        </div>
    </div>

    <div class="todo-columns a3" id="raTodoColumns"></div>

    {{-- Add / Edit --}}
    <div class="modal-bg" id="raTodoModalBg">
        <div class="modal-todo" role="dialog" aria-modal="true" aria-labelledby="raTodoModalTitle" onclick="event.stopPropagation()">
            <div class="modal-hd">
                <div>
                    <h3 class="font-bold text-[15.5px] text-[#1A1E2E]" id="raTodoModalTitle">@lang('modules.module.todos.modalNewTitle')</h3>
                    <p class="text-[11.5px] mt-0.5 text-[#8892A0]" id="raTodoModalSub">@lang('modules.module.todos.modalNewSubtitle')</p>
                </div>
                <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100" id="raTodoModalClose" aria-label="@lang('app.close')">
                    <svg class="w-4 h-4" fill="none" stroke="#8892A0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="raTodoEditId" value="">
                <div>
                    <p class="fl">@lang('modules.module.todos.taskTitle') <span class="text-red-500">*</span></p>
                    <div>
                        <input
                            type="text"
                            id="raTodoTitle"
                            class="fi"
                            placeholder="@lang('modules.module.todos.form.title')"
                        />
                        <div class="flex justify-end mt-2">
                            <button
                                type="button"
                                id="raTodoAiGenerateBtn"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-[#2563EB]/25 bg-[#EFF6FF] px-2.5 py-1.5 text-[11px] font-semibold text-[#2563EB] transition hover:bg-[#DBEAFE] disabled:cursor-not-allowed disabled:opacity-50"
                                aria-label="@lang('app.aiGenerate')"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l1.912 5.813a2 2 0 001.268 1.268L21 12l-5.82 1.919a2 2 0 00-1.261 1.261L12 21l-1.919-5.82a2 2 0 00-1.261-1.261L3 12l5.813-1.919a2 2 0 001.268-1.268L12 3z"/></svg>
                                <span>@lang('app.aiGenerate')</span>
                            </button>
                        </div>
                    </div>
                    <p id="raTodoTitleErr" class="hidden text-[11.5px] text-red-500 mt-1.5">@lang('modules.module.todos.taskTitleRequired')</p>
                </div>
                <div>
                    <p class="fl">@lang('modules.module.todos.description')</p>
                    <textarea id="raTodoDesc" class="fi" rows="2" placeholder="@lang('modules.module.todos.descriptionPlaceholder')" style="resize:none;"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="fl">@lang('modules.module.todos.priority')</p>
                        <select id="raTodoPriority" class="fs">
                            <option value="low">@lang('modules.module.todos.priorityLow')</option>
                            <option value="medium" selected>@lang('modules.module.todos.priorityMedium')</option>
                            <option value="high">@lang('modules.module.todos.priorityHigh')</option>
                        </select>
                    </div>
                    <div>
                        <p class="fl">@lang('modules.module.todos.tagCategory')</p>
                        <select id="raTodoTag" class="fs">
                            <option value="">@lang('modules.module.todos.tagNone')</option>
                            <option value="Recruitment">Recruitment</option>
                            <option value="Admin">Admin</option>
                            <option value="Interview">Interview</option>
                            <option value="Review">Review</option>
                            <option value="Follow-up">Follow-up</option>
                        </select>
                    </div>
                    <div>
                        <p class="fl">@lang('modules.module.todos.dueDate')</p>
                        <input type="date" id="raTodoDue" class="fi"/>
                    </div>
                    <div>
                        <p class="fl">@lang('modules.module.todos.form.status')</p>
                        <select id="raTodoStatus" class="fs">
                            <option value="pending">@lang('modules.module.todos.pending')</option>
                            <option value="in_progress">@lang('modules.module.todos.inProgress')</option>
                            <option value="completed">@lang('modules.module.todos.completed')</option>
                        </select>
                    </div>
                </div>
<div><label class="fl" for="raTodoAssignee">Assign to</label><select id="raTodoAssignee" class="fs"><option value="">Myself</option>@foreach($teamMembers as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-c" id="raTodoModalCancel">@lang('app.cancel')</button>
                <button type="button" class="btn-s" id="raTodoModalSave">@lang('modules.module.todos.saveTask')</button>
            </div>
        </div>
    </div>

    {{-- Delete --}}
    <div class="modal-bg" id="raTodoDelBg">
        <div class="del-modal" onclick="event.stopPropagation()">
            <div class="modal-hd">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#FFF1F2]">
                        <svg width="18" height="18" fill="none" stroke="#EF4444" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-[15px] text-[#1A1E2E]">@lang('modules.module.todos.deleteTask')</h3>
                        <p class="text-[11.5px] text-[#8892A0]">@lang('modules.module.todos.deleteTaskConfirm')</p>
                    </div>
                </div>
                <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100" id="raTodoDelClose" aria-label="@lang('app.close')">
                    <svg class="w-4 h-4" fill="none" stroke="#8892A0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-[13.5px] leading-relaxed text-[#5A6478]" id="raTodoDelText"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-c" id="raTodoDelCancel">@lang('app.cancel')</button>
                <button type="button" class="btn-danger" id="raTodoDelConfirm">@lang('app.delete')</button>
            </div>
        </div>
    </div>

    <div class="toast-todo" id="raTodoToast" role="status"><span class="tdot" id="raTodoToastDot"></span><span id="raTodoToastMsg"></span></div>
</div>
@endsection

@push('footer-script')
<script>
(function () {
    const CSRF = '{{ csrf_token() }}';
    const TODO_BASE = @json(rtrim(url('/admin/todo-items'), '/'));
    const ROUTES = {
        store: @json(route('admin.todo-items.store')),
        moveColumn: @json(route('admin.todo-items.move-column')),
        toggleComplete: @json(route('admin.todo-items.toggle-complete')),
        boardData: @json(route('admin.todo-items.board-data')),
        aiGenerateDescription: @json(route('admin.todo-items.ai-generate-description')),
    };
    function todoItemUrl(id) {
        return TODO_BASE + '/' + id;
    }
    const STR = {
        moved: @json(__('modules.module.todos.taskMoved')),
        completed: @json(__('modules.module.todos.taskCompleted')),
        reopened: @json(__('modules.module.todos.taskReopened')),
        deleted: @json(__('modules.module.todos.taskDeleted')),
        deleteQ: @json(__('modules.module.todos.deleteTaskQuestion')),
        editTitle: @json(__('modules.module.todos.modalEditTitle')),
        newTitle: @json(__('modules.module.todos.modalNewTitle')),
        newSub: @json(__('modules.module.todos.modalNewSubtitle')),
    };
    const COL_CONFIG = [
        { id: 'pending', label: @json(__('modules.module.todos.pending')), badge_bg: '#0F1F3D', bar: '#0F1F3D' },
        { id: 'in_progress', label: @json(__('modules.module.todos.inProgress')), badge_bg: '#F97316', bar: '#F97316' },
        { id: 'completed', label: @json(__('modules.module.todos.completed')), badge_bg: '#059669', bar: '#059669' },
    ];
    const TAG_COLORS = {
        Recruitment: { bg: '#EFF6FF', tc: '#1D4ED8' },
        Admin: { bg: '#F5F3FF', tc: '#5B21B6' },
        Interview: { bg: '#FFF7ED', tc: '#C2410C' },
        Review: { bg: '#ECFDF5', tc: '#065F46' },
        'Follow-up': { bg: '#FFF1F2', tc: '#9F1239' },
    };

    let tasks = @json($boardTodos ?? []);
    let editId = null;
    let delId = null;
    let activeTab = 'all';

    function getFiltered() {
        const q = (document.getElementById('raTodoSearch')?.value || '').toLowerCase();
        return tasks.filter(t => {
            if (q && !String(t.title).toLowerCase().includes(q) && !String(t.desc || '').toLowerCase().includes(q)) return false;
            if (activeTab === 'pending') return t.status === 'pending';
            if (activeTab === 'done') return t.status === 'completed';
            if (activeTab === 'high') return t.priority === 'high';
            return true;
        });
    }

    function showToast(msg, color) {
        const t = document.getElementById('raTodoToast');
        document.getElementById('raTodoToastMsg').textContent = msg;
        document.getElementById('raTodoToastDot').style.background = color;
        t.classList.add('show');
        clearTimeout(window._raTodoTt);
        window._raTodoTt = setTimeout(() => t.classList.remove('show'), 2600);
    }

    function updateProgress() {
        const done = tasks.filter(t => t.status === 'completed').length;
        const total = tasks.length || 1;
        document.getElementById('raTodoDoneCount').textContent = tasks.filter(t => t.status === 'completed').length;
        document.getElementById('raTodoTotalCount').textContent = String(tasks.length);
        const circ = document.getElementById('raTodoProgressCircle');
        const offset = 94.2 - (done / total) * 94.2;
        circ.style.strokeDashoffset = offset;
        circ.style.stroke = done === tasks.length && tasks.length > 0 ? '#059669' : '#2563EB';
    }

    function renderColumns() {
        const filtered = getFiltered();
        const container = document.getElementById('raTodoColumns');
        container.innerHTML = '';

        document.getElementById('raCntAll').textContent = String(tasks.length);
        document.getElementById('raCntPending').textContent = String(tasks.filter(t => t.status === 'pending').length);
        document.getElementById('raCntDone').textContent = String(tasks.filter(t => t.status === 'completed').length);
        document.getElementById('raCntHigh').textContent = String(tasks.filter(t => t.priority === 'high').length);
        updateProgress();

        COL_CONFIG.forEach(col => {
            const colTasks = filtered.filter(t => t.status === col.id);
            const el = document.createElement('div');
            el.className = 'todo-col';
            el.dataset.status = col.id;

            el.innerHTML = `
                <div class="col-hd">
                    <div class="col-title">
                        <span class="col-name">${col.label}</span>
                        <span class="col-badge" style="background:${col.badge_bg};">${colTasks.length}</span>
                    </div>
                    <button type="button" class="ra-col-add w-7 h-7 rounded-lg flex items-center justify-center hover:bg-white/60 transition-colors text-[#8892A0]" data-status="${col.id}" aria-label="@lang('app.add')">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <div class="col-body" data-col-body="${col.id}"></div>
                <button type="button" class="col-add-btn ra-col-add" data-status="${col.id}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    @lang('modules.module.todos.addTask')
                </button>`;

            container.appendChild(el);
            const body = el.querySelector('[data-col-body]');

            if (!colTasks.length) {
                body.innerHTML = `<div class="text-center py-8">
                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center mx-auto mb-2 bg-white/70">
                        <svg class="w-5 h-5" fill="none" stroke="#C4CBD4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <p class="text-[12px] text-[#B0B8C4]">@lang('modules.module.todos.noTasksHere')</p>
                </div>`;
            } else {
                colTasks.forEach(task => {
                    const isDone = task.status === 'completed';
                    const tc = TAG_COLORS[task.tag] || { bg: '#F1F3F7', tc: '#5A6478' };
                    const prioClass = task.priority === 'high' ? 'p-high' : task.priority === 'medium' ? 'p-medium' : 'p-low';
                    const prioLabel = task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : '';
                    let dueStr = '';
                    if (task.due) {
                        const d = new Date(task.due + 'T12:00:00');
                        dueStr = d.toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
                    }
                    const isOverdue = task.due && new Date(task.due + 'T12:00:00') < new Date(new Date().toDateString()) && !isDone;

                    const card = document.createElement('div');
                    card.className = 'todo-card' + (isDone ? ' done' : '');
                    card.dataset.id = String(task.id);
                    card.draggable = true;

                    card.innerHTML = `
                        <div class="card-bar" style="background:${col.bar};"></div>
                        <div class="card-body">
                            <div class="card-top">
                                <span class="card-title${isDone ? ' done-text' : ''}">${escapeHtml(task.title)}</span>
                                <div class="card-check${isDone ? ' checked' : ''}" data-check="${task.id}" role="checkbox" aria-checked="${isDone}">
                                    ${isDone ? '<svg class="w-3 h-3" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>' : ''}
                                </div>
                            </div>
                            ${task.desc ? `<div class="card-desc">${escapeHtml(task.desc)}</div>` : ''}
                            <div class="card-foot">
                                <span style="font-size:11px;color:#64748b">Assigned to: ${escapeHtml(task.assignee || 'Myself')}</span><span class="priority-pill ${prioClass}">${escapeHtml(prioLabel)}</span>
                                <div class="flex items-center gap-2 flex-wrap">
                                    ${task.tag ? `<span class="card-tag" style="background:${tc.bg};color:${tc.tc};">${escapeHtml(task.tag)}</span>` : ''}
                                    ${dueStr ? `<span class="card-date" style="${isOverdue ? 'color:#EF4444;' : ''}">${isOverdue ? '⚠️ ' : ''}<svg style="width:10px;height:10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>${escapeHtml(dueStr)}</span>` : ''}
                                </div>
                            </div>
                            <div class="card-actions">
                                <button type="button" class="ra-card-btn ra-card-btn--edit ra-edit" data-id="${task.id}">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    @lang('app.edit')
                                </button>
                                <button type="button" class="ra-card-btn ra-card-btn--del ra-del" data-id="${task.id}">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    @lang('app.delete')
                                </button>
                            </div>
                        </div>`;

                    card.querySelector('[data-check]')?.addEventListener('click', function (e) {
                        e.stopPropagation();
                        toggleDone(task.id);
                    });
                    card.querySelector('.ra-edit')?.addEventListener('click', function (e) {
                        e.stopPropagation();
                        openEdit(task.id);
                    });
                    card.querySelector('.ra-del')?.addEventListener('click', function (e) {
                        e.stopPropagation();
                        openDel(task.id);
                    });
                    card.addEventListener('click', function (e) {
                        if (e.target.closest('[data-check], .ra-edit, .ra-del')) return;
                        openEdit(task.id);
                    });
                    card.addEventListener('dragstart', e => {
                        e.dataTransfer.setData('taskId', String(task.id));
                        card.classList.add('dragging');
                    });
                    card.addEventListener('dragend', () => card.classList.remove('dragging'));
                    body.appendChild(card);
                });
            }

            el.addEventListener('dragover', e => { e.preventDefault(); el.classList.add('drag-over'); });
            el.addEventListener('dragleave', () => el.classList.remove('drag-over'));
            el.addEventListener('drop', e => {
                e.preventDefault();
                el.classList.remove('drag-over');
                const id = parseInt(e.dataTransfer.getData('taskId'), 10);
                const t = tasks.find(x => x.id === id);
                if (t && t.status !== col.id) {
                    moveTaskToColumn(id, col.id);
                }
            });
        });

        container.querySelectorAll('.ra-col-add').forEach(btn => {
            btn.addEventListener('click', () => openModalForStatus(btn.dataset.status));
        });
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function syncTaskFromServer(todo) {
        const i = tasks.findIndex(x => x.id === todo.id);
        if (i >= 0) tasks[i] = todo;
        else tasks.push(todo);
        renderColumns();
    }

    function removeTask(id) {
        tasks = tasks.filter(x => x.id !== id);
        renderColumns();
    }

    function moveTaskToColumn(id, status) {
        $.easyAjax({
            url: ROUTES.moveColumn,
            type: 'POST',
            data: { _token: CSRF, id: id, status: status },
            success: function () {
                const t = tasks.find(x => x.id === id);
                if (t) t.status = status;
                renderColumns();
                showToast(STR.moved, '#059669');
            },
        });
    }

    function toggleDone(id) {
        $.easyAjax({
            url: ROUTES.toggleComplete,
            type: 'POST',
            data: { _token: CSRF, id: id },
            success: function () {
                const t = tasks.find(x => x.id === id);
                if (t) {
                    t.status = t.status === 'completed' ? 'pending' : 'completed';
                    renderColumns();
                    showToast(t.status === 'completed' ? STR.completed : STR.reopened, t.status === 'completed' ? '#059669' : '#F97316');
                }
            },
        });
    }

    function openModal(reset) {
        document.getElementById('raTodoTitleErr').classList.add('hidden');
        document.getElementById('raTodoModalBg').classList.add('show');
        if (reset) setTimeout(() => document.getElementById('raTodoTitle').focus(), 80);
    }

    function closeModal() {
        document.getElementById('raTodoModalBg').classList.remove('show');
    }

    function openModalForStatus(status) {
        editId = null;
        document.getElementById('raTodoModalTitle').textContent = STR.newTitle;
        document.getElementById('raTodoModalSub').textContent = STR.newSub;
        document.getElementById('raTodoEditId').value = '';
        document.getElementById('raTodoTitle').value = '';
        document.getElementById('raTodoDesc').value = '';
        document.getElementById('raTodoPriority').value = 'medium';
        document.getElementById('raTodoTag').value = '';
        document.getElementById('raTodoDue').value = '';
        document.getElementById('raTodoStatus').value = status || 'pending';
        document.getElementById('raTodoAssignee').value = '';
        openModal(true);
    }

    function openEdit(id) {
        const t = tasks.find(x => x.id === id);
        if (!t) return;
        editId = id;
        document.getElementById('raTodoModalTitle').textContent = STR.editTitle;
        document.getElementById('raTodoModalSub').textContent = '';
        document.getElementById('raTodoEditId').value = String(id);
        document.getElementById('raTodoTitle').value = t.title;
        document.getElementById('raTodoDesc').value = t.desc || '';
        document.getElementById('raTodoPriority').value = t.priority || 'medium';
        document.getElementById('raTodoTag').value = t.tag || '';
        document.getElementById('raTodoDue').value = t.due || '';
        document.getElementById('raTodoStatus').value = t.status;
        document.getElementById('raTodoAssignee').value = t.assigned_to || '';
        openModal(true);
    }

    function saveTask() {
        const title = document.getElementById('raTodoTitle').value.trim();
        if (!title) {
            document.getElementById('raTodoTitleErr').classList.remove('hidden');
            return;
        }
        document.getElementById('raTodoTitleErr').classList.add('hidden');

        const payload = {
            _token: CSRF,
            title: title,
            description: document.getElementById('raTodoDesc').value.trim(),
            priority: document.getElementById('raTodoPriority').value,
            tag: document.getElementById('raTodoTag').value,
            due_date: document.getElementById('raTodoDue').value,
            status: document.getElementById('raTodoStatus').value,
            assigned_to: document.getElementById('raTodoAssignee').value,
        };

        if (editId) {
            payload._method = 'PUT';
            $.easyAjax({
                url: todoItemUrl(editId),
                type: 'POST',
                data: payload,
                success: function (res) {
                    if (res.status === 'success' && res.todo) {
                        syncTaskFromServer(res.todo);
                        closeModal();
                    }
                },
            });
        } else {
            $.easyAjax({
                url: ROUTES.store,
                type: 'POST',
                data: payload,
                success: function (res) {
                    if (res.status === 'success' && res.todo) {
                        syncTaskFromServer(res.todo);
                        closeModal();
                    }
                },
            });
        }
    }

    function openDel(id) {
        delId = id;
        const t = tasks.find(x => x.id === id);
        const name = t ? '"' + String(t.title) + '"' : '';
        document.getElementById('raTodoDelText').textContent = STR.deleteQ.replace(':name', name);
        document.getElementById('raTodoDelBg').classList.add('show');
    }

    function closeDel() {
        document.getElementById('raTodoDelBg').classList.remove('show');
    }

    function confirmDel() {
        if (!delId) return;
        $.easyAjax({
            url: todoItemUrl(delId),
            type: 'POST',
            data: { _token: CSRF, _method: 'DELETE' },
            success: function () {
                removeTask(delId);
                closeDel();
                showToast(STR.deleted, '#EF4444');
            },
        });
    }

    document.getElementById('raTodoBtnNew')?.addEventListener('click', () => openModalForStatus('pending'));
    document.getElementById('raTodoModalBg')?.addEventListener('click', e => { if (e.target.id === 'raTodoModalBg') closeModal(); });
    document.getElementById('raTodoModalClose')?.addEventListener('click', closeModal);
    document.getElementById('raTodoModalCancel')?.addEventListener('click', closeModal);
    document.getElementById('raTodoModalSave')?.addEventListener('click', saveTask);
    document.getElementById('raTodoDelBg')?.addEventListener('click', e => { if (e.target.id === 'raTodoDelBg') closeDel(); });
    document.getElementById('raTodoDelClose')?.addEventListener('click', closeDel);
    document.getElementById('raTodoDelCancel')?.addEventListener('click', closeDel);
    document.getElementById('raTodoDelConfirm')?.addEventListener('click', confirmDel);

    document.getElementById('raTodoAiGenerateBtn')?.addEventListener('click', function () {
        const titleEl = document.getElementById('raTodoTitle');
        const descEl = document.getElementById('raTodoDesc');
        const errEl = document.getElementById('raTodoTitleErr');
        const btnEl = document.getElementById('raTodoAiGenerateBtn');

        const title = (titleEl?.value || '').trim();
        if (!title) {
            errEl?.classList.remove('hidden');
            titleEl?.focus();
            showToast(@json(__('modules.module.todos.taskTitleRequired')), '#EF4444');
            return;
        }
        errEl?.classList.add('hidden');

        btnEl.disabled = true;
        showToast(@json(__('app.aiGenerate')), '#2563EB');

        $.easyAjax({
            url: ROUTES.aiGenerateDescription,
            type: 'POST',
            data: { _token: CSRF, title: title },
            success: function (res) {
                btnEl.disabled = false;
                if (res && res.status === 'success') {
                    descEl.value = res.description || '';
                    showToast('Description generated', '#059669');
                } else {
                    showToast(res && res.message ? res.message : 'AI generation failed', '#EF4444');
                }
            },
            error: function () {
                btnEl.disabled = false;
                showToast('AI generation failed', '#EF4444');
            }
        });
    });

    document.querySelectorAll('.ra-todos-board .tab-bar .tab').forEach(btn => {
        btn.addEventListener('click', () => {
            activeTab = btn.dataset.tab;
            document.querySelectorAll('.ra-todos-board .tab-bar .tab').forEach(b => b.classList.remove('on'));
            btn.classList.add('on');
            renderColumns();
        });
    });

    document.getElementById('raTodoSearch')?.addEventListener('input', () => renderColumns());

    renderColumns();
})();
</script>
@endpush
