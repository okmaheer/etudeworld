@extends('layouts.app')

@section('title', $page['title'] . ' – ' . config('app.name'))
@section('description', $page['desc'])

@push('head')<link rel="stylesheet" href="{{ asset('css/tools.css') }}?v=4">@endpush

@section('content')
<section class="section">
    <div class="container tool-wrap">
        <div class="section-head">
            <span class="eyebrow">Tools</span>
            <h1>{{ $page['title'] }}</h1>
            <p>{{ $page['blurb'] }}</p>
        </div>
        <div class="tool-box" data-tool="{{ $tool }}">
            @include('tools.' . $tool)
        </div>
        @php
            $exams = [
                'ielts' => ['IELTS', 'Take an IELTS practice test', 'Practise with real exam timing and see your score.', 'ielts'],
                'celpip' => ['CELPIP', 'Take a CELPIP practice test', 'Practise the computer-based test used for Canadian immigration.', 'celpip'],
                'toefl' => ['TOEFL', 'Take a TOEFL practice test', 'Practise for the TOEFL iBT.', 'toefl'],
            ];
            $related = [
                'study-planner' => ['ielts', 'celpip', 'toefl'],
                'ielts-calculator' => ['ielts'],
                'ielts-to-toefl' => ['toefl', 'ielts'],
                'ielts-to-pte' => ['ielts'],
                'ielts-to-celpip' => ['celpip', 'ielts'],
                'ielts-to-cefr' => ['ielts', 'celpip', 'toefl'],
            ][$tool] ?? [];
        @endphp
        @if ($related)
            <div class="tool-practice">
                <h2>Ready to practise?</h2>
                <div class="tool-practice__grid">
                    @foreach ($related as $k)
                        <a class="tool-practice__card tool-practice__card--{{ $k }}" href="{{ route($exams[$k][3]) }}">
                            <strong>{{ $exams[$k][1] }}</strong>
                            <span>{{ $exams[$k][2] }}</span>
                            <em>Start practising <span aria-hidden="true">→</span></em>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
        @php $faqs = config('tool_faqs.' . $tool, []); @endphp
        @if ($faqs)
            <div class="tool-faq">
                <h2>FAQ</h2>
                @foreach ($faqs as $f)
                    <details>
                        <summary>{{ $f[0] }}</summary>
                        <p>{{ $f[1] }}</p>
                    </details>
                @endforeach
            </div>
            <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
        @endif
        <div class="tool-more">
            <h2>More tools</h2>
            <div class="tool-links">
                @foreach ($pages as $key => $p)
                    @if ($key !== $tool)<a href="{{ route('tools.' . $key) }}">{{ $p['title'] }}</a>@endif
                @endforeach
            </div>
        </div>
    </div>
</section>
<script src="{{ asset('js/tools.js') }}?v=3" defer></script>
@endsection
