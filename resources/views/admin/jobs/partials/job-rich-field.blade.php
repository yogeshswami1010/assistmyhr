{{--
  Design-aligned rich field: toolbar + contenteditable, synced to hidden textarea for submit.
  @param string $name
  @param string $textareaId
  @param string $editorId
  @param string $label
  @param string $placeholder
  @param bool $required
  @param bool $showLinkButton
  @param string $value
--}}
@php
    $value = $value ?? '';
    $required = $required ?? false;
    $showLinkButton = $showLinkButton ?? false;
@endphp
<div class="form-group mb-0">
    <label for="{{ $textareaId }}" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">{{ $label }} @if ($required)<span class="text-red-500">*</span>@endif</label>
    <div class="job-rich-field overflow-hidden rounded-xl border border-gray-200 transition-all focus-within:border-[#2563EB] focus-within:ring-2 focus-within:ring-[#2563EB]/10">
        <div class="flex flex-wrap items-center gap-1 border-b border-gray-200 bg-[#F8F7F4] px-3 py-2">
            <button type="button" class="job-rte-btn flex h-7 w-7 items-center justify-center rounded-md border-0 bg-[#2563EB] text-[12.5px] font-bold text-white transition-opacity hover:opacity-80" data-cmd="bold" title="Bold"><span class="font-bold">B</span></button>
            <button type="button" class="job-rte-btn flex h-7 w-7 items-center justify-center rounded-md border-0 text-slate-500 transition-all hover:bg-gray-200 hover:text-[#0F1F3D]" data-cmd="italic" title="Italic"><span class="italic">I</span></button>
            <button type="button" class="job-rte-btn flex h-7 w-7 items-center justify-center rounded-md border-0 text-slate-500 transition-all hover:bg-gray-200 hover:text-[#0F1F3D]" data-cmd="underline" title="Underline"><span class="underline">U</span></button>
            <div class="mx-1 h-4 w-px bg-gray-300" aria-hidden="true"></div>
            <button type="button" class="job-rte-btn flex h-7 w-7 items-center justify-center rounded-md border-0 text-slate-500 transition-all hover:bg-gray-200 hover:text-[#0F1F3D]" data-cmd="insertUnorderedList" title="@lang('modules.jobs.rteBulletList')">
                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="4" cy="6" r="2"/><circle cx="4" cy="12" r="2"/><circle cx="4" cy="18" r="2"/><path d="M9 5h13v2H9zm0 6h13v2H9zm0 6h13v2H9z"/></svg>
            </button>
            <button type="button" class="job-rte-btn flex h-7 w-7 items-center justify-center rounded-md border-0 text-slate-500 transition-all hover:bg-gray-200 hover:text-[#0F1F3D]" data-cmd="insertOrderedList" title="@lang('modules.jobs.rteNumberedList')">
                <span aria-hidden="true" style="font-size: 12px; font-weight: 600;">1.</span><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M5 6h15M5 12h15M5 18h15"/></svg>
            </button>
            <div class="mx-1 h-4 w-px bg-gray-300" aria-hidden="true"></div>
            <button type="button" class="job-rte-btn flex h-7 w-7 items-center justify-center rounded-md border-0 text-slate-500 hover:bg-gray-200" data-cmd="decreaseFontSize" title="{{ __('Decrease font size') }}" aria-label="{{ __('Decrease font size') }}">A−</button>
            <button type="button" class="job-rte-btn flex h-7 w-7 items-center justify-center rounded-md border-0 text-slate-500 hover:bg-gray-200" data-cmd="increaseFontSize" title="{{ __('Increase font size') }}" aria-label="{{ __('Increase font size') }}">A+</button>
            <div class="mx-1 h-4 w-px bg-gray-300" aria-hidden="true"></div>
            @foreach ([['formatBlock', 'p', 'Paragraph', '¶'], ['formatBlock', 'h2', 'Heading', 'H2'], ['justifyLeft', '', 'Align left', 'Left'], ['justifyCenter', '', 'Align center', 'Center'], ['justifyRight', '', 'Align right', 'Right'], ['indent', '', 'Increase indent', '→'], ['outdent', '', 'Decrease indent', '←'], ['undo', '', 'Undo', '↶'], ['redo', '', 'Redo', '↷'], ['removeFormat', '', 'Clear formatting', 'Clear']] as [$command, $commandValue, $commandLabel, $buttonText])
                <button type="button" class="job-rte-btn flex h-7 items-center justify-center rounded-md border-0 px-2 text-slate-500 hover:bg-gray-200" style="font-size: 12px;" data-cmd="{{ $command }}" data-value="{{ $commandValue }}" title="{{ __($commandLabel) }}" aria-label="{{ __($commandLabel) }}">{{ $buttonText }}</button>
            @endforeach
            @if ($showLinkButton)
                <div class="mx-1 h-4 w-px bg-gray-300" aria-hidden="true"></div>
                <button type="button" class="job-rte-btn flex h-7 w-7 items-center justify-center rounded-md border-0 text-slate-500 transition-all hover:bg-gray-200 hover:text-[#0F1F3D]" data-cmd="createLink" title="@lang('modules.jobs.rteInsertLink')">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                </button>
                <button type="button" class="job-rte-btn flex h-7 items-center justify-center rounded-md border-0 px-2 text-slate-500 hover:bg-gray-200" style="font-size: 12px;" data-cmd="unlink" title="{{ __('Remove link') }}" aria-label="{{ __('Remove link') }}">{{ __('Unlink') }}</button>
            @endif
        </div>
        <div id="{{ $editorId }}" contenteditable="true" tabindex="0" role="textbox" aria-multiline="true" data-placeholder="{{ $placeholder }}" class="job-rich-editor-editor min-h-[110px] px-4 py-3 text-[13.5px] text-[#0F1F3D] outline-none"></div>
    </div>
    <textarea name="{{ $name }}" id="{{ $textareaId }}" class="hidden" tabindex="-1" aria-hidden="true" autocomplete="off">{{ $value }}</textarea>
</div>
