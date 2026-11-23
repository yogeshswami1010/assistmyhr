@php
    $registrationFilters = [
        'month' => [
            'label' => 'Submitted Month',
            'options' => collect($filterMonths)->mapWithKeys(fn ($month) => [(string) $month => \Carbon\Carbon::create()->month($month)->format('F')]),
        ],
        'year' => [
            'label' => 'Submitted Year',
            'options' => collect($filterYears)->mapWithKeys(fn ($year) => [(string) $year => (string) $year]),
        ],
        'gender' => [
            'label' => 'Gender',
            'options' => collect($filterGenders)->mapWithKeys(fn ($gender) => [(string) $gender => (string) $gender]),
        ],
        'city' => [
            'label' => 'City',
            'options' => collect($filterCities)->mapWithKeys(fn ($city) => [(string) $city => (string) $city]),
        ],
        'job_type' => [
            'label' => 'Job Type',
            'options' => collect($filterJobTypes)->mapWithKeys(fn ($jobType) => [(string) $jobType => (string) $jobType]),
        ],
        'available_weekends' => [
            'label' => 'Available Weekends',
            'options' => collect(['1' => 'Yes', '0' => 'No']),
        ],
        'night_shifts' => [
            'label' => 'Night Shifts',
            'options' => collect(['1' => 'Yes', '0' => 'No']),
        ],
    ];
    $filterAllLabels = collect($registrationFilters)->mapWithKeys(fn ($definition, $name) => [$name => $definition['label']]);
@endphp

<form id="consortium-registration-filters" method="GET" action="{{ route('admin.consortium-registrations.index') }}" class="ja-filter-form">
        @foreach($registrationFilters as $filterName => $definition)
            @php
                $selectedValues = array_values(array_filter(array_map('strval', (array) request($filterName, [])), fn ($value) => $value !== ''));
                $selectedLabels = collect($selectedValues)->map(fn ($value) => $definition['options']->get($value, $value))->values();
                $summary = $selectedLabels->isNotEmpty() ? $selectedLabels->implode(', ') : $definition['label'];
            @endphp
            <div class="registration-multi-filter relative" data-filter-name="{{ $filterName }}">
                <button type="button" aria-label="{{ $definition['label'] }}" aria-expanded="false" class="registration-filter-toggle flex h-[38px] w-full items-center justify-between rounded-lg border bg-white px-3 text-left text-[13px] outline-none transition hover:border-[#BFC7D3] focus:border-blue-500 {{ $selectedLabels->isNotEmpty() ? 'border-blue-300 text-blue-700 ring-1 ring-blue-100' : 'border-[#DDE2EA] text-[#3D4A5C]' }}">
                    <span class="registration-filter-summary truncate" title="{{ $summary }}">{{ $summary }}</span>
                    <span class="ml-2 flex shrink-0 items-center gap-1.5">
                        <span class="registration-filter-count {{ $selectedLabels->isEmpty() ? 'hidden' : '' }} rounded-full bg-blue-600 px-1.5 py-0.5 text-[9px] font-bold text-white">{{ $selectedLabels->count() }}</span>
                        <svg class="h-3.5 w-3.5 text-[#8892A0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </span>
                </button>
                <div class="registration-filter-menu absolute left-0 top-full z-50 mt-2 hidden w-[280px] overflow-hidden rounded-xl border border-[#DDE2EA] bg-white shadow-xl">
                    <div class="border-b border-[#EEF0F4] p-2.5">
                        <input type="search" class="registration-filter-search w-full rounded-lg border border-[#DDE2EA] bg-[#F8F9FB] px-3 py-2 text-[12px] outline-none focus:border-blue-500" placeholder="Search {{ strtolower($definition['label']) }}..." autocomplete="off">
                    </div>
                    <div class="registration-filter-options max-h-60 overflow-y-auto p-2" style="overscroll-behavior:contain">
                        @foreach($definition['options'] as $optionValue => $optionLabel)
                            @php($isSelected = in_array((string) $optionValue, $selectedValues, true))
                            <label class="registration-filter-option flex cursor-pointer items-center gap-2.5 rounded-lg border px-2.5 py-2 text-[12px] transition {{ $isSelected ? 'border-blue-200 bg-blue-50 font-semibold text-blue-700' : 'border-transparent text-[#3D4A5C] hover:bg-[#F1F5FF]' }}" data-option-search="{{ strtolower($optionLabel) }}">
                                <input type="checkbox" name="{{ $filterName }}[]" value="{{ $optionValue }}" @checked($isSelected) class="h-4 w-4 rounded border-[#C8D0DC] text-blue-600 focus:ring-blue-500">
                                <span>{{ $optionLabel }}</span>
                            </label>
                        @endforeach
                        <p class="registration-filter-empty hidden px-3 py-6 text-center text-[11px] text-[#8892A0]">No option found.</p>
                    </div>
                    <div class="flex items-center justify-end border-t border-[#EEF0F4] bg-[#FBFCFE] px-3 py-2.5">
                        <button type="button" class="registration-filter-clear text-[11px] font-semibold text-[#6B7280] hover:text-[#DC2626]">Clear selection</button>
                    </div>
                </div>
            </div>
        @endforeach
    <div class="flex shrink-0 items-center gap-2">
        <button type="submit" id="registration-filters-apply" class="inline-flex h-[38px] items-center gap-2 rounded-lg bg-[#2563EB] px-3 text-[13px] font-semibold text-white hover:bg-[#1D4ED8] focus-visible:ring-2 focus-visible:ring-blue-500">
            <i class="fa fa-filter" aria-hidden="true"></i> {{ __('Apply filters') }}
        </button>
        <a href="{{ route('admin.consortium-registrations.index') }}" id="reset-filters" class="inline-flex h-[38px] items-center gap-2 px-3 text-[13px] font-semibold text-slate-600 hover:text-blue-700 focus-visible:ring-2 focus-visible:ring-blue-500">
            <i class="fa fa-refresh" aria-hidden="true"></i> @lang('app.reset')
        </a>
    </div>
</form>

@push('footer-script')
<script>
(function () {
    var lockedScrollContainer = null;
    var lockedScrollTop = 0;
    var previousOverflowY = '';

    function lockFilterPage() {
        if (lockedScrollContainer) return;
        lockedScrollContainer = document.querySelector('.ra-main > .ra-scroll') || document.querySelector('.ra-scroll');
        if (!lockedScrollContainer) return;
        lockedScrollTop = lockedScrollContainer.scrollTop;
        previousOverflowY = lockedScrollContainer.style.overflowY;
        lockedScrollContainer.style.overflowY = 'hidden';
        lockedScrollContainer.addEventListener('scroll', holdFilterPagePosition);
    }

    function holdFilterPagePosition() {
        if (lockedScrollContainer) lockedScrollContainer.scrollTop = lockedScrollTop;
    }

    function unlockFilterPage() {
        if (!lockedScrollContainer) return;
        lockedScrollContainer.removeEventListener('scroll', holdFilterPagePosition);
        lockedScrollContainer.style.overflowY = previousOverflowY;
        lockedScrollContainer.scrollTop = lockedScrollTop;
        lockedScrollContainer = null;
    }

    function closeFilterMenus() {
        $('.registration-filter-menu').addClass('hidden');
        $('.registration-filter-toggle').attr('aria-expanded', 'false');
        unlockFilterPage();
    }

    function updateFilterDisplay($filter) {
        var labels = $filter.find('input[type="checkbox"]:checked').map(function () {
            return $.trim($(this).closest('.registration-filter-option').find('span').text());
        }).get();
        var allLabel = {!! json_encode($filterAllLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
        var fallback = allLabel[$filter.data('filter-name')] || 'All';
        var summary = labels.length ? labels.join(', ') : fallback;
        $filter.find('.registration-filter-summary').text(summary).attr('title', summary);
        $filter.find('.registration-filter-count').text(labels.length).toggleClass('hidden', labels.length === 0);
        $filter.find('.registration-filter-toggle')
            .toggleClass('border-blue-300 text-blue-700 ring-1 ring-blue-100', labels.length > 0)
            .toggleClass('border-[#DDE2EA] text-[#3D4A5C]', labels.length === 0);
        $filter.find('.registration-filter-option').each(function () {
            var selected = $(this).find('input').is(':checked');
            $(this).toggleClass('border-blue-200 bg-blue-50 font-semibold text-blue-700', selected)
                .toggleClass('border-transparent text-[#3D4A5C] hover:bg-[#F1F5FF]', !selected);
        });
    }

    $('.registration-filter-toggle').on('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var $filter = $(this).closest('.registration-multi-filter');
        var $menu = $filter.find('.registration-filter-menu');
        var opening = $menu.hasClass('hidden');
        closeFilterMenus();
        if (opening) {
            lockFilterPage();
            $menu.removeClass('hidden');
            $(this).attr('aria-expanded', 'true');
            $menu.css('left', '');
            var overflow = $menu[0].getBoundingClientRect().right - document.documentElement.clientWidth + 12;
            if (overflow > 0) $menu.css('left', -overflow + 'px');
            var search = $filter.find('.registration-filter-search').get(0);
            if (search) search.focus({ preventScroll: true });
        }
    });

    $('.registration-filter-menu').on('click', function (event) { event.stopPropagation(); });
    $(document).on('click', closeFilterMenus);
    $('#consortium-registration-filters').on('keydown', function (event) {
        if (event.key !== 'Escape') return;
        var toggle = this.querySelector('.registration-filter-toggle[aria-expanded="true"]');
        if (!toggle) return;
        closeFilterMenus();
        toggle.focus();
    });

    $('.registration-filter-search').on('input', function () {
        var $filter = $(this).closest('.registration-multi-filter');
        var query = $.trim($(this).val()).toLowerCase();
        var visible = 0;
        $filter.find('.registration-filter-option').each(function () {
            var matches = !query || String($(this).data('option-search')).indexOf(query) !== -1;
            $(this).toggleClass('hidden', !matches);
            if (matches) visible++;
        });
        $filter.find('.registration-filter-empty').toggleClass('hidden', visible > 0);
    });

    $('.registration-filter-options').on('change', 'input[type="checkbox"]', function (event) {
        event.stopPropagation();
        var $options = $(this).closest('.registration-filter-options');
        var scrollTop = $options.scrollTop();
        updateFilterDisplay($(this).closest('.registration-multi-filter'));
        $options.scrollTop(scrollTop);
    }).on('wheel', function (event) {
        var element = this;
        var original = event.originalEvent;
        var atTop = element.scrollTop <= 0;
        var atBottom = Math.ceil(element.scrollTop + element.clientHeight) >= element.scrollHeight;
        if ((original.deltaY < 0 && atTop) || (original.deltaY > 0 && atBottom)) event.preventDefault();
        event.stopPropagation();
    });

    $('.registration-filter-clear').on('click', function () {
        var $filter = $(this).closest('.registration-multi-filter');
        $filter.find('input[type="checkbox"]').prop('checked', false);
        updateFilterDisplay($filter);
    });
})();
</script>
@endpush
