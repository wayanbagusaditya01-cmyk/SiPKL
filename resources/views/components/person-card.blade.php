@props(['mentor' => null, 'label', 'compact' => false])
@php
    $name = $mentor?->user?->name;
    $initials = $name ? collect(explode(' ', trim($name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') : '?';
    $detail = $mentor ? ($mentor->isIndustry() ? ($mentor->position ?: 'Pembina Industri') : ($mentor->nip ? 'NIP '.$mentor->nip : 'Guru Pembimbing')) : null;
@endphp
<section {{ $attributes->class(['panel', 'person-card', 'person-card--compact' => $compact]) }}>
    <p class="eyebrow">{{ $label }}</p>
    @if($mentor)
        <div class="person-card__head">
            <span class="person-card__avatar" aria-hidden="true">{{ $initials }}</span>
            <div>
                <h2>{{ $name }}</h2>
                <p>{{ $detail }}</p>
            </div>
        </div>
        @unless($compact)
            <dl class="info-list">
                <x-info-item icon="mail" label="Email">{{ $mentor->user->email }}</x-info-item>
                <x-info-item icon="phone" label="Telepon">{{ $mentor->phone ?: 'Belum dicatat' }}</x-info-item>
                @if($mentor->isIndustry())
                    <x-info-item icon="place" label="Tempat PKL">{{ $mentor->place?->name ?? 'Belum ditetapkan' }}</x-info-item>
                @endif
            </dl>
        @endunless
        <div class="person-card__actions">
            <x-button variant="secondary" :href="'mailto:'.$mentor->user->email"><x-icon name="mail"/>Email</x-button>
            @if($mentor->phone)
                <x-button variant="secondary" :href="'tel:'.preg_replace('/[^0-9+]/', '', $mentor->phone)"><x-icon name="phone"/>Telepon</x-button>
            @endif
        </div>
    @else
        <div class="person-card__head">
            <span class="person-card__avatar person-card__avatar--empty" aria-hidden="true"><x-icon name="user"/></span>
            <div>
                <h2>Belum ditetapkan</h2>
                <p>Koordinator belum memilih {{ mb_strtolower($label) }}.</p>
            </div>
        </div>
    @endif
</section>
