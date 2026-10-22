@props(['href' => null, 'variant' => 'primary'])
@if($href)
<a href="{{ $href }}" {{ $attributes->class(['button', $variant]) }}>{{ $slot }}</a>
@else
<button {{ $attributes->class(['button', $variant])->merge(['type' => 'button']) }}>{{ $slot }}</button>
@endif
