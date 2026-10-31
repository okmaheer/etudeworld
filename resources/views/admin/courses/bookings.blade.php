@extends('admin.layout')

@section('title', 'Course Bookings')

@section('content')
    <p class="muted" style="margin-bottom:16px">Students who bought a course and whose payment was confirmed by the payment gateway.</p>

    <form method="GET" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap">
        <input type="search" name="q" value="{{ $search }}" placeholder="Search name, email or course" style="min-width:260px">
        <button class="btn btn-sm btn-ghost" type="submit">Search</button>
        @if ($search !== '') <a href="{{ route('admin.course-bookings.index') }}" class="btn btn-sm btn-ghost">Clear</a> @endif
    </form>

    <section class="panel" style="padding:0;overflow-x:auto">
        <table class="table" style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="text-align:left;color:#6b7488;font-size:.82rem">
                    <th style="padding:12px 16px">Student</th><th>Course</th><th>Amount paid</th><th>Paid on</th><th>Payment reference</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $b)
                    <tr style="border-top:1px solid #e3e7ee;vertical-align:top">
                        <td style="padding:12px 16px"><strong>{{ $b->student_name }}</strong><br><small class="muted">{{ $b->student_email }}@if ($b->student_phone) · {{ $b->student_phone }}@endif</small></td>
                        <td>{{ $b->course_title }}@if ($b->exam)<br><span class="badge badge-blue">{{ \App\Models\Course::EXAMS[$b->exam] ?? strtoupper($b->exam) }}</span>@endif</td>
                        <td><strong>{{ $b->amountLabel() }}</strong></td>
                        <td style="white-space:nowrap">{{ $b->paid_at->format('j M Y') }}<br><small class="muted">{{ $b->paid_at->format('H:i') }}</small></td>
                        <td><small>{{ $b->payment_reference ?: '—' }}</small>@if ($b->gateway)<br><small class="muted">{{ ucfirst($b->gateway) }}</small>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted" style="padding:24px 16px">
                        @if ($search !== '') No booking matches “{{ $search }}”.
                        @else No bookings yet. A booking appears here by itself once a student pays and the payment gateway confirms it (the payment gateway is still to be added).
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if ($bookings->hasPages()) <div style="margin-top:14px">{{ $bookings->links('admin.partials.pagination') }}</div> @endif
@endsection
