@extends('admin.layout')

@section('title', 'Vocabulary overview')

@section('content')
    <section class="panel">
        <p class="muted vo-note">Every IELTS and CELPIP test section and every Writing / Speaking topic, with its word count. <strong>{{ $totals['approved'] }}</strong> of <strong>{{ $totals['total'] }}</strong> words are approved. <strong>{{ $totals['missing'] }}</strong> words still miss some translations.</p>
    </section>

    @foreach ($sections as $title => $rows)
        @continue(! count($rows))
        <h3 class="vo-title">{{ $title }}</h3>
        <section class="panel vo-panel">
            <table class="vo-table">
                <thead><tr><th class="vo-name">Test / topic</th><th class="vo-num">Words</th><th class="vo-num">Approved</th><th class="vo-num">Missing translations</th><th class="vo-act"></th></tr></thead>
                <tbody>
                @foreach ($rows as $r)
                    <tr>
                        <td class="vo-name"><a href="{{ $r['open'] }}">{{ $r['label'] }}</a></td>
                        <td class="vo-num">{{ $r['total'] }}</td>
                        <td class="vo-num">@if ($r['total'] === 0)<span class="muted">none yet</span>@elseif ($r['approved'] === $r['total'])<span class="badge badge-green">All</span>@else {{ $r['approved'] }} of {{ $r['total'] }}@endif</td>
                        <td class="vo-num">{{ $r['missing'] ?: '–' }}</td>
                        <td class="vo-act">
                            @if ($r['missing'])
                                <form method="POST" action="{{ $r['translate'] }}" >@csrf<button class="btn btn-sm btn-ghost">Fill missing translations</button></form>
                            @endif
                            <a class="btn btn-sm btn-primary" href="{{ $r['open'] }}">{{ $r['total'] ? 'Open' : 'Add words' }}</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>
    @endforeach
@endsection
