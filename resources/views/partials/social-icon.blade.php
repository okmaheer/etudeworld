{{-- Simple social media icons (filled). Usage: @include('partials.social-icon', ['name' => 'facebook']) --}}
@switch($name)
    @case('facebook')
        <svg class="social-svg" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M13.5 21v-7.5h2.6l.4-3h-3V8.6c0-.9.3-1.5 1.6-1.5h1.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.2H7.9v3h2.6V21h3z"/></svg>
        @break
    @case('x')
        <svg class="social-svg" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M17.2 3.5h3l-6.6 7.5 7.8 9.5h-6.1l-4.8-5.9-5.4 5.9h-3l7-7.9L1.6 3.5h6.2l4.3 5.4 5.1-5.4zm-1 15.3h1.7L7 5.1H5.2l11 13.7z"/></svg>
        @break
    @case('linkedin')
        <svg class="social-svg" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM3 9.5h4V21H3V9.5zm6.5 0h3.8v1.6h.1c.5-1 1.8-2 3.8-2 4 0 4.8 2.6 4.8 6V21h-4v-5.2c0-1.2 0-2.8-1.7-2.8s-2 1.3-2 2.7V21h-4V9.5z"/></svg>
        @break
    @case('tiktok')
        <svg class="social-svg" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M16.6 3c.3 2.4 1.7 3.9 4 4v3a7.2 7.2 0 0 1-4-1.2v6.2a6 6 0 1 1-6-6c.3 0 .6 0 .9.1v3.1a3 3 0 1 0 2.1 2.8V3h3z"/></svg>
        @break
@endswitch
