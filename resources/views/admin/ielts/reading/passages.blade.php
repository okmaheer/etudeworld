@extends('admin.layout')

{{-- [id-3] The three reading passages / sections. --}}
@php $isL = $module === 'listening'; @endphp
@section('title', $isL ? 'Listening Transcripts' : 'Reading Passages / Sections')

@section('actions')
    <a href="{{ route('admin.ielts.' . $module, $test) }}" class="btn btn-sm btn-ghost">← {{ $isL ? 'Listening' : 'Reading' }} questions</a>
@endsection

@section('content')
    @include('admin.ielts.reading._head')

    @if ($isL)
        <div class="panel rda-help"><p><strong>Transcripts</strong> are never shown to students. They are only used by the Vocabulary Builder to find the difficult words. Paste the transcript of each part (you can leave a part empty).</p></div>
    @else
    <div class="panel rda-help">
        <p><strong>Paragraph letters:</strong> for <em>Headings</em> and <em>Matching</em> questions, start a paragraph with its letter in square brackets,
            for example <code>[A] In our modern lives…</code>. Students see a bold <strong>A</strong>; with Headings questions, an empty box appears above each lettered paragraph.</p>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.ielts.' . $module . '.passages.save', $test) }}" id="passages-form" data-dirty-form>
        @csrf @method('PUT')
        @foreach ($R::PARTS as $p)
            <section class="panel task-panel" id="passage-{{ $p }}">
                <div class="task-head">
                    <span class="task-num">{{ $p }}</span>
                    <div>
                        <h2>{{ $isL ? 'Transcript – Part' : 'Passage / Section' }} {{ $p }}</h2>
                        <p class="muted">@if ($isL) Used only for finding vocabulary. @else Shown on the left side of the student's screen. Students move between passages with the arrow buttons. @endif</p>
                    </div>
                </div>
                <textarea name="passages[{{ $p }}]" id="passage-editor-{{ $p }}" class="rda-editor rda-editor--big" rows="18">{{ old('passages.' . $p, $content['passages'][$p]) }}</textarea>
            </section>
        @endforeach

        <div class="save-bar">
            <span class="muted" data-save-status>Click Save to keep your changes.</span>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('js/editor-paste-clean.js') }}?v=1"></script>
    <script src="{{ asset('js/editor-blocks.js') }}?v=5"></script>
    <script>window.EW_EDITOR_IMAGE_URL = @json(route('admin.blogs.editor-image'));</script>
    <script src="{{ asset('js/admin-ielts-reading.js') }}?v=1"></script>
@endpush
