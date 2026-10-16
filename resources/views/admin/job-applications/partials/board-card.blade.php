@php
    $catName = optional($application->job?->category)->name ?? __('app.category');

    $catKey = \Illuminate\Support\Str::slug($catName);

    $catClass = match (true) {
        str_contains($catKey, 'engineer') || str_contains($catKey, 'tech') || str_contains($catKey, 'it') => 'bg-blue-50 text-blue-800',
        str_contains($catKey, 'sale') || str_contains($catKey, 'market') => 'bg-orange-50 text-orange-800',
        str_contains($catKey, 'content') || str_contains($catKey, 'design') => 'bg-emerald-50 text-emerald-800',
        str_contains($catKey, 'hr') || str_contains($catKey, 'people') => 'bg-violet-50 text-violet-800',
        default => 'bg-slate-100 text-slate-700',
    };

    // ✅ Updated knockout logic
    $isRejected = collect($application->answers ?? [])->contains(function ($answer) {
        if (!isset($answer->question)) {
            return false;
        }

        $question = $answer->question;

        // Must be radio type AND marked as a knockout question
        if ($question->type !== 'radio' || !$question->is_knockout) {
            return false;
        }

        // Applicant's answer must match the stored knockout answer
        return strtolower(trim($answer->answer)) === strtolower(trim($question->knockout_answer ?? ''));
    });
@endphp

<div id="task-card{{ $application->id }}"
     class="ja-k-card cursor-pointer select-none overflow-hidden rounded-[12px] border-[1.5px] border-transparent bg-white pt-3.5 pl-[14px] pr-[14px] pb-3 shadow-none transition duration-[180ms] ease-out hover:-translate-y-0.5 hover:border-[#E2DED8] hover:shadow-[0_8px_20px_rgba(15,31,61,0.09)] lobipanel show-detail task-card"
     data-row-id="{{ $application->id }}"
     data-column-id="{{ $columnId }}"
     data-application-id="{{ $application->id }}"
     data-sortable="true">

    <div class="pl-2.5">

        <input type="checkbox" class="cc-select" data-type="application" value="{{ $application->id }}" aria-label="Select {{ $application->full_name }}" onclick="event.stopPropagation()">
        {{-- Header --}}
        <div class="mb-2 flex items-start justify-between gap-2">

            <div class="flex min-w-0 flex-1 items-start gap-[9px]">

                <img src="{{ $application->photo_url }}"
                     alt=""
                     class="h-[34px] w-[34px] shrink-0 rounded-full border-2 border-white object-cover shadow-[0_2px_6px_rgba(0,0,0,0.14)]">

                <div class="min-w-0 flex-1">

                    <h5 class="truncate text-[13px] font-bold leading-snug text-[#1A1E2E]"
                        title="{{ $application->full_name }}">
                        {{ ucwords($application->full_name) }}
                    </h5>

                    <div class="stars stars-example-fontawesome mt-[3px] [&_.br-theme-fontawesome-stars]:leading-none [&_.br-widget]:h-3 [&_.br-widget_a]:!text-[11px]">

                        <select id="example-fontawesome_{{ $application->id }}"
                                data-value="{{ $application->rating }}"
                                data-id="{{ $application->id }}"
                                class="example-fontawesome bar-rating"
                                name="rating"
                                autocomplete="off">

                            <option value=""></option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>

                        </select>

                    </div>

                </div>

            </div>

            {{-- Rejected Badge --}}
            @if($isRejected)
                <span class="shrink-0 rounded-full bg-red-100 px-2 py-[4px] text-[10px] font-bold text-red-600">
                    Rejected
                </span>
            @endif

        </div>

        {{-- Job Title --}}
        <p class="mb-[10px] break-words text-[12px] leading-[1.45] text-[#5A6478]">
             {!! \App\Support\CandidateJobLabel::html($application->job) !!}
        </p>

        {{-- Footer --}}
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-[#F0EEE9] pt-[9px]">

            <span class="flex items-center gap-1 text-[11px] font-semibold text-[#B0B8C4]">

                <svg class="h-3 w-3 shrink-0"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24"
                     aria-hidden="true">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>

                </svg>

                @if(!is_null($application->schedule) && $columnId == 3)
                    {{ $application->schedule->schedule_date->format('d M, Y') }}
                @else
                    {{ $application->created_at->format('d M, Y') }}
                @endif

            </span>

            <div class="flex shrink-0 items-center gap-2">

                <span class="rounded-full px-[9px] py-[3px] text-[10.5px] font-bold tracking-wide {{ $catClass }}">
                    {{ ucfirst($catName) }}
                </span>

                @if(in_array('add_schedule', $userPermissions))

                    <span id="buttonBox{{ $columnId }}{{ $application->id }}"
                          data-timestamp="@if(!is_null($application->schedule)){{ $application->schedule->schedule_date->timestamp }}@endif">

                        @if(!is_null($application->schedule) && $columnId == 3 && $currentDate < $application->schedule->schedule_date->timestamp)

                            <button onclick="sendReminder({{ $application->id }}, 'reminder')"
                                    type="button"
                                    class="notify-button rounded-md bg-cyan-600 px-2 py-1 text-[10px] font-semibold text-white hover:bg-cyan-700">

                                @lang('app.reminder')

                            </button>

                        @endif

                        @if(in_array($statusSlug, ['hired', 'rejected']))

                            <button onclick="sendReminder({{ $application->id }}, 'notify', {{ json_encode($statusSlug) }})"
                                    type="button"
                                    class="notify-button rounded-md bg-cyan-600 px-2 py-1 text-[10px] font-semibold text-white hover:bg-cyan-700">

                                @lang('app.notify')

                            </button>

                        @endif

                    </span>

                @endif

            </div>

        </div>

    </div>

</div>
