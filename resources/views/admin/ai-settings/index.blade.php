@extends('layouts.app')

@push('head-script')
    @include('admin.partials.settings-design-styles')
    <style>
        #modal-ai-edit { display: none; }
        #modal-ai-edit.is-open { display: flex; align-items: center; justify-content: center; }
    </style>
@endpush

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-tight text-[#1A1E2E]">@lang('menu.aiSettings')</span>
@endsection

@section('create-button')
    <a href="{{ route('admin.ai-settings.usage') }}"
       class="inline-flex items-center gap-2 rounded-xl bg-[#2563EB] px-5 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
        @lang('app.viewUsage')
    </a>
@endsection

@section('content')
    <div class="mx-auto pb-10">
        <div id="alert"></div>

        <div class="bs-set-card mb-6">
            <div class="bs-set-card-hd">
                <div class="bs-set-card-ic">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="#2563EB" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('app.aiAddKey')</h2>
                    <p class="text-[12px] text-[#8892A0]">@lang('app.aiSettingsIntro')</p>
                </div>
            </div>
            <div class="p-6">
                <form id="form-add-ai-key" class="ajax-form space-y-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="add_name" class="bs-set-lbl">@lang('app.name')</label>
                            <input type="text" name="name" id="add_name" class="bs-f-input" required maxlength="191" placeholder="@lang('app.aiKeyNamePlaceholder')">
                        </div>
                        <div>
                            <label for="add_provider" class="bs-set-lbl">@lang('app.aiProvider')</label>
                            <input type="text" name="provider" id="add_provider" class="bs-f-input" maxlength="191" placeholder="deepseek, openai, anthropic, …">
                        </div>
                    </div>
                    <div>
                        <label for="add_model" class="bs-set-lbl">Model</label>
                        <input type="text" name="model" id="add_model" class="bs-f-input" maxlength="191" placeholder="deepseek-chat">
                        <p class="text-xs text-gray-500">For DeepSeek, enter your model name. Blank uses deepseek-chat.</p>
                    </div>
                    <div>
                        <label for="add_api_key" class="bs-set-lbl">@lang('app.aiApiKey')</label>
                        <input type="password" name="api_key" id="add_api_key" class="bs-f-input" required autocomplete="off" placeholder="@lang('app.aiKeyPlaceholder')">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="add_sort_order" class="bs-set-lbl">@lang('app.aiSortOrder')</label>
                            <input type="number" name="sort_order" id="add_sort_order" class="bs-f-input" value="0" min="0">
                        </div>
                        <div>
                            <label for="add_is_active" class="bs-set-lbl">@lang('app.status')</label>
                            <select name="is_active" id="add_is_active" class="bs-f-input">
                                <option value="1" selected>@lang('app.active')</option>
                                <option value="0">@lang('app.inactive')</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <button type="button" id="btn-add-ai-key" class="inline-flex items-center justify-center gap-2 rounded-[11px] bg-[#2563EB] px-8 py-3 text-[13.5px] font-bold text-white shadow-sm transition hover:bg-[#1d4ed8]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            @lang('app.save')
                        </button>
                        <button type="button" id="btn-test-ai-key" class="inline-flex items-center justify-center gap-2 rounded-[10px] border border-[#CBD5E1] bg-white px-8 py-3 text-[12.5px] font-semibold text-[#1E293B] transition hover:bg-[#F8FAFC]">
                            @lang('app.test')
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="bs-set-card overflow-hidden">
            <div class="bs-set-card-hd">
                <div class="bs-set-card-ic" style="background:#F0FDF4;">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="#059669" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                </div>
                <div>
                    <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('app.aiConfiguredKeys')</h2>
                    <p class="text-[12px] text-[#8892A0]">@lang('app.aiConfiguredKeysHint')</p>
                </div>
            </div>

            @if($aiKeys->isEmpty())
                <p class="px-6 py-10 text-center text-[13.5px] text-[#5A6478]">
                    @lang('app.aiNoKeysYet')
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="jc-cat-table min-w-[720px] w-full">
                        <thead>
                            <tr>
                                <th>@lang('app.name')</th>
                                <th>@lang('app.aiProvider')</th>
                                <th>@lang('app.aiApiKey')</th>
                                <th>@lang('app.status')</th>
                                <th class="jc-th-right">@lang('app.aiSortOrder')</th>
                                <th class="jc-th-right">@lang('app.action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($aiKeys as $key)
                                <tr class="group">
                                    <td class="font-semibold text-[#1A1E2E]">{{ $key->name }}</td>
                                    <td class="text-[#3D4A5C]">{{ $key->provider ?: '—' }}</td>
                                    <td>
                                        <div class="inline-flex max-w-full items-center gap-2">
                                            <span class="font-mono text-[12.5px] text-[#5A6478]">{{ $key->api_key_masked }}</span>
                                            <button
                                                type="button"
                                                class="ai-key-copy inline-flex shrink-0 rounded-md p-1 text-[#94A3B8] transition hover:bg-[#EFF6FF] hover:text-[#2563EB]"
                                                data-copy-url="{{ route('admin.ai-settings.clipboard-key', $key) }}"
                                                title="@lang('app.copyApiKey')"
                                                aria-label="@lang('app.copyApiKey')"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <label class="inline-flex cursor-pointer items-center gap-3" title="@lang('app.active') / @lang('app.inactive')">
                                            <input
                                                type="checkbox"
                                                class="peer sr-only ai-key-toggle-active"
                                                data-toggle-url="{{ route('admin.ai-settings.toggle-active', $key) }}"
                                                aria-label="{{ $key->is_active ? __('app.active') : __('app.inactive') }}"
                                                @if($key->is_active) checked @endif
                                            >
                                            <span class="bs-toggle-track" aria-hidden="true">
                                                <span class="bs-toggle-thumb"></span>
                                            </span>
                                            <span class="ai-key-status-label text-[12px] font-semibold {{ $key->is_active ? 'text-emerald-700' : 'text-gray-600' }}">
                                                {{ $key->is_active ? __('app.active') : __('app.inactive') }}
                                            </span>
                                        </label>
                                    </td>
                                    <td class="jc-td-right text-[#3D4A5C]">{{ $key->sort_order }}</td>
                                    <td class="jc-td-right">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <button type="button"
                                                class="ai-key-edit inline-flex items-center gap-1.5 rounded-lg border border-[#E2DED8] bg-white px-3 py-1.5 text-[12.5px] font-semibold text-[#2563EB] transition hover:bg-[#EFF6FF]"
                                                data-url="{{ route('admin.ai-settings.update', $key) }}"
                                                data-name="{{ e($key->name) }}"
                                                data-provider="{{ e($key->provider ?? '') }}"
                                                data-sort-order="{{ $key->sort_order }}" data-model="{{ $key->model }}"
                                                data-is-active="{{ $key->is_active ? '1' : '0' }}"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                @lang('app.edit')
                                            </button>
                                            <button type="button"
                                                class="ai-key-delete inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-[12.5px] font-semibold text-red-700 transition hover:bg-red-100"
                                                data-delete-url="{{ route('admin.ai-settings.destroy', $key) }}"
                                            >
                                                @lang('app.delete')
                                            </button>
                                            <button
                                                type="button"
                                                class="ai-key-test inline-flex items-center justify-center gap-1.5 rounded-[10px] border border-green-200 bg-green-50 px-3 py-1.5 text-[12.5px] font-semibold text-[#1E293B] transition hover:bg-[#F8FAFC]"
                                                data-provider="{{ e($key->provider ?? 'openai') }}"
                                                data-copy-url="{{ route('admin.ai-settings.clipboard-key', $key) }}"
                                            >
                                                @lang('app.test')
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Edit modal --}}
    <div id="modal-ai-edit" class="fixed inset-0 z-[100] p-4" aria-hidden="true" role="dialog" aria-labelledby="modal-ai-edit-title">
        <div class="ai-modal-backdrop absolute inset-0 bg-black/50" aria-hidden="true"></div>
        <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-[#F0EEE9] px-6 py-4">
                <h3 id="modal-ai-edit-title" class="text-[16px] font-bold text-[#1A1E2E]">@lang('app.aiEditKeyModalTitle')</h3>
                <button type="button" class="rounded-lg p-2 text-[#8892A0] hover:bg-gray-100" data-ai-modal-close aria-label="@lang('app.close')">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="form-edit-ai-key-modal" class="ajax-form space-y-4 p-6">
                @csrf
                @method('PUT')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="edit_name" class="bs-set-lbl">@lang('app.name')</label>
                        <input type="text" name="name" id="edit_name" class="bs-f-input" required maxlength="191">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="edit_provider" class="bs-set-lbl">@lang('app.aiProvider')</label>
                        <input type="text" name="provider" id="edit_provider" class="bs-f-input" maxlength="191">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="edit_model" class="bs-set-lbl">Model</label>
                        <input type="text" name="model" id="edit_model" class="bs-f-input" maxlength="191" placeholder="deepseek-chat">
                    </div>
                    <div>
                        <label for="edit_api_key" class="bs-set-lbl">@lang('app.aiApiKey')</label>
                        <input type="password" name="api_key" id="edit_api_key" class="bs-f-input" autocomplete="off" placeholder="@lang('app.aiKeyHintKeep')">
                    </div>
                    <div>
                        <label for="edit_sort_order" class="bs-set-lbl">@lang('app.aiSortOrder')</label>
                        <input type="number" name="sort_order" id="edit_sort_order" class="bs-f-input" min="0">
                    </div>
                    <div>
                        <label for="edit_is_active" class="bs-set-lbl">@lang('app.status')</label>
                        <select name="is_active" id="edit_is_active" class="bs-f-input">
                            <option value="1">@lang('app.active')</option>
                            <option value="0">@lang('app.inactive')</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-[#F0EEE9] pt-4">
                    <button type="button" class="rounded-[11px] border border-[#E2DED8] bg-[#F1F3F7] px-6 py-2.5 text-[13px] font-semibold text-[#5A6478] hover:bg-[#E8EBEF]" data-ai-modal-close>@lang('app.cancel')</button>
                    <button type="button" id="btn-save-ai-modal" class="inline-flex items-center justify-center gap-2 rounded-[11px] bg-[#2563EB] px-6 py-2.5 text-[13px] font-bold text-white shadow-sm hover:bg-[#1d4ed8]">
                        @lang('app.update')
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('footer-script')
    <script>
        (function () {
            var $modal = $('#modal-ai-edit');
            var updateUrl = '';

            function openModal() {
                $modal.addClass('is-open').attr('aria-hidden', 'false');
            }

            function closeModal() {
                $modal.removeClass('is-open').attr('aria-hidden', 'true');
                updateUrl = '';
                $('#form-edit-ai-key-modal')[0].reset();
                $('#edit_is_active').val('1');
            }

            $(document).on('click', '.ai-modal-backdrop', function () {
                closeModal();
            });
            $(document).on('click', '[data-ai-modal-close]', function () {
                closeModal();
            });

            $(document).on('click', '.ai-key-edit', function () {
                var $btn = $(this);
                updateUrl = $btn.data('url');
                $('#edit_name').val($btn.data('name') || '');
                $('#edit_provider').val($btn.data('provider') || '');
                $('#edit_model').val($btn.attr('data-model') || '');
                $('#edit_sort_order').val($btn.attr('data-sort-order'));
                $('#edit_api_key').val('');
                $('#edit_is_active').val(String($btn.attr('data-is-active')) === '1' ? '1' : '0');
                openModal();
            });

            $('#btn-save-ai-modal').on('click', function () {
                if (!updateUrl) {
                    return;
                }
                $.easyAjax({
                    url: updateUrl,
                    container: '#form-edit-ai-key-modal',
                    type: 'POST',
                    redirect: true,
                    messagePosition: 'inline',
                    data: $('#form-edit-ai-key-modal').serialize(),
                });
            });

            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' && $modal.hasClass('is-open')) {
                    closeModal();
                }
            });
        })();

        $('#btn-add-ai-key').on('click', function () {
            $.easyAjax({
                url: '{{ route('admin.ai-settings.store') }}',
                container: '#form-add-ai-key',
                type: 'POST',
                redirect: true,
                messagePosition: 'inline',
                data: $('#form-add-ai-key').serialize(),
            });
        });

        function testAiKey($button, providerValue, keyValue) {
            var provider = String(providerValue || '').trim();
            var apiKey = String(keyValue || '').trim();
            if (apiKey === '') {
                $.toast({
                    text: @json(__('errors.enterTheValue')),
                    position: 'top-right',
                    loaderBg: '#ff6849',
                    icon: 'error',
                    hideAfter: 3500,
                });
                return;
            }

            var defaultLabel = $button.data('default-label') || $button.text().trim();
            $button.data('default-label', defaultLabel);
            $button.prop('disabled', true).text('Testing...');

            $.ajax({
                url: '{{ route('admin.ai-settings.test-key') }}',
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    provider: provider,
                    api_key: apiKey
                },
                complete: function () {
                    $button.prop('disabled', false).text(defaultLabel);
                },
                success: function (response) {
                    var ok = response && response.status === 'success';
                    $.toast({
                        text: (response && response.message) ? response.message : (ok ? 'API key is valid and working.' : 'API key test failed.'),
                        position: 'top-right',
                        loaderBg: '#ff6849',
                        icon: ok ? 'success' : 'error',
                        hideAfter: 3500,
                    });
                },
                error: function (xhr) {
                    var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'API key test failed.';
                    $.toast({
                        text: message,
                        position: 'top-right',
                        loaderBg: '#ff6849',
                        icon: 'error',
                        hideAfter: 3500,
                    });
                }
            });
        }

        $('#btn-test-ai-key').on('click', function () {
            testAiKey($(this), $('#add_provider').val(), $('#add_api_key').val());
        });

        $('#btn-test-ai-key-modal').on('click', function () {
            testAiKey($(this), $('#edit_provider').val(), $('#edit_api_key').val());
        });

        $(document).on('click', '.ai-key-test', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var provider = String($btn.data('provider') || '').trim();
            var copyUrl = $btn.data('copy-url');
            if (!copyUrl) {
                return;
            }

            var defaultLabel = $btn.data('default-label') || $btn.text().trim();
            $btn.data('default-label', defaultLabel);
            $btn.prop('disabled', true).text('Testing...');

            $.ajax({
                url: copyUrl,
                type: 'POST',
                dataType: 'json',
                data: { _token: '{{ csrf_token() }}' },
                success: function (response) {
                    if (response.status === 'fail' || !response.api_key) {
                        var failMsg = (response && response.message) ? response.message : 'Unable to read API key.';
                        $.toast({
                            text: failMsg,
                            position: 'top-right',
                            loaderBg: '#ff6849',
                            icon: 'error',
                            hideAfter: 3500,
                        });
                        return;
                    }

                    testAiKey($btn, provider, response.api_key);
                },
                error: function () {
                    $.toast({
                        text: @json(__('messages.apiKeyCopyFailed')),
                        position: 'top-right',
                        loaderBg: '#ff6849',
                        icon: 'error',
                        hideAfter: 3500,
                    });
                },
                complete: function () {
                    // testAiKey controls the button state during actual test call.
                    if (!$btn.prop('disabled')) {
                        $btn.text(defaultLabel);
                    } else {
                        $btn.prop('disabled', false).text(defaultLabel);
                    }
                }
            });
        });

        $(document).on('click', '.ai-key-delete', function () {
            if (!confirm('@lang('errors.areYouSure')')) {
                return;
            }
            $.easyAjax({
                url: $(this).data('delete-url'),
                type: 'POST',
                redirect: true,
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
            });
        });

        function copyAiKeyFallback(text, done) {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            try {
                document.execCommand('copy');
                if (typeof done === 'function') {
                    done();
                }
            } catch (err) {
                // ignore
            }
            $temp.remove();
        }

        $(document).on('click', '.ai-key-copy', function (e) {
            e.preventDefault();
            var url = $(this).data('copy-url');
            if (!url) {
                return;
            }
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: { _token: '{{ csrf_token() }}' },
                success: function (response) {
                    if (response.status === 'fail' || !response.api_key) {
                        if (response.message) {
                            $.toast({
                                text: response.message,
                                position: 'top-right',
                                loaderBg: '#ff6849',
                                icon: 'error',
                                hideAfter: 3500,
                            });
                        }
                        return;
                    }
                    var text = String(response.api_key);
                    var toastMsg = response.message || @json(__('messages.apiKeyCopiedToClipboard'));
                    function showToast() {
                        $.toast({
                            text: toastMsg,
                            position: 'top-right',
                            loaderBg: '#ff6849',
                            icon: 'success',
                            hideAfter: 3500,
                        });
                    }
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(showToast).catch(function () {
                            copyAiKeyFallback(text, showToast);
                        });
                    } else {
                        copyAiKeyFallback(text, showToast);
                    }
                },
                error: function () {
                    $.toast({
                        text: @json(__('messages.apiKeyCopyFailed')),
                        position: 'top-right',
                        loaderBg: '#ff6849',
                        icon: 'error',
                        hideAfter: 3500,
                    });
                }
            });
        });

        $(document).on('change', '.ai-key-toggle-active', function () {
            var $cb = $(this);
            var checked = $cb.is(':checked');
            var $row = $cb.closest('tr');
            var $editBtn = $row.find('.ai-key-edit');
            var $label = $row.find('.ai-key-status-label');
            var activeText = @json(__('app.active'));
            var inactiveText = @json(__('app.inactive'));

            $.easyAjax({
                url: $cb.data('toggle-url'),
                type: 'POST',
                redirect: false,
                blockUI: true,
                data: {
                    _token: '{{ csrf_token() }}',
                    is_active: checked ? 1 : 0
                },
                success: function (response) {
                    if (response.status === 'fail') {
                        $cb.prop('checked', !checked);
                        return;
                    }
                    if (response.status === 'success') {
                        $editBtn.attr('data-is-active', checked ? '1' : '0');
                        $cb.attr('aria-label', checked ? activeText : inactiveText);
                        if (checked) {
                            $label.removeClass('text-gray-600').addClass('text-emerald-700').text(activeText);
                        } else {
                            $label.removeClass('text-emerald-700').addClass('text-gray-600').text(inactiveText);
                        }
                    }
                },
                error: function () {
                    $cb.prop('checked', !checked);
                }
            });
        });
    </script>
@endpush
