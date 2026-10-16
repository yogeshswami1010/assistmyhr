<div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
    <h4 class="text-lg font-semibold text-gray-900">@lang('modules.module.todos.editTodoItem')</h4>
    <button type="button" class="text-gray-400 hover:text-gray-600 focus:outline-none" onclick="$('#application-md-modal').addClass('hidden')">
        <i class="fa fa-times"></i>
    </button>
</div>
<div class="p-6">
    <form id="editTodoItem" class="ajax-form" method="POST" autocomplete="off">
        @csrf
        @method('PUT')
        <div class="space-y-4">
@include('admin.todo-list.assignee-field')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">@lang('modules.module.todos.form.title')</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary" id="title" name="title" value="{{ $todoItem->title }}">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">@lang('modules.module.todos.description')</label>
                <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary text-sm" placeholder="@lang('modules.module.todos.descriptionPlaceholder')">{{ $todoItem->description }}</textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">@lang('modules.module.todos.priority')</label>
                    <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm">
                        <option @if (($todoItem->priority ?? 'medium') == 'low') selected @endif value="low">@lang('modules.module.todos.priorityLow')</option>
                        <option @if (($todoItem->priority ?? 'medium') == 'medium') selected @endif value="medium">@lang('modules.module.todos.priorityMedium')</option>
                        <option @if (($todoItem->priority ?? 'medium') == 'high') selected @endif value="high">@lang('modules.module.todos.priorityHigh')</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">@lang('modules.module.todos.tagCategory')</label>
                    <select name="tag" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">@lang('modules.module.todos.tagNone')</option>
                        <option @if (($todoItem->tag ?? '') == 'Recruitment') selected @endif value="Recruitment">Recruitment</option>
                        <option @if (($todoItem->tag ?? '') == 'Admin') selected @endif value="Admin">Admin</option>
                        <option @if (($todoItem->tag ?? '') == 'Interview') selected @endif value="Interview">Interview</option>
                        <option @if (($todoItem->tag ?? '') == 'Review') selected @endif value="Review">Review</option>
                        <option @if (($todoItem->tag ?? '') == 'Follow-up') selected @endif value="Follow-up">Follow-up</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">@lang('modules.module.todos.dueDate')</label>
                    <input type="date" name="due_date" value="{{ $todoItem->due_date ? $todoItem->due_date->format('Y-m-d') : '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">@lang('modules.module.todos.form.status')</label>
                    <select id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary" name="status">
                        <option @if ($todoItem->status == 'pending') selected @endif value="pending">@lang('modules.module.todos.pending')</option>
                        <option @if ($todoItem->status == 'in_progress') selected @endif value="in_progress">@lang('modules.module.todos.inProgress')</option>
                        <option @if ($todoItem->status == 'completed') selected @endif value="completed">@lang('modules.module.todos.completed')</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end space-x-3 mt-6">
            <button type="button" id="update-todo-item" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2" data-id="{{ $todoItem->id }}">
                @lang('app.update')
                <i class="fa fa-arrow-right"></i>
            </button>
        </div>
    </form>
</div>
