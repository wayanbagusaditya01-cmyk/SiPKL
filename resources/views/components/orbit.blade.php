<svg {{ $attributes->merge(['class' => 'orbit']) }} viewBox="0 0 400 400" aria-hidden="true">
    <circle class="orbit__ring" cx="200" cy="200" r="190"/>
    <circle class="orbit__ring orbit__ring--dashed" data-orbit-dash cx="200" cy="200" r="140"/>
    <circle class="orbit__ring" cx="200" cy="200" r="90"/>
    <circle class="orbit__core" cx="200" cy="200" r="34"/>
    <g data-orbit-spin><circle class="orbit__satellite" cx="200" cy="60" r="7"/></g>
    <g data-orbit-spin-reverse><circle class="orbit__satellite orbit__satellite--alt" cx="200" cy="290" r="5"/></g>
</svg>
