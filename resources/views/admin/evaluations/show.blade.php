@extends('admin.layout')

@section('title', $label . ' Evaluation')

@section('actions')
    <a href="{{ route('admin.evaluations.index', $exam) }}" class="btn btn-sm btn-ghost">← All submissions</a>
@endsection

@section('content')
    @php $s = $submission; @endphp
    <div class="panel ev-head">
        <div>
            <h2>{{ $s->student_name }} <small class="muted">· <a href="mailto:{{ $s->student_email }}">{{ $s->student_email }}</a></small></h2>
            <p class="muted">{{ $s->test_name }} · {{ \App\Models\TestSubmission::MODULES[$s->module] }} · {{ $s->mode === 'mock' ? 'Full mock test' : 'Practice' }}
                · submitted {{ $s->created_at->format('M j, Y \a\t g:i a') }}</p>
        </div>
        @if ($s->isEvaluated()) <span class="badge badge-green">Evaluated</span> @else <span class="badge badge-amber">Waiting for evaluation</span> @endif
    </div>

    <div class="ev-grid">
        <div class="ev-answers">
            @forelse ($s->answers ?? [] as $key => $a)
                <section class="panel ev-item">
                    <h3>{{ $a['title'] ?? $key }}</h3>
                    <div class="ev-question">
                        <p class="ev-label">Question</p>
                        <div class="ev-html">{!! $a['question'] ?? '' !!}</div>
                        @if (! empty($a['question_audio']))
                            <audio controls preload="none" src="{{ asset($a['question_audio']) }}"></audio>
                        @endif
                        @if (! empty($a['note'])) <div class="ev-html ev-note">{!! $a['note'] !!}</div> @endif
                        @if (! empty($a['image']))
                            <img class="ev-img" src="{{ asset($a['image']) }}" alt="">
                        @endif
                        @if (! empty($a['choice']))
                            <p class="ev-label">Option chosen: {{ $a['choice'] }} @if (! empty($a['auto_chosen'])) <span class="badge badge-amber">chosen by the computer (time ran out)</span> @endif</p>
                            @if (! empty($a['choice_image'])) <img class="ev-img" src="{{ asset($a['choice_image']) }}" alt=""> @endif
                            @if (! empty($a['option'])) <div class="ev-html ev-note">{!! $a['option'] !!}</div> @endif
                            @if (! empty($a['choice_text'])) <div class="ev-html ev-note">{!! $a['choice_text'] !!}</div> @endif
                        @endif
                    </div>

                    @if ($s->module === 'writing')
                        <p class="ev-label">Student's answer @php $min = $a['min'] ?? ($exam === 'toefl' ? 0 : 150); $max = $a['max'] ?? ($exam === 'toefl' ? 100000 : 200); @endphp
                            <span class="badge {{ ($a['words'] ?? 0) >= $min && ($a['words'] ?? 0) <= $max ? 'badge-green' : 'badge-amber' }}">{{ $a['words'] ?? 0 }} words</span>
                            @isset($a['min']) <span class="muted-inline">({{ $a['min'] }}–{{ $a['max'] }} required)</span> @endisset</p>
                        <div class="ev-answer">{!! nl2br(e($a['answer'] ?? '')) ?: '<em>No answer written.</em>' !!}</div>
                    @else
                        <p class="ev-label">Student's recording</p>
                        @if (! empty($a['file']))
                            <audio controls preload="metadata" src="{{ route('admin.evaluations.audio', [$exam, $s, $a['file']]) }}"></audio>
                            <p><a href="{{ route('admin.evaluations.audio', [$exam, $s, $a['file']]) }}" download="{{ \Illuminate\Support\Str::slug($s->student_name) }}-{{ $a['file'] }}">Download the recording</a></p>
                        @else
                            <p class="muted"><em>No recording was received for this {{ in_array($exam, ['ielts', 'toefl'], true) ? 'question' : 'task' }}.</em></p>
                        @endif
                    @endif
                </section>
            @empty
                <div class="panel"><p class="muted">No answers in this submission.</p></div>
            @endforelse
        </div>

        <aside class="panel ev-form">
            <h3>Your evaluation</h3>
            <form method="POST" action="{{ route('admin.evaluations.update', [$exam, $s]) }}">
                @csrf @method('PUT')
                <div class="field">
                    <label for="score">Score / level</label>
                    <input type="text" id="score" name="score" value="{{ old('score', $s->score) }}" placeholder="{{ $exam === 'ielts' ? 'e.g. Band 6.5' : ($exam === 'toefl' ? 'e.g. 4.5 or 24/30' : 'e.g. CLB 8 or 9/12') }}">
                </div>
                <div class="field">
                    <label for="feedback">Feedback</label>
                    <textarea id="feedback" name="feedback" rows="10" placeholder="Notes for the student…">{{ old('feedback', $s->feedback) }}</textarea>
                </div>
                <div class="field">
                    <input type="hidden" name="status" value="pending">
                    <label class="ev-check"><input type="checkbox" name="status" value="evaluated" @checked(old('status', $s->status) === 'evaluated')> Mark as evaluated</label>
                </div>
                <button type="submit" class="btn btn-primary">Save evaluation</button>
                @if ($s->evaluated_at) <p class="muted ev-small">Evaluated {{ $s->evaluated_at->format('M j, Y') }}</p> @endif
            </form>
            <form method="POST" action="{{ route('admin.evaluations.destroy', [$exam, $s]) }}" onsubmit="return confirm('Delete this submission and its recordings? This cannot be undone.')" class="ev-delete">
                @csrf @method('DELETE')
                <button type="submit" class="media-remove">Delete this submission</button>
            </form>
        </aside>
    </div>
@endsection
