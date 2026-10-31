@extends('layouts.app')
@section('title', $heading . ' – ' . $test->name)
@section('content')
    <section class="section">
        <div class="container" style="max-width:640px;text-align:center">
            <h1>{{ $heading }}</h1>
            <p><strong>{{ $test->name }}</strong></p>
            <p>{{ $text }}</p>
            <p><a href="{{ $backUrl }}" class="btn btn-primary">← Back to the tests</a></p>
        </div>
    </section>
@endsection
