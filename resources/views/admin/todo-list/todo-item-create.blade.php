<div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
    <h4 class="text-lg font-semibold text-gray-900">@lang('modules.module.todos.createTodoItem')</h4>
</div>
<div class="p-6">
    <form id="createTodoItem" class="ajax-form" method="POST" autocomplete="off">
        @csrf
        <div class="space-y-4">
@include('admin.todo-list.assignee-field')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1 required">@lang('modules.module.todos.form.title')</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary" id="title" name="title">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">@lang('modules.module.todos.description')</label>
                <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary text-sm" placeholder="@lang('modules.module.todos.descriptionPlaceholder')"></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">@lang('modules.module.todos.priority')</label>
                    <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="low">@lang('modules.module.todos.priorityLow')</option>
                        <option value="medium" selected>@lang('modules.module.todos.priorityMedium')</option>
                        <option value="high">@lang('modules.module.todos.priorityHigh')</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">@lang('modules.module.todos.tagCategory')</label>
                    <select name="tag" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">@lang('modules.module.todos.tagNone')</option>
                        <option value="Recruitment">Recruitment</option>
                        <option value="Admin">Admin</option>
                        <option value="Interview">Interview</option>
                        <option value="Review">Review</option>
                        <option value="Follow-up">Follow-up</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">@lang('modules.module.todos.dueDate')</label>
                    <input type="date" name="due_date" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm">
                </div>
            </div>
        </div>
    </form>
</div>
