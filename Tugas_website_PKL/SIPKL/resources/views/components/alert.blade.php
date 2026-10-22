@props(['type' => 'info'])
<div class="alert {{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}"><x-icon name="info"/><div>{{ $slot }}</div></div>
