@props(['label', 'value', 'caption' => null])
<div class="stat"><p>{{ $label }}</p><strong>{{ $value }}</strong>@if($caption)<small>{{ $caption }}</small>@endif</div>
