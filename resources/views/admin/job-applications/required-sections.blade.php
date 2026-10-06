@if($section_visibility['profile_image'] == 'yes')
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 border-t border-gray-200 pt-6">
        <div class="md:col-span-8 md:col-start-5">
            <div class="form-group">
                <h6 class="text-sm font-semibold text-gray-900 mb-2">
                    @lang('modules.front.photo')
                </h6>
                <input class="select-file" accept=".png,.jpg,.jpeg" type="file" name="photo">
                <p class="text-xs text-gray-600 mt-1 required">@lang('modules.front.photoFileType')</p>
            </div>
            @if(!empty($application) && !is_null($application->photo))
                <p class="mt-2">
                    <a target="_blank" href="{{ asset_url_local_s3('candidate-photos/'.$application->photo) }}" class="px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm inline-flex items-center">@lang('app.view')</a>
                </p>
            @endif
        </div>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-12 gap-6 border-t border-gray-200 pt-6">
    @if($section_visibility['resume'] == 'yes')
        <div class="md:col-span-4">
            <h5 class="text-lg font-semibold text-gray-900 required">@lang('modules.front.resume')</h5>
        </div>
        
        <div class="md:col-span-8">
            <div class="form-group">
                <input class="select-file" accept=".png,.jpg,.jpeg,.pdf,.doc,.docx,.xls,.xlsx,.rtf" type="file"
                    name="resume">
                <p class="text-xs text-gray-600 mt-1 required">@lang('modules.front.resumeFileType')</p>
                <p class="text-xs text-gray-500 mt-1">@lang('modules.front.resumeAiAutoFillHint')</p>
            </div>
            @if (!empty($application) && $application->resume_url)
                <p class="mt-2">
                    <a target="_blank" href="{{ $application->resume_url }}" class="px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm inline-flex items-center">
                        @lang('app.view')
                    </a>
                </p>
            @endif
        </div>
    @endif
    @if($section_visibility['cover_letter'] == 'yes')
        <div class="md:col-span-4">
            <h5 class="text-lg font-semibold text-gray-900">@lang('modules.front.coverLetter')</h5>
        </div>
        
        <div class="md:col-span-8">
            <div class="form-group">
                <div class="flex justify-end mb-2">
                    <button type="button"
                        class="inline-flex items-center gap-1 ai-generate-cover-letter px-3 py-1.5 rounded-lg border border-[#2563EB]/25 bg-[#EFF6FF] text-[#2563EB] text-[12px] font-semibold hover:bg-[#DBEAFE] disabled:cursor-not-allowed disabled:opacity-60">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l1.912 5.813a2 2 0 001.268 1.268L21 12l-5.82 1.919a2 2 0 00-1.261 1.261L12 21l-1.919-5.82a2 2 0 00-1.261-1.261L3 12l5.813-1.919a2 2 0 001.268-1.268L12 3z"/>
                        </svg>
                        <span>@lang('app.aiGenerate')</span>
                    </button>
                </div>
                <textarea id="cover_letter" class="form-control" name="cover_letter" rows="4">{{ !empty($application) ? $application->cover_letter : '' }}</textarea>
            </div>
        </div>
    @endif
</div>