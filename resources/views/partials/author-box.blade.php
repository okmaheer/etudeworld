{{-- Author block shown at the end of a blog. The whole box links to the author page.
     $label: "Written by" for blogs, "Evaluated by" for sample answers. --}}
@php $label = $label ?? 'Written by'; @endphp
<aside class="author-box" aria-label="About the author">
    <a href="{{ route('authors.show', $author) }}" class="author-box__photo" tabindex="-1" aria-hidden="true">
        @if ($author->image)
            <img src="{{ asset($author->image) }}" alt="" width="96" height="96" loading="lazy">
        @else
            <span>{{ $author->initials }}</span>
        @endif
    </a>
    <div class="author-box__body">
        <p class="author-box__label">{{ $label }}</p>
        <h2 class="author-box__name"><a href="{{ route('authors.show', $author) }}" rel="author">{{ $author->name }}</a></h2>
        @if ($author->bio)
            <div class="author-box__bio">{!! $author->bio !!}</div>
        @endif
        <div class="author-box__foot">
            @if ($author->social_links)
                <div class="social-links">
                    @foreach ($author->social_links as $link)
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener me" class="social-link social-link--{{ $link['key'] }}" aria-label="{{ $author->name }} on {{ $link['label'] }}">
                            @include('partials.social-icon', ['name' => $link['key']])
                        </a>
                    @endforeach
                </div>
            @endif
            <a href="{{ route('authors.show', $author) }}" class="author-box__more">More from {{ \Illuminate\Support\Str::before($author->name, ' ') ?: $author->name }} →</a>
        </div>
    </div>
</aside>
