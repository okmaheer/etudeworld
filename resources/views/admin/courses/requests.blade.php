@extends('admin.layout')

@section('title', 'Course Requests')

@section('content')
    <p class="muted" style="margin-bottom:16px">Students who filled in “Build your own course” at the bottom of the <a href="{{ route('courses.index') }}#custom-course" target="_blank" rel="noopener">Courses page</a>. Contact them with a quote, then mark the request.</p>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
        <a href="{{ route('admin.course-requests.index') }}" class="btn btn-sm {{ $filter === 'all' ? 'btn-primary' : 'btn-ghost' }}">All ({{ $counts->sum() }})</a>
        @foreach (\App\Models\CourseRequest::STATUSES as $k => $label)
            <a href="{{ route('admin.course-requests.index', ['show' => $k]) }}" class="btn btn-sm {{ $filter === $k ? 'btn-primary' : 'btn-ghost' }}">{{ $label }} ({{ $counts[$k] ?? 0 }})</a>
        @endforeach
    </div>

    <section class="panel" style="padding:0;overflow-x:auto">
        <table class="table" style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="text-align:left;color:#6b7488;font-size:.82rem">
                    <th style="padding:12px 16px">Student</th><th>Contact</th><th>Test</th><th>Classes</th><th>Modules</th><th>Sent</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $r)
                    <tr style="border-top:1px solid #e3e7ee;vertical-align:top">
                        <td style="padding:12px 16px"><strong>{{ $r->name }}</strong><br><small class="muted">{{ $r->country }}</small></td>
                        <td style="white-space:nowrap">{{ $r->fullPhone() }}<br><a href="{{ $r->whatsappUrl() }}" target="_blank" rel="noopener"><small>Open WhatsApp</small></a></td>
                        <td>{{ \App\Models\Course::EXAMS[$r->exam] ?? strtoupper($r->exam) }}</td>
                        <td>{{ $r->classes }}</td>
                        <td>{{ $r->moduleLabels() }}</td>
                        <td style="white-space:nowrap">{{ $r->created_at->format('j M Y') }}<br><small class="muted">{{ $r->created_at->format('H:i') }}</small></td>
                        <td>
                            <form method="POST" action="{{ route('admin.course-requests.status', $r) }}">
                                @csrf
                                <select name="status" onchange="this.form.submit()" aria-label="Status of the request from {{ $r->name }}">
                                    @foreach (\App\Models\CourseRequest::STATUSES as $k => $label) <option value="{{ $k }}" @selected($r->status === $k)>{{ $label }}</option> @endforeach
                                </select>
                            </form>
                        </td>
                        <td style="padding-right:16px;text-align:right">
                            <form method="POST" action="{{ route('admin.course-requests.destroy', $r) }}" onsubmit="return confirm('Delete the request from {{ addslashes($r->name) }}?')">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost" type="submit" style="color:#d7263d">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted" style="padding:24px 16px">No requests here yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if ($requests->hasPages()) <div style="margin-top:14px">{{ $requests->links('admin.partials.pagination') }}</div> @endif
@endsection
