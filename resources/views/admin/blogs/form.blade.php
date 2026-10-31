@extends('admin.layout')

@php
    $editing = $blog->exists;
    $siteUrl = rtrim(config('app.url'), '/');
@endphp

@section('title', $editing ? 'Edit Blog' : 'Blogs')

@section('actions')
    <a href="{{ route('admin.blogs.index') }}" class="btn btn-sm btn-ghost">All blogs</a>
@endsection

@section('content')
    <div class="tabs">
        <a href="{{ route('admin.blogs.create') }}" class="tab {{ $editing ? '' : 'is-active' }}">{{ $editing ? '+ New blog' : 'New blog' }}</a>
        @if ($editing)
            <span class="tab is-active">Editing: {{ \Illuminate\Support\Str::limit($blog->title, 40) }}</span>
        @endif
        <a href="{{ route('admin.blogs.index') }}" class="tab">All blogs</a>
    </div>

    <form method="POST" enctype="multipart/form-data" id="blog-form" data-seo-form
          action="{{ $editing ? route('admin.blogs.update', $blog) : route('admin.blogs.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="editor-layout">
            {{-- ═════════ Main column ═════════ --}}
            <div class="editor-main">
                <section class="panel">
                    <div class="field">
                        <label for="title">Title <span class="req">*</span></label>
                        <input id="title" name="title" type="text" required maxlength="255" class="input-lg"
                               value="{{ old('title', $blog->title) }}" placeholder="e.g. 10 Tips to Score Band 8 in IELTS Writing" data-slug-source>
                        @error('title') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label for="slug">URL <span class="req">*</span></label>
                            <div class="url-input">
                                @php $catInfo = \App\Models\Blog::allCategories()[old('category', $blog->category)] ?? null; @endphp
                                <span class="url-prefix" data-url-prefix>/{{ $catInfo['path'] ?? 'blog/category' }}/</span>
                                <input id="slug" name="slug" type="text" maxlength="255"
                                       value="{{ old('slug', $blog->slug) }}" placeholder="auto-made-from-title"
                                       data-slug-target {{ $editing ? 'data-slug-locked' : '' }}>
                            </div>
                            <p class="hint">Filled in automatically from the title. Short, lowercase, words separated by dashes.</p>
                            @error('slug') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="field">
                            <label for="category">Category <span class="req">*</span></label>
                            <select id="category" name="category" required data-category>
                                <option value="" disabled {{ old('category', $blog->category) ? '' : 'selected' }}>Choose…</option>
                                @foreach (\App\Models\Blog::categoryGroups() as $group => $cats)
                                    <optgroup label="{{ $group }}">
                                        @foreach ($cats as $value => $cat)
                                            <option value="{{ $value }}" data-path="{{ $cat['path'] }}" @selected(old('category', $blog->category) === $value)>{{ $cat['label'] }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="hint">Choose a blog topic, or a <strong>Sample Answers</strong> section (e.g. IELTS › Speaking › Part 1).</p>
                            @error('category') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Sample answers only: the topic used by the topic filter on the Sample Answers pages --}}
                    @php
                        $sampleTopics = [];
                        foreach (\App\Support\SampleAnswers::hubs() as $hub) {
                            $sampleTopics['sa-' . implode('-', array_slice(explode('/', $hub['path']), 1))] = \App\Models\Blog::topicsFor('sa-' . implode('-', array_slice(explode('/', $hub['path']), 1)));
                        }
                    @endphp
                    <div class="field" data-topic-field hidden @if (! \App\Models\Blog::hasTopicColumn()) data-off @endif>
                        <label for="topic">Topic</label>
                        <input id="topic" name="topic" type="text" maxlength="80" list="topic-list" autocomplete="off"
                               value="{{ old('topic', $blog->topic) }}" placeholder="e.g. Transport, Health, Education">
                        <datalist id="topic-list"></datalist>
                        <p class="hint">Visitors can filter sample answers by this topic. Type it yourself, or pick one already used (the list appears as you type) so the same topic is never written two ways.</p>
                        @error('topic') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="field">
                        <label for="author_id">Author</label>
                        <div class="author-pick">
                            <select id="author_id" name="author_id">
                                <option value="">— No author —</option>
                                @foreach ($authors as $a)
                                    <option value="{{ $a->id }}" @selected((string) old('author_id', $blog->author_id) === (string) $a->id)>{{ $a->name }}</option>
                                @endforeach
                            </select>
                            <a href="{{ route('admin.authors.create') }}" target="_blank" class="btn btn-ghost btn-sm">+ New author</a>
                        </div>
                        <p class="hint">
                            @if ($authors->isEmpty())
                                No authors yet. Add one in <strong>Manage Authors</strong>, then reload this page.
                            @else
                                The author's name, bio and social links appear in a box at the end of the blog.
                            @endif
                        </p>
                        @error('author_id') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="field">
                        <label for="content">Blog text</label>
                        <p class="hint">Tip: your blog title is already shown as the main heading (H1) on the page, so use <strong>Heading 2</strong> for sections and <strong>Heading 3</strong> for sub-sections.
                            <strong>Right-click</strong> anywhere in the text for quick options (Bold, headings, FAQ, Button).
                            In a FAQ block, type straight into the questions and answers: press <strong>Enter</strong> after a question to jump to its answer,
                            and press <strong>Enter twice</strong> at the end of an answer to start a new question. Double-click a button to change it.</p>
                        <textarea id="content" name="content" rows="20">{{ old('content', $blog->content) }}</textarea>
                    </div>
                </section>

                {{-- SEO --}}
                <section class="panel">
                    <div class="panel-head"><h2>SEO settings</h2></div>

                    <div class="field">
                        <label for="meta_title">Meta title</label>
                        <input id="meta_title" name="meta_title" type="text" maxlength="255"
                               value="{{ old('meta_title', $blog->meta_title) }}" placeholder="Leave empty to use the blog title"
                               data-counter="60" data-preview="title">
                        <div class="meter"><span data-meter-for="meta_title"></span></div>
                        <p class="hint" data-count-for="meta_title">Best length: 50–60 characters.</p>
                    </div>

                    <div class="field">
                        <label for="meta_description">Meta description</label>
                        <textarea id="meta_description" name="meta_description" rows="3" maxlength="500"
                                  placeholder="A short summary that appears under the title in Google results"
                                  data-counter="160" data-preview="description">{{ old('meta_description', $blog->meta_description) }}</textarea>
                        <div class="meter"><span data-meter-for="meta_description"></span></div>
                        <p class="hint" data-count-for="meta_description">Best length: 120–160 characters.</p>
                    </div>

                    <div class="field">
                        <label for="canonical_url">Canonical URL</label>
                        <input id="canonical_url" name="canonical_url" type="url" maxlength="255"
                               value="{{ old('canonical_url', $blog->canonical_url) }}"
                               placeholder="Leave empty to use this blog's own address">
                        <p class="hint">Only fill this if the same article also exists at another address and you want Google to treat that one as the original.</p>
                        @error('canonical_url') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <p class="label-sm">Google search preview</p>
                    <div class="serp" data-serp data-site="{{ $siteUrl }}">
                        <div class="serp-url"><span class="serp-favicon"></span><span data-serp-url>{{ $siteUrl }}/blog/…</span></div>
                        <div class="serp-title" data-serp-title>Your blog title</div>
                        <div class="serp-desc" data-serp-desc>Your meta description will appear here.</div>
                    </div>
                </section>
            </div>

            {{-- ═════════ Side column ═════════ --}}
            <aside class="editor-side">
                <section class="panel">
                    <div class="field">
                        <label>Status</label>
                        <div class="segmented">
                            @foreach (\App\Models\Blog::STATUSES as $value => $label)
                                <label>
                                    <input type="radio" name="status" value="{{ $value }}" @checked(old('status', $blog->status) === $value)>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="hint">Drafts are not shown on the live website.</p>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">{{ $editing ? 'Save changes' : 'Save blog' }}</button>
                    @if ($editing)
                        <a href="{{ url($blog->public_path) }}" target="_blank" class="btn btn-ghost btn-block" style="margin-top:10px">
                            {{ $blog->status === 'published' ? 'View on website' : 'Preview draft' }}
                        </a>
                        <p class="hint center">Last saved {{ $blog->updated_at->diffForHumans() }}</p>
                    @endif
                </section>

                <section class="panel">
                    <div class="panel-head"><h2>Blog image</h2></div>
                    <label class="dropzone {{ $blog->image ? 'has-image' : '' }}" data-dropzone>
                        <img src="{{ $blog->image ? asset($blog->image) : '' }}" alt="" data-image-preview>
                        <span class="dz-empty">
                            @include('admin.partials.icon', ['name' => 'image'])
                            <strong>Click to choose an image</strong>
                            <small>JPG, PNG or WebP · max 4 MB<br>Best size: 1200 × 630 px</small>
                        </span>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" data-image-input>
                    </label>
                    @error('image') <p class="field-error">{{ $message }}</p> @enderror
                    @if ($blog->image)
                        <label class="check"><input type="checkbox" name="remove_image" value="1"> Remove current image</label>
                    @endif
                    <div class="field">
                        <label for="image_alt">Image description (alt text)</label>
                        <input id="image_alt" name="image_alt" type="text" maxlength="255"
                               value="{{ old('image_alt', $blog->image_alt) }}" placeholder="e.g. Student practising IELTS writing">
                        <p class="hint">Helps Google and visually impaired readers understand the picture.</p>
                    </div>
                    <p class="hint">Used on the blog card, as the big image at the top of the blog, and as the preview picture when the link is shared on social media.</p>

                    <p class="label-sm">Social share preview</p>
                    <div class="social-card">
                        <div class="social-img" data-social-img style="{{ $blog->image ? 'background-image:url(' . asset($blog->image) . ')' : '' }}"></div>
                        <div class="social-body">
                            <small>{{ parse_url($siteUrl, PHP_URL_HOST) }}</small>
                            <strong data-social-title>Your blog title</strong>
                            <span data-social-desc>Your meta description</span>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head"><h2>SEO checklist</h2></div>
                    <ul class="checklist" data-seo-checklist>
                        <li data-check="title">Title added</li>
                        <li data-check="metaTitle">Meta title is 30–60 characters</li>
                        <li data-check="metaDesc">Meta description is 120–160 characters</li>
                        <li data-check="words">Blog text has at least 300 words <em data-word-count></em></li>
                        <li data-check="h1">No extra H1 inside the text (the title is the H1)</li>
                        <li data-check="h2">Uses H2 headings to split sections</li>
                        <li data-check="alt">All images in the text have a description</li>
                        <li data-check="image">Blog image added</li>
                    </ul>
                    <p class="label-sm">Heading outline</p>
                    <ol class="outline" data-outline><li class="muted">Headings you add will show here.</li></ol>
                </section>

                @if ($editing)
                    <section class="panel danger-zone">
                        <button type="submit" form="delete-blog" class="btn btn-danger btn-block"
                                data-confirm="Delete this blog permanently? This cannot be undone.">Delete blog</button>
                    </section>
                @endif
            </aside>
        </div>
    </form>

    @if ($editing)
        <form id="delete-blog" method="POST" action="{{ route('admin.blogs.destroy', $blog) }}" hidden>
            @csrf @method('DELETE')
        </form>
    @endif
@endsection

@push('scripts')
    {{-- One shared text editor (TinyMCE, free open-source version): see public/js/rich-editor.js --}}
    <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('js/editor-paste-clean.js') }}?v=1"></script>
    <script src="{{ asset('js/editor-blocks.js') }}?v=5"></script>
    <script src="{{ asset('js/rich-editor.js') }}?v=1"></script>
    <script>EtudeRichEditor.init({ selector: '#content', height: 640 });</script>
    <script>
        // Topic box: only for Sample Answers categories, and it suggests the topics already used by the same exam + skill.
        (function () {
            const topics = @json($sampleTopics ?? []);
            const select = document.getElementById('category');
            const field = document.querySelector('[data-topic-field]');
            const list = document.getElementById('topic-list');
            if (!select || !field) return;
            function refresh() {
                const cat = select.value || '';
                const isSample = cat.indexOf('sa-') === 0;
                field.hidden = !isSample || field.hasAttribute('data-off');
                list.innerHTML = '';
                // "sa-ielts-speaking-part-1" → topics of "sa-ielts-speaking"
                const hub = cat.replace(/-(part|task)-\d+$/, '');
                (topics[hub] || []).forEach(t => { const o = document.createElement('option'); o.value = t; list.appendChild(o); });
            }
            select.addEventListener('change', refresh);
            refresh();
        })();
    </script>
@endpush
