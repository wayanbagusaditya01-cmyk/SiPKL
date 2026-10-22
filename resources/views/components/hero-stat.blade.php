@props(['label', 'value', 'unit' => null, 'icon' => 'dashboard', 'href' => null])
@if($href)
<a href="{{ $href }}" {{ $attributes->class('hero-stat') }} data-spotlight>
@else
<div {{ $attributes->class('hero-stat') }} data-spotlight>
@endif
    <span class="hero-stat__icon" aria-hidden="true"><x-icon :name="$icon"/></span>
    <strong><span data-count="{{ is_numeric($value) ? $value : '' }}">{{ $value }}</span>@if($unit)<small> {{ $unit }}</small>@endif</strong>
    <span class="hero-stat__label">{{ $label }}</span>
@if($href)
</a>
@else
</div>
@endif
