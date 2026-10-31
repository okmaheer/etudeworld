@extends('admin.layout')

@section('title', 'Courses')

@section('actions')
    <a href="{{ route('admin.seo.index', ['tab' => 'site-courses']) }}" class="btn btn-sm btn-ghost">Page title &amp; description (SEO)</a>
    <a href="{{ route('admin.courses.create') }}" class="btn btn-sm btn-primary">+ Add a course</a>
@endsection

@section('content')
    <p class="muted" style="margin-bottom:16px">These are the cards on the <a href="{{ route('courses.index') }}" target="_blank" rel="noopener">/courses</a> page. “Purchase Now” opens the payments page (until it exists, the contact page).</p>

    @if ($courses->isEmpty())
        <form method="POST" action="{{ route('admin.courses.seed') }}" class="panel" style="margin-bottom:20px">
            @csrf
            <p style="margin-top:0">There are no courses yet. One click adds the standard set: for IELTS, CELPIP and TOEFL, the Listening, Reading, Writing and Speaking courses, the Complete Preparation course, the Computer Based Mock Tests and the Recorded Lessons. You can edit all of them afterwards.</p>
            <button type="submit" class="btn btn-primary">Add the standard courses</button>
        </form>
    @endif

    @foreach (\App\Models\Course::EXAMS as $key => $label)
        <section class="panel no-pad" style="margin-bottom:20px">
            <div class="panel-head" style="padding:16px 20px;display:flex;justify-content:space-between;align-items:center">
                <h2>{{ $label }} courses</h2>
                <a href="{{ route('admin.courses.create', ['exam' => $key]) }}" class="btn btn-sm btn-ghost">+ Add {{ $label }} course</a>
            </div>
            @forelse ($courses[$key] ?? [] as $c)
                <div class="blog-row">
                    <div class="blog-info">
                        <a href="{{ route('admin.courses.edit', $c) }}" class="blog-title">{{ $c->title }}</a>
                        <div class="meta-line">
                            <span class="badge badge-blue">{{ $c->priceLabel() }}</span>
                            @if ($c->is_featured) <span class="badge badge-teal">Highlighted</span> @endif
                            <span class="badge {{ $c->is_published ? 'badge-green' : 'badge-grey' }}">{{ $c->is_published ? 'Shown' : 'Hidden' }}</span>
                            <span class="muted">order {{ $c->sort_order }}</span>
                        </div>
                    </div>
                    <div class="row-actions">
                        <a href="{{ route('admin.courses.edit', $c) }}" class="icon-btn" title="Edit" aria-label="Edit {{ $c->title }}">@include('admin.partials.icon', ['name' => 'edit'])</a>
                        <form method="POST" action="{{ route('admin.courses.destroy', $c) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="icon-btn icon-btn--danger" title="Delete" aria-label="Delete {{ $c->title }}" data-confirm="Delete “{{ $c->title }}”?">@include('admin.partials.icon', ['name' => 'trash'])</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="empty-state"><p class="muted">No {{ $label }} courses yet. This block stays hidden on the website until you add one.</p></div>
            @endforelse
        </section>
    @endforeach

    <form method="POST" action="{{ route('admin.courses.sections') }}" class="panel">
        @csrf
        <div class="panel-head"><h2>Block headings on the Courses page</h2></div>
        <p class="hint" style="margin-top:0">The title and text above each group of cards. Leave a box empty to use the standard wording.</p>
        @foreach (\App\Models\Course::EXAMS as $key => $label)
            <div class="field-row">
                <div class="field">
                    <label for="st-{{ $key }}">{{ $label }} – title</label>
                    <input id="st-{{ $key }}" name="sections[{{ $key }}][title]" type="text" maxlength="120" value="{{ old("sections.$key.title", $sections[$key]['title']) }}">
                </div>
                <div class="field" style="flex:2">
                    <label for="sx-{{ $key }}">{{ $label }} – text</label>
                    <input id="sx-{{ $key }}" name="sections[{{ $key }}][text]" type="text" maxlength="400" value="{{ old("sections.$key.text", $sections[$key]['text']) }}">
                </div>
            </div>
        @endforeach
        <button type="submit" class="btn btn-primary">Save headings</button>
    </form>
@endsection
