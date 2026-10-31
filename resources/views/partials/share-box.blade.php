{{-- "Share Now" row at the end of a blog. Each button opens that site's own share window with this blog's address filled in. --}}
@php
    $u = rawurlencode($url);
    $t = rawurlencode($title);
    $networks = [
        ['name' => 'facebook', 'label' => 'Facebook', 'href' => 'https://www.facebook.com/sharer/sharer.php?u=' . $u],
        ['name' => 'x',        'label' => 'Twitter',  'href' => 'https://twitter.com/intent/tweet?url=' . $u . '&text=' . $t],
        ['name' => 'linkedin', 'label' => 'LinkedIn', 'href' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u],
    ];
@endphp
<div class="share-box">
    <strong class="share-box__title">Share Now:</strong>
    @foreach ($networks as $n)
        <a class="share-box__item" href="{{ $n['href'] }}" target="_blank" rel="noopener noreferrer" aria-label="Share on {{ $n['label'] }}">
            <span class="social-link social-link--{{ $n['name'] }}">@include('partials.social-icon', ['name' => $n['name']])</span>
            <span>{{ $n['label'] }}</span>
        </a>
    @endforeach
</div>
