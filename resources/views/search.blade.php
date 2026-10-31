@extends('layouts.app')

@section('title', ($q !== '' ? 'Search results for "' . $q . '"' : 'Search') . ' – ' . config('app.name'))
@section('description', 'Search Etude World for IELTS, CELPIP and TOEFL practice, tools, lessons and blog posts.')

@push('head')
    <meta name="robots" content="noindex, follow">
@endpush

@section('content')
<section class="info-hero">
    <div class="container">
        <h1>Search</h1>
        <p>Find practice tests, tools, lessons, sample answers and blog posts.</p>
    </div>
</section>

<section class="section search-page">
    <div class="container-narrow">
        <form action="{{ route('search') }}" method="GET" role="search" class="search-form">
            <label class="sr-only" for="sp-q">Search the website</label>
            <input id="sp-q" type="search" name="q" value="{{ $q }}" maxlength="60" placeholder="Try &quot;IELTS writing&quot;, &quot;calculator&quot; or &quot;German&quot;" autocomplete="off" @if ($q === '') autofocus @endif>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        @if ($tooShort)
            <p class="search-note">Please type at least 2 letters.</p>
        @elseif ($q !== '')
            @if (count($results))
                <p class="search-note">{{ count($results) }} {{ count($results) === 1 ? 'result' : 'results' }} for <strong>{{ $q }}</strong></p>
                <ul class="search-results">
                    @foreach ($results as $r)
                        <li>
                            <span class="search-type">{{ $r['type'] }}</span>
                            <a href="{{ $r['url'] }}" class="search-title">{{ $r['title'] }}</a>
                            @if ($r['text']) <p>{{ $r['text'] }}</p> @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="search-empty">
                    <h2>Nothing found for "{{ $q }}"</h2>
                    <p>Check the spelling or try a shorter word. You can also start from here:</p>
                    <p class="search-quick">
                        <a href="{{ route('ielts') }}">IELTS</a>
                        <a href="{{ route('celpip') }}">CELPIP</a>
                        <a href="{{ route('toefl') }}">TOEFL</a>
                        <a href="{{ route('courses.index') }}">Courses</a>
                    </p>
                </div>
            @endif
        @endif
    </div>
</section>
@endsection
