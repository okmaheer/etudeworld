{{--
    One listening part (1–6): Sections 1–2, then the conversation section with the conversation(s) and questions.
    $partNo – 1, 2…   $partName   $pc – this part's saved content   $progress – ['done','total']
    Part 1 has 3 conversation divisions (3+3+2 questions); Part 2 has one conversation with 5 questions;
    Part 3 has one conversation with 6 questions; Part 4 has a news item with 5 questions shown together.
    Part 5 has a discussion with 8 questions and Part 6 a report with 6 questions, shown together.
    Parts 3–6 have no picture in Section 2.
--}}
@php
    use App\Support\CelpipListening as L;
    $layout = L::PART_DIVISIONS[$partNo];
    $single = count($layout) === 1;
    $group  = L::isGroupPart($partNo);   // Part 4: all questions on one screen, no audio per question
    $s2Image = in_array($partNo, L::S2_IMAGE_PARTS, true);
    $title  = 'Listening Part ' . $partNo . ': ' . $partName;
    $roman  = ['i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii'];
    $n = 0;
@endphp
<details class="part-bar" id="id-part-{{ $partNo }}-35" data-part>
    <summary>
        <span class="part-bar__num">{{ $partNo }}</span>
        <span class="part-bar__title">Add Part {{ $partNo }} <small>{{ $partName }}</small></span>
        <span class="part-bar__progress {{ $progress['done'] === $progress['total'] ? 'is-done' : '' }}" data-part-progress>
            {{ $progress['done'] }} / {{ $progress['total'] }} questions complete
        </span>
        @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
    </summary>

    <div class="part-bar__body">
        {{-- Section 1 --}}
        <section class="ls-card ls-card--inner" id="id-part-{{ $partNo }}-intro-56">
            <div class="ls-card__head">
                <span class="ls-tag">Section 1</span>
                <h3>{{ $title }}</h3>
                <p class="muted">Part {{ $partNo }} introduction. Filled in by default for every test.</p>
            </div>
            <div class="field">
                <label for="part-{{ $partNo }}-intro">Text Editor 1</label>
                <textarea id="part-{{ $partNo }}-intro" name="listening[parts][{{ $partNo }}][s1][text]" class="ls-editor" rows="6">{{ $pc['s1']['text'] }}</textarea>
            </div>
            @include('admin.celpip.partials.media', [
                'kind' => 'audio', 'label' => 'Part ' . $partNo . ' introduction audio', 'id' => 'part-' . $partNo . '-intro-audio',
                'name' => 'listening[parts][' . $partNo . '][s1][audio]', 'value' => $pc['s1']['audio'],
            ])
        </section>

        {{-- Section 2 --}}
        <section class="ls-card ls-card--inner" id="id-part-{{ $partNo }}-q-intro-78">
            <div class="ls-card__head">
                <span class="ls-tag">Section 2</span>
                <h3>{{ $title }}</h3>
                <p class="muted">Different for each test, e.g. "You will hear a conversation between two co-workers…"{{ $s2Image ? ' with a picture' : '' }}.</p>
            </div>
            <div class="field">
                <label for="part-{{ $partNo }}-question-intro">Text Editor 2</label>
                <textarea id="part-{{ $partNo }}-question-intro" name="listening[parts][{{ $partNo }}][s2][text]" class="ls-editor" rows="5">{{ $pc['s2']['text'] }}</textarea>
            </div>
            <div class="ls-media-row">
                @include('admin.celpip.partials.media', [
                    'kind' => 'audio', 'label' => 'Section 2 audio', 'id' => 'part-' . $partNo . '-question-intro-audio',
                    'name' => 'listening[parts][' . $partNo . '][s2][audio]', 'value' => $pc['s2']['audio'],
                ])
                @if ($s2Image)
                @include('admin.celpip.partials.media', [
                    'kind' => 'image', 'label' => 'Section 2 image', 'id' => 'part-' . $partNo . '-question-intro-image',
                    'name' => 'listening[parts][' . $partNo . '][s2][image]', 'value' => $pc['s2']['image'],
                ])
                @endif
            </div>
        </section>


        {{-- Section 4: the conversation(s) with their questions --}}
        <section class="ls-card ls-card--inner ls-card--plain">
            <div class="ls-card__head">
                <span class="ls-tag">Section 3</span>
                <h3>Conversation and questions</h3>
                <p class="muted">
                    @if ($group)
                        The {{ [5 => 'discussion', 6 => 'report'][$partNo] ?? 'news item' }} recording, then {{ count($layout[0]['question_ids']) }} questions shown together on one screen.
                        Students get {{ intdiv(L::GROUP_PARTS[$partNo]['seconds'], 60) }} minutes for all of them.
                    @elseif ($single)
                        The conversation recording, then {{ count($layout[0]['question_ids']) }} questions. Students get 30 seconds for each question.
                    @else
                        The conversation is played in {{ count($layout) }} parts (Divisions {{ implode(', ', array_column($layout, 'label')) }}).
                        Each one has its recording, then {{ implode(', ', array_map(fn ($d) => count($d['question_ids']), $layout)) }} questions – {{ $progress['total'] }} questions in total.
                    @endif
                </p>
            </div>

            @foreach ($layout as $d => $div)
                @php $dv = $pc['divisions'][$d]; $editorNo = 4 + $d; @endphp
                <details class="ls-division" id="id-part-{{ $partNo }}-{{ $div['key'] }}" open>
                    <summary>
                        <span class="ls-division__tag">{{ $group ? ([5 => 'Discussion', 6 => 'Report'][$partNo] ?? 'News item') : ($single ? 'Conversation' : 'Division 1 [' . $div['label'] . ']') }}</span>
                        <span>{{ $single ? '' : 'Conversation part ' . ($d + 1) . ' · ' }}{{ count($div['question_ids']) }} questions</span>
                        @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
                    </summary>
                    <div class="ls-division__body">
                        <div class="field">
                            <label for="part-{{ $partNo }}-{{ $div['key'] }}-text">Text Editor {{ $editorNo }} [i] <span class="muted-inline">– shown above the recording</span></label>
                            <textarea id="part-{{ $partNo }}-{{ $div['key'] }}-text" name="listening[parts][{{ $partNo }}][divisions][{{ $d }}][text]" class="ls-editor ls-editor--small" rows="3"
                                      placeholder="e.g. Listen to the conversation. You will hear the conversation only once.">{{ $dv['text'] }}</textarea>
                        </div>
                        @include('admin.celpip.partials.media', [
                            'kind' => 'audio', 'label' => $single ? 'Conversation audio' : 'Conversation audio (Division ' . $div['label'] . ')',
                            'id' => 'part-' . $partNo . '-' . $div['key'] . '-audio',
                            'name' => 'listening[parts][' . $partNo . '][divisions][' . $d . '][audio]', 'value' => $dv['audio'],
                        ])
                        @if ($group)
                            <div class="field" style="margin-top:16px">
                                <label for="part-{{ $partNo }}-questions-intro">Instructions above the questions <span class="muted-inline">– shown at the top of the question screen</span></label>
                                <textarea id="part-{{ $partNo }}-questions-intro" name="listening[parts][{{ $partNo }}][divisions][{{ $d }}][questions_intro]" class="ls-editor ls-editor--small" rows="2">{{ $dv['questions_intro'] ?? '' }}</textarea>
                            </div>
                        @endif

                        <div class="ls-questions">
                            @foreach ($div['question_ids'] as $qi => $qid)
                                @php $n++; @endphp
                                @include('admin.celpip.partials.listening-question', [
                                    'q' => $dv['questions'][$qi], 'qid' => $qid, 'num' => $qi + 1, 'overall' => $n,
                                    'part' => $partNo, 'div' => $single ? '' : $div['key'],
                                    'editorLabel' => 'Text Editor ' . $editorNo . ' [' . ($roman[$qi + 1] ?? $qi + 2) . ']',
                                    'base' => 'listening[parts][' . $partNo . '][divisions][' . $d . '][questions][' . $qi . ']',
                                ])
                            @endforeach
                        </div>
                    </div>
                </details>
                @if ($d < count($layout) - 1)
                    <p class="ls-pause">⏸ 10-second pause before the next division</p>
                @endif
            @endforeach
        </section>
    </div>
</details>
