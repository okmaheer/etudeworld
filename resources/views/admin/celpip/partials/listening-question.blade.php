{{--
    One listening question.
    $q – question data   $base – form name prefix   $qid – 80…   $num – number within the conversation   $overall – number within the part
    $part – 1, 2…   $div – A/B/C (Part 1) or '' (one conversation)   $editorLabel – e.g. "Text Editor 4 [ii]"
--}}
@php
    $complete = \App\Support\CelpipListening::questionComplete($q, $part);
    $simple = \App\Support\CelpipListening::isGroupPart($part); // Parts 4–6: statement + 4 text options only
    $idBase = 'id-part-' . $part . '-' . ($div !== '' ? $div . '-' : '');
@endphp
<details class="ls-question {{ $complete ? 'is-complete' : '' }}" id="id-part-{{ $part }}-q-intro-{{ $qid }}" data-question @if ($simple) data-no-audio @endif>
    <summary>
        <span class="ls-q-num">Q{{ $overall }}</span>
        <span class="ls-q-title">
            Question {{ $num }}
            <small data-q-preview>{{ \Illuminate\Support\Str::limit($q['statement'] ?: strip_tags($q['text']), 70) ?: 'Not written yet' }}</small>
        </span>
        <span class="ls-status" data-q-status>{{ $complete ? 'Complete' : 'Incomplete' }}</span>
        @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
    </summary>

    <div class="ls-q-body">
        @unless ($simple)
        <div class="field">
            <label for="p{{ $part }}-q-{{ $qid }}-text">Text above the question <span class="muted-inline">({{ $editorLabel }})</span></label>
            <textarea id="p{{ $part }}-q-{{ $qid }}-text" name="{{ $base }}[text]" class="ls-editor ls-editor--small" rows="3"
                      placeholder="e.g. Listen to the question. You will hear it only once.">{{ $q['text'] }}</textarea>
        </div>
        @endunless

        <div class="field">
            <label for="p{{ $part }}-q-{{ $qid }}-statement">Main question statement</label>
            <input type="text" id="p{{ $part }}-q-{{ $qid }}-statement" name="{{ $base }}[statement]" value="{{ $q['statement'] }}"
                   placeholder="{{ $simple ? ([5 => 'e.g. What is the main topic being discussed?', 6 => 'e.g. What is the speaker’s main point?'][$part] ?? 'e.g. The news item is about') : 'e.g. Choose the best answer to each question.' }}" data-q-statement>
        </div>

        <div class="field">
            <label>Answer options <span class="muted-inline">– select the correct one</span></label>
            <div class="ls-options">
                @foreach ($q['options'] as $o => $opt)
                    <div class="ls-option {{ (string) $q['correct'] === (string) $o ? 'is-correct' : '' }}" data-option>
                        <label class="ls-correct" title="Mark as the correct answer">
                            <input type="radio" name="{{ $base }}[correct]" value="{{ $o }}" @checked((string) $q['correct'] === (string) $o) data-q-correct>
                            <span class="ls-letter">{{ chr(65 + $o) }}</span>
                        </label>
                        <input type="text" name="{{ $base }}[options][{{ $o }}][text]" value="{{ $opt['text'] }}"
                               placeholder="Option {{ chr(65 + $o) }} text{{ $simple ? '' : ' (or add a picture)' }}" data-option-text>
                        @unless ($simple)
                        @include('admin.celpip.partials.media', [
                            'kind' => 'image', 'compact' => true, 'label' => 'Picture',
                            'name' => $base . '[options][' . $o . '][image]', 'value' => $opt['image'],
                            'id' => $idBase . 'q' . $num . '-option-' . chr(97 + $o) . '-image',
                        ])
                        @endunless
                    </div>
                @endforeach
            </div>
            <p class="hint">Click a letter to mark the correct answer (it turns green). Options can be text, a picture, or both.</p>
        </div>

        @unless ($simple)
        <div class="ls-media-row">
            @include('admin.celpip.partials.media', [
                'kind' => 'audio', 'label' => 'Question audio', 'name' => $base . '[audio]', 'value' => $q['audio'],
                'id' => $idBase . 'question-' . $num . '-audio',
            ])
            @include('admin.celpip.partials.media', [
                'kind' => 'image', 'label' => 'Question image (optional)', 'name' => $base . '[image]', 'value' => $q['image'],
                'id' => $idBase . 'question-' . $num . '-image',
            ])
        </div>
        @endunless
    </div>
</details>
