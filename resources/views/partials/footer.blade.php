<footer class="site-footer">
    @php
        $set = \App\Models\SiteSetting::group('site');
        $href = fn ($u) => \App\Models\SiteSetting::href($u);
    @endphp
    <div class="container footer-grid" style="--cols: {{ max(1, count($set['columns'])) }}">
        <div class="footer-brand">
            <a href="{{ route('home') }}" class="brand brand--light">
                <img src="{{ \App\Models\SiteSetting::logoUrl() }}" alt="" width="44" height="44">
                <span>Etude <strong>World</strong></span>
            </a>
            @if ($set['footer_about'] !== '') <p>{{ $set['footer_about'] }}</p> @endif
            @if ($set['contact_email'] !== '') <p class="footer-mail"><a href="mailto:{{ $set['contact_email'] }}">{{ $set['contact_email'] }}</a></p> @endif
            {{-- Social links: set in Admin → Site Settings --}}
            <div class="social-links footer-social">
                @foreach (['linkedin' => 'LinkedIn', 'facebook' => 'Facebook', 'tiktok' => 'TikTok'] as $net => $netName)
                    <a href="{{ $href($set['social'][$net] ?? '') }}" class="social-link social-link--{{ $net }}" aria-label="{{ $netName }}" target="_blank" rel="noopener" onclick="return this.getAttribute('href')!=='#'">@include('partials.social-icon', ['name' => $net])</a>
                @endforeach
            </div>
        </div>

        {{-- Link columns: set in Admin → Site Settings --}}
        @foreach ($set['columns'] as $col)
            <div class="footer-col">
                @if (($col['title'] ?? '') !== '') <h3>{{ $col['title'] }}</h3> @endif
                <ul>
                    @foreach ($col['links'] ?? [] as $l)
                        <li><a href="{{ $href($l['url'] ?? '') }}">{{ $l['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    <div class="footer-bottom">
        <div class="container">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <a href="#top" class="to-top" onclick="event.preventDefault();window.scrollTo({top:0,behavior:'smooth'});">Back to top ↑</a>
        </div>
    </div>
</footer>
