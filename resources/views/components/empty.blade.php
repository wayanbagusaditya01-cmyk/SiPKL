@props(['title', 'description'])
<div class="empty"><span class="empty-icon"><x-icon name="file"/></span><h2>{{ $title }}</h2><p>{{ $description }}</p>{{ $slot }}</div>
