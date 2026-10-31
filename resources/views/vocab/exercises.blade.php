@extends('layouts.app')

@section('title', 'Vocabulary Exercises – ' . $list['examLabel'] . ' ' . $list['skillLabel'] . ' ' . $list['label'] . ' – ' . config('app.name'))
@section('description', 'Practise the ' . $list['examLabel'] . ' ' . strtolower($list['skillLabel']) . ' words from ' . $list['label'] . ' with quick exercises: match the meaning and fill the blank.')

@push('head')<link rel="stylesheet" href="{{ asset('css/tools.css') }}?v=4"><link rel="stylesheet" href="{{ asset('css/vocab.css') }}?v=7">@endpush

@section('content')
<section class="section">
    <div class="container vb-wrap" id="vbx">
        <x-breadcrumbs :items="[['Vocabulary Builder'], [$list['examLabel'], $list['examUrl']], [$list['skillLabel'], $list['skillUrl']], [$list['heading'], $list['show']], ['Exercises']]" />
        <h1 style="margin:0 0 6px">Exercises: {{ $list['heading'] }}</h1>
        <div class="vb-tabs" role="tablist">
            <button type="button" class="vb-tab is-active" data-ex="meaning">Match the meaning</button>
            <button type="button" class="vb-tab" data-ex="blank">Fill the blank</button>
        </div>
        <div id="vbx-area"></div>
    </div>
</section>
<script>window.VBX = {words: @json($words)};</script>
<script src="{{ asset('js/vocab-exercises.js') }}?v=1" defer></script>
@endsection
