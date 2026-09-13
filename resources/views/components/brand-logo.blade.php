@props(['organization' => null, 'variant' => 'logo', 'pdf' => false, 'class' => ''])
@php($source = $pdf ? $organization?->brandingDataUri($variant) : $organization?->brandingUrl($variant))
@if($source)
    <img src="{{ $source }}" alt="{{ $organization->name }}" {{ $attributes->merge(['class' => 'object-contain '.$class]) }}>
@else
    <span {{ $attributes->merge(['class' => 'font-black tracking-widest '.$class]) }}>{{ $organization?->name ?? 'EDUTECH' }}</span>
@endif
