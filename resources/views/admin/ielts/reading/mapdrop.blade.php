@extends('admin.layout')

{{--
    Listening: "Map Labelling – Drop Ver". Part, instructions, picture, click the picture to place points, and one answer per point.
    public/js/admin-ielts-mapdrop.js sends everything as one JSON value ("group_json") to the normal save address.
--}}
@php
    $rdaData = ['group' => $group, 'imageUrl' => route('admin.ielts.listening.image', $test), 'imageSrc' => ! empty($group['image']) ? asset($group['image']) : ''];
@endphp

@section('title', ($isNew ? '' : 'Edit: ') . $info['button'])

@section('actions')
    <a href="{{ route('admin.ielts.' . $module, $test) }}" class="btn btn-sm btn-ghost">← Listening questions</a>
@endsection

@section('content')
    @include('admin.ielts.reading._head')

    <form method="POST" action="{{ route('admin.ielts.' . $module . '.save', $test) }}" id="mapdrop-form" data-type="mapdrop">
        @csrf
        <input type="hidden" name="group_json" data-group-json>
        <script type="application/json" data-rda-data>@json($rdaData)</script>

        <section class="panel rda-step">
            <h2><span class="option-badge">1</span> Which part are these questions for?</h2>
            <div class="rda-passages">
                @foreach ($R::PARTS as $p)
                    <label class="rda-radio">
                        <input type="radio" name="passage" value="{{ $p }}" @checked($group['passage'] === $p) data-passage>
                        <span>Part {{ $p }} <small class="muted">questions start at {{ $starts[$p] }}</small></span>
                    </label>
                @endforeach
            </div>
        </section>

        <section class="panel rda-step">
            <h2><span class="option-badge">2</span> Question instructions</h2>
            <p class="muted">Shown on the left, above the picture. Filled in by default – change them as needed.</p>
            <textarea id="rda-instructions" class="rda-editor" rows="4">{{ $group['instructions'] }}</textarea>
        </section>

        <section class="panel rda-step">
            <h2><span class="option-badge">3</span> Picture and points</h2>
            <p class="muted">Add the map or picture, then <strong>click on the picture</strong> where each answer box should be. Each click adds a point (A, B, C…) and each point is one question worth one mark. Drag a point to move it.</p>
            <div class="rda-image" data-map-box>
                <input type="file" accept="image/*" data-map-file>
                <button type="button" class="btn btn-sm btn-ghost" data-map-remove @if (empty($group['image'])) hidden @endif>Remove image</button>
                <span class="muted" data-map-status></span>
            </div>
            <div class="rda-pinpad" data-pad hidden>
                <img data-map-img alt="" draggable="false">
            </div>
            <p class="muted" data-pointer>Click the picture to see the position of the pointer.</p>
        </section>

        <section class="panel rda-step">
            <h2><span class="option-badge">4</span> Answers</h2>
            <p class="muted">One box for each point. What you write is the answer students must drag onto that point. On the website the answers are shown on the right in a mixed-up order.</p>
            <div class="rda-items" data-points></div>
            <p class="muted" data-empty>No points yet – click on the picture above.</p>
        </section>

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
    <script src="{{ asset('js/admin-ielts-mapdrop.js') }}?v=1"></script>
@endpush
