@props(['name', 'role', 'subtitle' => null])
@php
    $initials = collect(explode(' ', trim($name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp
<section class="profile-header" data-spotlight>
    <x-orbit class="page-heading__orbit"/>
    <span class="profile-header__avatar" aria-hidden="true">{{ $initials }}</span>
    <div class="profile-header__body">
        <p class="profile-header__role">{{ $role }}</p>
        <h1>{{ $name }}</h1>
        @if($subtitle)<p class="profile-header__subtitle">{{ $subtitle }}</p>@endif
    </div>
    @isset($meta)<dl class="profile-header__meta">{{ $meta }}</dl>@endisset
</section>
