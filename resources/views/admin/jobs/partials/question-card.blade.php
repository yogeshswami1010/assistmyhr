            @php $isResumeQuestion = strtolower(trim($question->question)) === 'upload resume'; @endphp
            <label
                data-question-text="{{ strtolower($question->question) }}"
                class="question-item flex items-start justify-between gap-4 rounded-xl border border-gray-200 bg-white p-4 cursor-pointer hover:border-blue-300 hover:bg-blue-50/30 transition {{ $isResumeQuestion ? 'border-blue-300 bg-blue-50/40' : '' }}">

                <div class="flex items-start gap-3 flex-1">
                    <div class="pt-0.5">
                        <input type="checkbox"
                               name="question[]"
                               value="{{ $question->id }}"
                               class="question-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                               @if($isResumeQuestion || in_array($question->id, $jobQuestion ?? [])) checked @endif
                               @if($isResumeQuestion) onclick="return false;" @endif
                               onchange="updateSelectedCount()">
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="text-sm font-semibold text-gray-800">
                                {{ $question->question }}
                            </h4>
                            <span class="rounded bg-blue-100 px-2 py-0.5 text-[10px] font-medium text-blue-700">
                                {{ ucfirst($question->type) }}
                            </span>
                            @if($question->required == 'yes')
                                <span class="rounded bg-red-100 px-2 py-0.5 text-[10px] font-medium text-red-700">Required</span>
                            @endif
                            @if($isResumeQuestion)
                                <span class="rounded bg-blue-100 px-2 py-0.5 text-[10px] font-medium text-blue-700">Always required</span>
                            @endif
                            @if($question->is_knockout)
                                <span class="rounded bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700">Knockout</span>
                            @endif

                            {{-- Job usage count badge --}}
                            @php $usedInJobs = $jobUsageCounts[$question->id] ?? 0; @endphp
                            <span class="rounded px-2 py-0.5 text-[10px] font-medium inline-flex items-center gap-1
                                {{ $usedInJobs > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}">
                                <i class="fa fa-briefcase text-[9px]"></i>
                                {{ $usedInJobs > 0 ? 'Used in ' . $usedInJobs . ' job' . ($usedInJobs > 1 ? 's' : '') : 'Not used yet' }}
                            </span>
                        </div>
                        @if($question->type == 'radio' && !empty($question->answer_type))
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach(explode(',', $question->answer_type) as $option)
                                    @php $option = trim($option); $isKnockout = $question->knockout_answer == $option; @endphp
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px]
                                        {{ $isKnockout ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-gray-100 text-gray-700 border border-gray-200' }}">
                                        {{ $option }}
                                        @if($isKnockout)<i class="fa fa-ban text-[9px]"></i>@endif
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <a href="{{ route('admin.questions.edit', $question->id) }}"
                   target="_blank"
                   onclick="event.stopPropagation();"
                   class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-600 hover:bg-blue-100 transition"
                   title="Edit Question">
                    <i class="fa fa-pencil"></i>
                </a>
            </label>
