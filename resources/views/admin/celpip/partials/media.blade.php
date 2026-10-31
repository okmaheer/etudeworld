{{--
    Audio or image upload box. The file is uploaded as soon as it is chosen;
    its saved path goes into the hidden input and is saved with the page.
    $kind  – audio | image        $name – form field name       $value – current path
    $id    – element id           $label – heading              $compact – smaller layout
--}}
@php
    $isDefault = $value && str_starts_with($value, 'audio/celpip/');
    $compact = $compact ?? false;
@endphp
<div class="media-field media-field--{{ $kind }} {{ $compact ? 'media-field--compact' : '' }} {{ $value ? 'has-file' : '' }}" id="{{ $id }}" data-media data-kind="{{ $kind }}">
    <div class="media-head">
        <span class="media-label">
            @include('admin.partials.icon', ['name' => $kind === 'audio' ? 'headphones' : 'image'])
            {{ $label }}
        </span>
        <span class="badge badge-teal" data-media-default @unless ($isDefault) hidden @endunless>Default</span>
    </div>
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-media-path>
    <div class="media-preview" data-media-preview>
        @if ($value)
            @if ($kind === 'audio')
                <audio controls preload="none" src="{{ asset($value) }}"></audio>
            @else
                <img src="{{ asset($value) }}" alt="">
            @endif
        @else
            <span class="media-empty">No {{ $kind }} yet</span>
        @endif
    </div>
    <div class="media-actions">
        <label class="btn btn-sm btn-ghost media-pick">
            <input type="file" accept="{{ $kind === 'audio' ? 'audio/*,.mp3,.wav,.m4a,.aac,.ogg' : 'image/*' }}" data-media-input hidden>
            <span data-media-pick-label>{{ $value ? 'Replace' : 'Upload' }} {{ $kind }}</span>
        </label>
        <button type="button" class="media-remove" data-media-remove @unless ($value) hidden @endunless>Remove</button>
        <span class="media-status" data-media-status></span>
    </div>
    <div class="media-progress" data-media-progress hidden><span></span></div>
</div>
