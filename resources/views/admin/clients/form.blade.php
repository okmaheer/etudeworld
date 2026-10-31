@extends('admin.layout')

@section('title', 'Add a new client')

@section('actions')
    <a href="{{ route('admin.clients.index') }}" class="btn btn-sm btn-ghost">All clients</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.clients.store') }}" style="max-width:640px">
        @csrf
        <section class="panel">
            <p class="muted" style="margin-top:0">When you save, this email address becomes a paid client. As soon as they log in with it, every premium test of the chosen exam opens for them – until the last day you choose.</p>

            <div class="field">
                <label for="email">Email <span class="req">*</span></label>
                <input id="email" name="email" type="email" required maxlength="190" autocomplete="off" value="{{ old('email') }}" placeholder="student@gmail.com">
                <p class="hint">Must be the same email address the student logs in with (Google or Microsoft).</p>
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="name">Name <span class="req">*</span></label>
                    <input id="name" name="name" type="text" required maxlength="120" value="{{ old('name') }}">
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="phone">Contact No</label>
                    <input id="phone" name="phone" type="text" maxlength="40" value="{{ old('phone') }}" placeholder="+92 300 1234567">
                    @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="field">
                <label>Test type <span class="req">*</span></label>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    @foreach ($exams as $key => $label)
                        <label class="check" style="border:1px solid #d4dbe8;border-radius:10px;padding:10px 16px;cursor:pointer">
                            <input type="radio" name="exam" value="{{ $key }}" required @checked(old('exam') === $key)> {{ $label }}
                        </label>
                    @endforeach
                </div>
                <p class="hint">Choose one. The client can open all premium tests of this exam only.</p>
                @error('exam') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field" style="max-width:260px">
                <label for="days">Duration (days) <span class="req">*</span></label>
                <input id="days" name="days" type="number" required min="1" max="30" step="1" value="{{ old('days', 7) }}" data-days>
                <p class="hint" data-end-note>From 1 to 30 days.</p>
                @error('days') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
        </section>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var input = document.querySelector('[data-days]'), note = document.querySelector('[data-end-note]');
            var base = new Date(@json(now()->format('Y-m-d')) + 'T12:00:00');
            var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            function show() {
                var n = parseInt(input.value, 10);
                if (!(n >= 1 && n <= 30)) { note.textContent = 'From 1 to 30 days.'; return; }
                var d = new Date(base.getTime()); d.setDate(d.getDate() + n);
                note.textContent = 'Access starts now and lasts until the end of ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear() + '.';
            }
            input.addEventListener('input', show); show();
        })();
    </script>
@endpush
