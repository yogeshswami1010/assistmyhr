@props(['board'])

@php
    $color = match ($board['color']) {
        'violet' => 'bg-violet-100 text-violet-800',
        'blue' => 'bg-blue-100 text-blue-800',
        'emerald' => 'bg-emerald-100 text-emerald-800',
        'amber' => 'bg-amber-100 text-amber-900',
        'rose' => 'bg-rose-100 text-rose-800',
    };
@endphp
<span aria-hidden="true" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-bold {{ $color }}">{{ $board['icon'] }}</span>
