@props(['title', 'description'])
<div class="page-heading" data-spotlight><x-orbit class="page-heading__orbit"/><div><h1>{{ $title }}</h1><p>{{ $description }}</p></div><div class="heading-actions">{{ $slot }}</div></div>
