@extends('admin.layout')

{{--
    Reading: "FITB – Label". Passage, instructions, picture, click the picture to place numbered points, and one answer per point.
    public/js/admin-ielts-fitblabel.js sends everything as one JSON value ("group_json") to the normal save address.
--}}
@php
    $rdaData = ['group' => $group, 'starts' => $starts, 'imageUrl' => route('admin.ielts.' . $module . '.image', $test), 'imageSrc' => ! empty($group['image']) ? asset($group['image']) : ''];
@endphp

@section('title', ($isNew ? '' : 'Edit: ') . $info['button'])

@section('actions')
    <a href="{{ route('admin.ielts.' . $module, $test) }}" class="btn btn-sm btn-ghost">← Reading questions</a>
@endsection

@section('content')
    @include('admin.ielts.reading._head')

    <form method="POST" action="{{ route('admin.ielts.' . $module . '.save', $test) }}" id="fitblabel-form">
        @csrf
        <input type="hidden" name="group_json" data-group-json>
        <script type="application/json" data-rda-data>@json($rdaData)</script>

        <section class="panel rda-step">
            <h2><span class="option-badge">1</span> Which passage are these questions for?</h2>
            <div class="rda-passages">
                @foreach ($R::PARTS as $p)
                    <label class="rda-radio">
                        <input type="radio" name="passage" value="{{ $p }}" @checked($group['passage'] === $p) data-passage>
                        <span>Passage {{ $p }} <small class="muted">questions start at {{ $starts[$p] }}</small></span>
                    </label>
                @endforeach
            </div>
        </section>

        <section class="panel rda-step">
            <h2><span class="option-badge">2</span> Question instructions</h2>
            <p class="muted">Shown at the top, above the picture. Filled in by default – change them as needed.</p>
            <textarea id="rda-instructions" class="rda-editor" rows="4">{{ $group['instructions'] }}</textarea>
        </section>

        <section class="panel rda-step">
            <h2><span class="option-badge">3</span> Picture and points</h2>
            <p class="muted">Add the diagram, then <strong>click on the picture</strong> where each question number should appear. Each click adds one numbered point, and each point is one question worth one mark. Drag a point to move it. The number box sits on the point and the answer box is shown next to it.</p>
            <div class="rda-image" data-map-box>
                <input type="file" accept="image/*" data-map-file>
                <button type="button" class="btn btn-sm btn-ghost" data-map-remove @if (empty($group['image'])) hidden @endif>Remove image</button>
                <span class="muted" data-map-status></span>
            </div>
            <div class="rda-pinpad" data-pad hidden>
                <img data-map-img alt="" draggable="false">
            </div>
            <p class="muted" data-pointer>Click the picture to place a point.</p>
        </section>

        <section class="panel rda-step">
            <h2><span class="option-badge">4</span> Answers</h2>
            <p class="muted">One box for each point, in the same order as the numbers on the picture. To accept more than one spelling, separate them with a slash, e.g. <strong>colour / color</strong>.</p>
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
    <script src="{{ asset('js/admin-ielts-fitblabel.js') }}?v=1"></script>
@endpush
