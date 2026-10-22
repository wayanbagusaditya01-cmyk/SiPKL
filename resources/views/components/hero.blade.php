@props(['title' => null, 'name' => null, 'description' => null])
@php
    $hour = (int) now()->format('G');
    $greeting = match (true) {
        $hour < 11 => 'Selamat pagi',
        $hour < 15 => 'Selamat siang',
        $hour < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };
    $heading = $title ?? $greeting.($name ? ', '.\Illuminate\Support\Str::of($name)->explode(' ')->first() : '');
@endphp
<section class="hero" data-hero data-spotlight data-timezone="{{ config('app.timezone') }}">
    <x-orbit class="hero__orbit"/>
    <div class="hero__body">
        <p class="hero__eyebrow">{{ now()->translatedFormat('l, j F Y') }} · <span data-live-clock>{{ now()->format('H:i') }}</span></p>
        <h1 class="hero__title">{{ $heading }}</h1>
        @if($description)<p class="hero__description">{{ $description }}</p>@endif
        @isset($actions)<div class="hero__actions">{{ $actions }}</div>@endisset
    </div>
    @isset($stats)<div class="hero__stats">{{ $stats }}</div>@endisset
</section>
