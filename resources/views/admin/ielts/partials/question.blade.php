{{-- One Part 1 / Part 3 question: audio (what the student hears) + optional text (for the admin and the teacher). --}}
<div class="iq-item" data-q-item>
    <div class="iq-item__head">
        <span class="iq-item__title" data-q-label>Question</span>
        <span class="iq-warn" data-q-warn hidden>Needs audio – students won't hear this question yet</span>
        <button type="button" class="media-remove" data-q-remove>Remove</button>
    </div>
    @include('admin.celpip.partials.media', [
        'kind' => 'audio', 'label' => 'Question audio (students hear this)', 'id' => 'ielts-p' . $p . '-q' . $key . '-audio',
        'name' => 'speaking[parts][' . $p . '][questions][' . $key . '][audio]', 'value' => $q['audio'], 'compact' => true,
    ])
    <div class="field iq-text">
        <label for="ielts-p{{ $p }}-q{{ $key }}">Question text <span class="muted-inline">(optional – not shown to students)</span></label>
        <textarea id="ielts-p{{ $p }}-q{{ $key }}" name="speaking[parts][{{ $p }}][questions][{{ $key }}][text]" class="ls-editor ls-editor--small" rows="3">{{ $q['text'] }}</textarea>
    </div>
</div>
