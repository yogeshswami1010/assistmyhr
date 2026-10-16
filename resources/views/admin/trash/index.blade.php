@extends('layouts.app')

@section('content')
<div class="px-4 py-5 sm:px-6 lg:px-8">
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-[22px] font-bold tracking-tight text-[#1A1E2E]">Trash</h1>
            <p class="mt-1 text-[12.5px] text-[#8892A0]">Deleted applications and notes stay here until permanently deleted.</p>
        </div>
        <form method="GET" action="{{ route('admin.trash.index') }}" class="flex gap-2">
            <input type="hidden" name="type" value="{{ $selectedType }}">
            <input name="search" value="{{ $search }}" placeholder="Search trash…" class="rounded-lg border border-[#E2DED8] px-3 py-2 text-sm">
            <button class="rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-semibold text-white">Search</button>
        </form>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach(['all' => 'All', 'application' => 'Applications', 'application-note' => 'Application notes', 'client-note' => 'Client notes'] as $value => $label)
            <a href="{{ route('admin.trash.index', ['type' => $value]) }}" class="rounded-lg px-3 py-2 text-xs font-semibold {{ $selectedType === $value ? 'bg-[#1A1E2E] text-white' : 'border border-[#E2DED8] bg-white text-[#5A6478]' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl border border-[#E8E6E1] bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-[#F8F7F4] text-[11px] uppercase tracking-wide text-[#8A94A6]">
                    <tr><th class="px-5 py-3">Type</th><th class="px-5 py-3">Deleted item</th><th class="px-5 py-3">Application / Job</th><th class="px-5 py-3">Deleted</th><th class="px-5 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-[#F0EEE9]">
                @forelse($trashItems as $item)
                    <tr>
                        <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">{{ $item->type_label }}</span></td>
                        <td class="max-w-md px-5 py-4"><div class="font-semibold text-[#1A1E2E]">{{ $item->title }}</div><div class="mt-1 line-clamp-2 text-xs text-[#6B7280]">{{ $item->details }}</div></td>
                        <td class="px-5 py-4 text-xs font-medium text-[#5A6478]">{{ $item->context }}</td>
                        <td class="whitespace-nowrap px-5 py-4 text-xs text-[#6B7280]">{{ $item->deleted_at->format('d M Y, h:i A') }}</td>
                        <td class="px-5 py-4"><div class="flex justify-end gap-2">
                            <form method="POST" action="{{ route('admin.trash.restore', [$item->type, $item->id]) }}">@csrf<button class="rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Restore</button></form>
                            <form method="POST" action="{{ route('admin.trash.destroy', [$item->type, $item->id]) }}" onsubmit="return confirm('Permanently delete this item? This cannot be undone.')">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Delete forever</button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-[#8892A0]">Trash is empty.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $trashItems->links() }}</div>
</div>
@endsection
