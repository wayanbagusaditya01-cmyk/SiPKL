@props(['icon' => 'info', 'label'])
<div {{ $attributes->class('info-item') }}>
    <span class="info-item__icon" aria-hidden="true"><x-icon :name="$icon"/></span>
    <div>
        <dt>{{ $label }}</dt>
        <dd>{{ $slot }}</dd>
    </div>
</div>
