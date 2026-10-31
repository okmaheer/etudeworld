@extends('admin.layout')

@php use App\Support\IeltsWriting as W; @endphp

@section('title', 'IELTS Writing Questions')

@section('actions')
    @if ($test->moduleReady('writing'))
        <a href="{{ route('ielts.practice.test', [$test->formatPath(), 'writing', $test->slug]) }}" class="btn btn-sm btn-ghost" target="_blank" rel="noopener">View on website ↗</a>
    @endif
    <a href="{{ route('admin.ielts.index') }}" class="btn btn-sm btn-ghost">← All IELTS Tests</a>
@endsection

@section('content')
    <div class="module-tabs">
        @foreach ($test->builtModules() as $key)
            <a href="{{ $test->adminModuleUrl($key) }}" class="module-tab {{ $key === $module ? 'is-active' : '' }}">
                @include('admin.partials.icon', ['name' => \App\Models\IeltsTest::MODULES[$key]['icon']]) {{ \App\Models\IeltsTest::MODULES[$key]['label'] }}
            </a>
        @endforeach
    </div>

    <p class="module-context">
        <strong>{{ $test->name }}</strong>
        <span class="badge badge-blue">{{ $test->formatShort() }}</span>
        <span class="badge {{ $test->type === 'paid' ? 'badge-amber' : 'badge-teal' }}">{{ ucfirst($test->type) }}</span>
        <span class="badge {{ $test->status === 'active' ? 'badge-green' : 'badge-grey' }}">{{ ucfirst($test->status) }}</span>
    </p>

    <p class="hint">Students have <strong>1 hour</strong> in total for both tasks and can switch between them at any time.</p>

    <form method="POST" action="{{ route('admin.ielts.module.save', [$test, 'writing']) }}" id="writing-form">
        @csrf @method('PUT')

        @foreach (W::TASKS as $key => $task)
            <section class="panel task-panel">
                <div class="task-head">
                    <span class="task-num">{{ $loop->iteration }}</span>
                    <div>
                        <h2>{{ $task['title'] }}</h2>
                        <p class="muted">{{ W::subtitle($key, $test->format) }}</p>
                    </div>
                    <div class="task-meta">
                        <span class="chip-static">✎ {{ $task['min'] }}–{{ $task['max'] }} words</span>
                    </div>
                </div>
                <div class="writing-editor">
                    <label for="{{ $key }}" class="writing-editor__label">
                        Question
                        <span class="muted-inline">– shown on the <strong>left</strong> side of the student's screen; the right side is their answer box{{ $key === 'task1' && $test->format !== 'gt' ? ' (add the graph / chart / diagram with the picture button)' : '' }}</span>
                    </label>
                    <textarea id="{{ $key }}" name="{{ $key }}" class="task-editor" rows="14"
                              data-placeholder="{{ W::placeholder($key, $test->format) }}">{{ old($key, $content[$key]) }}</textarea>
                </div>

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
    <script>
        const ewSetup = editor => {
            EtudeEditorBlocks.basics(editor);
            editor.on('input change undo redo', () => window.markDirty && window.markDirty());
        };
        document.querySelectorAll('.task-editor').forEach(el => tinymce.init({
            target: el,
            license_key: 'gpl',
            promotion: false,
            branding: false,
            menubar: 'edit insert format table',
            plugins: 'advlist autolink lists link image charmap table wordcount autoresize code',
            toolbar: 'undo redo | blocks | bold italic underline | forecolor | alignleft aligncenter alignright | bullist numlist | link image table | removeformat code',
            toolbar_mode: 'wrap',
            block_formats: 'Paragraph=p; Heading 3=h3; Heading 4=h4',
            contextmenu: 'bold italic underline | link image table',
            contextmenu_never_use_native: true,
            min_height: 320,
            autoresize_bottom_margin: 30,
            convert_urls: false,
            image_caption: true,
            content_style: 'body{font-family:"Plus Jakarta Sans",system-ui,sans-serif;font-size:16px;line-height:1.7;color:#1c2333;margin:14px 18px} h3,h4{color:#13234a} img{max-width:100%;height:auto} table{border-collapse:collapse} td,th{border:1px solid #d4dbe8;padding:6px 10px}',
            placeholder: el.dataset.placeholder,
            images_upload_handler: (blobInfo) => new Promise((resolve, reject) => {
                const data = new FormData();
                data.append('file', blobInfo.blob(), blobInfo.filename());
                fetch(@json(route('admin.blogs.editor-image')), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: data
                })
                .then(r => r.ok ? r.json() : r.json().then(j => Promise.reject(j.message || 'Upload failed')))
                .then(j => resolve(j.location))
                .catch(err => reject({ message: String(err), remove: true }));
            }),
            setup: ewSetup,
        }));

        // Warn before leaving the page with unsaved changes.
        (function () {
            const form = document.getElementById('writing-form');
            const status = document.querySelector('[data-save-status]');
            let dirty = false;
            window.markDirty = () => {
                if (dirty) return;
                dirty = true;
                status.textContent = 'You have unsaved changes.';
                status.classList.add('is-dirty');
            };
            window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
            form.addEventListener('submit', () => { tinymce.triggerSave(); dirty = false; });
        })();
    </script>
@endpush
