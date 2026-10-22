@props(['title' => 'Panduan pengisian', 'items' => []])
<aside {{ $attributes->class('panel form-guide') }}>
    <span class="form-guide__icon" aria-hidden="true"><x-icon name="info"/></span>
    <h2>{{ $title }}</h2>
    <ul>
        @foreach($items as $item)
            <li>{{ $item }}</li>
        @endforeach
    </ul>
    <p class="form-guide__note">Kolom bertanda <span aria-hidden="true">*</span> wajib diisi.</p>
</aside>
