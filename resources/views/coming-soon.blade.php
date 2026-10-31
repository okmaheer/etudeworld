@extends('layouts.app')

@section('title', $title . ' – Coming soon – ' . config('app.name'))
@section('description', $title . ' is coming soon to ' . config('app.name') . '. We are preparing this page right now.')

{{-- Pages that are not ready yet should not appear in Google. --}}
@push('head')
    <meta name="robots" content="noindex, follow">
@endpush

@section('content')
    <section class="coming-soon">
        <div class="container">
            <span class="eyebrow">Coming soon</span>
            <h1>{{ $title }}</h1>
            <p>We're working on this page right now. Check back soon!</p>
            <a href="{{ route('home') }}" class="btn btn-primary">Back to homepage</a>
        </div>
    </section>
@endsection
