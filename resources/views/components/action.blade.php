@props(['href' => null, 'icon' => null, 'variant' => 'default', 'type' => 'submit'])
@php($classes = ['action-btn', 'action-btn--'.$variant])
@if($href)
<a href="{{ $href }}" {{ $attributes->class($classes) }}>@if($icon)<x-icon :name="$icon"/>@endif<span>{{ $slot }}</span></a>
@else
<button type="{{ $type }}" {{ $attributes->class($classes) }}>@if($icon)<x-icon :name="$icon"/>@endif<span>{{ $slot }}</span></button>
@endif
