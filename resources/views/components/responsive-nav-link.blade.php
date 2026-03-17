@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-2xl border border-black/10 bg-black/[0.03] px-4 py-3 text-start text-base font-semibold text-slate-950 transition duration-150 ease-in-out'
            : 'block w-full rounded-2xl border border-transparent px-4 py-3 text-start text-base font-medium text-slate-600 transition duration-150 ease-in-out hover:bg-black/[0.03] hover:text-slate-950';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
