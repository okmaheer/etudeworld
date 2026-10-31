@extends('admin.layout')

{{-- [id-1] IELTS Reading home page: the "Add …" buttons, then a preview of every passage and question group. --}}
@php $mod = $module; $isL = $mod === 'listening'; $word = $isL ? 'Part' : 'Passage / Section'; @endphp

@section('title', 'IELTS ' . ($isL ? 'Listening' : 'Reading') . ' Questions')

@section('actions')
    @if ($test->moduleReady($mod))
        <a href="{{ route('ielts.practice.test', [$test->formatPath(), $mod, $test->slug]) }}" class="btn btn-sm btn-ghost" target="_blank" rel="noopener">View on website ↗</a>
    @endif
    <a href="{{ route('admin.ielts.index') }}" class="btn btn-sm btn-ghost">← All IELTS Tests</a>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('css/ielts-reading.css') }}?v=27">
@endpush

@section('content')
    @include('admin.ielts.reading._head')

    <div class="rda-buttons">
        <a href="{{ route('admin.ielts.' . $mod . '.passages', $test) }}" class="btn btn-primary">@include('admin.partials.icon', ['name' => 'page']) {{ $isL ? 'Add Listening Transcripts' : 'Add Reading Passages / Sections' }}</a>
        <a href="{{ route('admin.ielts.vocab', [$test, $mod]) }}" class="btn btn-ghost">Vocabulary</a>
        @foreach ($R::TYPES as $type => $t)
            <a href="{{ route('admin.ielts.' . $mod . '.create', [$test, $type]) }}" class="btn btn-ghost rda-add">+ {{ preg_replace('/^Add /', '', $t['button']) }}</a>
        @endforeach
    </div>

    @if ($isL)
        <section class="panel rda-audio">
            <h2>Audio file</h2>
            <p class="muted">One audio file for the whole test. It starts playing by itself when the student clicks <strong>Start test</strong> on the instructions page.</p>
            @if ($content['audio'] !== '')
                <audio controls preload="metadata" src="{{ asset($content['audio']) }}"></audio>
                <form method="POST" action="{{ route('admin.ielts.listening.audio.remove', $test) }}" class="rda-audio__remove">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-ghost" data-confirm="Remove the audio from this test?">Remove audio</button></form>
            @else
                <p class="rda-warn">No audio yet – students cannot take the test until you add it.</p>
            @endif
            <form method="POST" action="{{ route('admin.ielts.listening.audio', $test) }}" enctype="multipart/form-data" class="rda-audio__form">@csrf
                <input type="file" name="audio" accept="audio/*,.mp3,.wav,.m4a,.aac,.ogg" required>
                <button class="btn btn-primary btn-sm">{{ $content['audio'] !== '' ? 'Replace audio' : 'Upload audio' }}</button>
                @error('audio') <span class="rda-warn">{{ $message }}</span> @enderror
            </form>
        </section>
    @endif

    <div class="rda-summary {{ $total === 40 ? 'is-ok' : '' }}">
        <strong>{{ $total }}</strong> of 40 questions added.
        @if ($total < 40) A full IELTS {{ $isL ? 'Listening' : 'Reading' }} test has 40 questions. @elseif ($total > 40) That is more than the usual 40 questions. @else The test is complete. @endif
        @if (! $isL && ! \App\Support\IeltsSpeaking::filled($content['passages'][1]))
            <span class="rda-warn">Add the reading passages first – students can take the test once Passage 1 and at least one question are added.</span>
        @endif
    </div>

    @foreach ($R::PARTS as $p)
        @php $groups = array_filter($ordered, fn ($g) => $g['passage'] === $p); $hasPassage = $isL || \App\Support\IeltsSpeaking::filled($content['passages'][$p]); @endphp
        <section class="panel rda-passage">
            <header class="rda-passage__head">
                <span class="part-bar__num">{{ $p }}</span>
                <div>
                    <h2>{{ $word }} {{ $p }}</h2>
                    <p class="muted">{{ $ranges[$p] ? $R::questionRange($ranges[$p][0], $ranges[$p][1] - $ranges[$p][0] + 1) : 'No questions yet' }}
                        @if (! $isL && ($letters = $R::letters($content['passages'][$p]))) · Paragraphs {{ implode(', ', $letters) }} @endif</p>
                </div>
                @unless ($isL)<a href="{{ route('admin.ielts.' . $mod . '.passages', $test) }}#passage-{{ $p }}" class="btn btn-sm btn-ghost">Edit passage</a>@endunless
            </header>

            @unless ($isL)
            <details class="rda-text" @unless ($hasPassage) open @endunless>
                <summary>{{ $hasPassage ? 'Show the passage' : 'No passage yet' }}</summary>
                <div class="rd-scope rda-text__body">
                    @if ($hasPassage)
                        {!! $R::passageHtml($content['passages'][$p], $R::headingBoxes($content, $p)) !!}
                    @else
                        <p class="muted">Click <strong>Add Reading Passages / Sections</strong> to write it.</p>
                    @endif
                </div>
            </details>
            @endunless

            @forelse ($groups as $g)
                @php $last = $g['start'] + max($g['count'], 1) - 1; @endphp
                <article class="rda-group">
                    <header class="rda-group__head">
                        <span class="badge badge-blue">{{ $R::TYPES[$g['type']]['label'] }}</span>
                        <strong>{{ $g['count'] ? $R::questionRange($g['start'], $g['count']) : 'No questions yet (add # for blanks)' }}</strong>
                        <div class="rda-group__actions">
                            <form method="POST" action="{{ route('admin.ielts.' . $mod . '.move', [$test, $g['id'], 'up']) }}">@csrf <button class="icon-btn" title="Move up" aria-label="Move up">↑</button></form>
                            <form method="POST" action="{{ route('admin.ielts.' . $mod . '.move', [$test, $g['id'], 'down']) }}">@csrf <button class="icon-btn" title="Move down" aria-label="Move down">↓</button></form>
                            <a href="{{ route('admin.ielts.' . $mod . '.edit', [$test, $g['id']]) }}" class="icon-btn" title="Edit" aria-label="Edit">@include('admin.partials.icon', ['name' => 'edit'])</a>
                            <form method="POST" action="{{ route('admin.ielts.' . $mod . '.destroy', [$test, $g['id']]) }}">@csrf @method('DELETE')
                                <button class="icon-btn icon-btn--danger" title="Delete" aria-label="Delete" data-confirm="Delete these questions? Question numbers after them will move up.">@include('admin.partials.icon', ['name' => 'trash'])</button></form>
                        </div>
                    </header>
                    <div class="rd-scope rda-preview">
                        {!! $R::groupHtml($g) !!}
                    </div>
                    @if ($g['count'])
                        <p class="rda-key"><strong>Answers:</strong>
                            @for ($q = $g['start']; $q <= $last; $q++)
                                @php $k = $key[$q] ?? null; @endphp
                                @if ($k)
                                    @php
                                        $a = match ($k['kind']) {
                                            'blanks'  => implode(' · ', $k['answers']),
                                            'two'     => implode(', ', $k['answers']),
                                            'heading' => $k['answer'] !== '' ? ($k['para'] !== '' ? $k['para'] . ' → ' : '') . ($k['texts'][(int) $k['answer']] ?? '') : '',
                                            default   => $k['answer'],
                                        };
                                        if ($k['kind'] === 'two' && $k['part'] === 2) continue;
                                    @endphp
                                    <span class="rda-ans {{ trim($a) === '' ? 'is-missing' : '' }}"><b>{{ $k['kind'] === 'two' ? $q . '–' . ($q + 1) : $q }}</b> {{ trim($a) !== '' ? $a : ($k['kind'] === 'heading' && $k['para'] !== '' ? 'paragraph ' . $k['para'] . ' has no heading yet' : 'no answer yet') }}</span>
                                @endif
                            @endfor
                        </p>
                    @endif
                </article>
            @empty
                <p class="muted rda-empty">No questions for this {{ $isL ? 'part' : 'passage' }} yet. Use the buttons above to add them.</p>
            @endforelse
        </section>
    @endforeach
@endsection
