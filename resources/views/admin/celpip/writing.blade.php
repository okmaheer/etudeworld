@extends('admin.layout')

@section('title', 'Writing Questions')

@section('actions')
    <a href="{{ route('admin.celpip.index') }}" class="btn btn-sm btn-ghost">← All CELPIP Tests</a>
@endsection

@section('content')
    <div class="module-tabs">
        @foreach (\App\Models\CelpipTest::MODULES as $key => $mod)
            @if ($test->hasModule($key))
                <a href="{{ route('admin.celpip.module', [$test, $key]) }}" class="module-tab {{ $key === $module ? 'is-active' : '' }}">
                    @include('admin.partials.icon', ['name' => $mod['icon']]) {{ $mod['label'] }}
                </a>
            @endif
        @endforeach
    </div>

    <p class="module-context">
        <strong>{{ $test->name }}</strong>
        <span class="badge badge-blue">{{ $test->format === 'ls' ? 'LS' : 'General' }}</span>
        <span class="badge {{ $test->type === 'paid' ? 'badge-amber' : 'badge-teal' }}">{{ ucfirst($test->type) }}</span>
        <span class="badge {{ $test->status === 'active' ? 'badge-green' : 'badge-grey' }}">{{ ucfirst($test->status) }}</span>
    </p>

    <form method="POST" action="{{ route('admin.celpip.module.save', [$test, $module]) }}" id="writing-form" data-dirty-guard>
        @csrf @method('PUT')

        @foreach (\App\Models\CelpipTest::WRITING_TASKS as $key => $task)
            <section class="panel task-panel">
                <div class="task-head">
                    <span class="task-num">{{ $loop->iteration }}</span>
                    <div>
                        <h2>{{ $task['title'] }}</h2>
                        <p class="muted">{{ $task['subtitle'] }}</p>
                    </div>
                    <div class="task-meta">
                        <span class="chip-static">⏱ {{ $task['time'] }}</span>
                        <span class="chip-static">✎ {{ $task['words'] }}</span>
                    </div>
                </div>
                <div class="writing-editor" id="id-39-{{ $key }}">
                    <label for="{{ $key }}" class="writing-editor__label">
                        <span class="option-badge">1</span> Text Editor 1
                        <span class="muted-inline">– shown on the <strong>left</strong> side of the screen</span>
                    </label>
                    <textarea id="{{ $key }}" name="{{ $key }}" class="task-editor" rows="14">{{ old($key, $content[$key] ?? '') }}</textarea>
                    @error($key) <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="writing-editor" id="id-40-{{ $key }}">
                    <label for="{{ $key }}_note" class="writing-editor__label">
                        <span class="option-badge">2</span> Text Editor 2
                        <span class="muted-inline">– shown on the <strong>right</strong> side, above the student's response box (optional)</span>
                    </label>
                    <textarea id="{{ $key }}_note" name="{{ $key }}_note" class="option-editor note-editor" rows="5">{{ old($key . '_note', $content[$key . '_note'] ?? '') }}</textarea>
                    @error($key . '_note') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                @if ($key === 'task2')
                    <div class="options-head">
                        <h3>Options</h3>
                        <p class="muted">On the website, the student reads both options, chooses one, and writes their answer about the option they chose.</p>
                    </div>
                    <div class="options-grid">
                        @foreach ([1, 2] as $n)
                            <div class="option-box option-box--{{ $n }}">
                                <label for="task2_option{{ $n }}" class="option-label">
                                    <span class="option-badge">{{ $n }}</span> Option {{ $n }}
                                </label>
                                <textarea id="task2_option{{ $n }}" name="task2_option{{ $n }}" class="option-editor" rows="6">{{ old('task2_option' . $n, $content['task2_option' . $n] ?? '') }}</textarea>
                                @error('task2_option' . $n) <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach

        <div class="save-bar">
            <span class="muted" data-save-status>
                @if (! empty($content)) All changes saved. @else Nothing saved yet. @endif
            </span>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('js/editor-paste-clean.js') }}?v=1"></script>
    <script src="{{ asset('js/editor-blocks.js') }}?v=5"></script>
    <script>
        tinymce.init({
            selector: '.task-editor',
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
            placeholder: 'Type the question / instructions for this task here…',
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
            setup: (editor) => {
                EtudeEditorBlocks.basics(editor);
                editor.on('input change undo redo', () => window.markDirty && window.markDirty());
            }
        });

        // Smaller editors for Task 2's Option 1 and Option 2.
        tinymce.init({
            selector: '.option-editor',
            license_key: 'gpl',
            promotion: false,
            branding: false,
            menubar: false,
            plugins: 'lists link autolink wordcount autoresize',
            toolbar: 'bold italic underline | bullist numlist | link | removeformat',
            contextmenu: 'bold italic underline | link',
            contextmenu_never_use_native: true,
            min_height: 180,
            autoresize_bottom_margin: 20,
            convert_urls: false,
            content_style: 'body{font-family:"Plus Jakarta Sans",system-ui,sans-serif;font-size:15px;line-height:1.65;color:#1c2333;margin:12px 14px}',
            placeholder: 'Type here…',
            setup: (editor) => {
                EtudeEditorBlocks.basics(editor);
                editor.on('input change undo redo', () => window.markDirty && window.markDirty());
            }
        });

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
            form.addEventListener('submit', () => { dirty = false; });
        })();
    </script>
@endpush
