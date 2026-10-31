@extends('admin.layout')

@section('title', 'Messages')

@section('content')
    <p class="muted" style="margin-bottom:16px">Messages sent from the <a href="{{ route('contact') }}" target="_blank" rel="noopener">Contact Us</a> form. Reply by email or WhatsApp, then mark the message as read.</p>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
        <a href="{{ route('admin.messages.index') }}" class="btn btn-sm {{ $filter === 'all' ? 'btn-primary' : 'btn-ghost' }}">All ({{ $total }})</a>
        <a href="{{ route('admin.messages.index', ['show' => 'unread']) }}" class="btn btn-sm {{ $filter === 'unread' ? 'btn-primary' : 'btn-ghost' }}">Unread ({{ $unread }})</a>
    </div>

    @forelse ($messages as $m)
        <section class="panel" style="margin-bottom:12px;{{ $m->is_read ? '' : 'border-left:4px solid var(--primary);' }}">
            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start">
                <div>
                    <strong>{{ $m->name }}</strong>
                    @unless ($m->is_read) <span class="badge badge-blue">New</span> @endunless
                    <div class="muted" style="font-size:.9rem;margin-top:2px">
                        <a href="mailto:{{ $m->email }}?subject={{ rawurlencode('Re: your message to ' . config('app.name')) }}">{{ $m->email }}</a>
                        @if ($m->phone) · {{ $m->phone }} @if ($m->whatsappUrl()) · <a href="{{ $m->whatsappUrl() }}" target="_blank" rel="noopener">WhatsApp</a> @endif @endif
                    </div>
                </div>
                <div class="muted" style="font-size:.85rem;white-space:nowrap">{{ $m->created_at->format('j M Y, H:i') }}</div>
            </div>
            <p style="margin:12px 0;white-space:pre-line;overflow-wrap:anywhere">{{ $m->message }}</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a class="btn btn-sm btn-primary" href="mailto:{{ $m->email }}?subject={{ rawurlencode('Re: your message to ' . config('app.name')) }}">Reply by email</a>
                <form method="POST" action="{{ route('admin.messages.toggle', $m) }}">@csrf<button class="btn btn-sm btn-ghost" type="submit">{{ $m->is_read ? 'Mark as unread' : 'Mark as read' }}</button></form>
                <form method="POST" action="{{ route('admin.messages.destroy', $m) }}" onsubmit="return confirm('Delete this message?')">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost" type="submit" style="color:#d7263d">Delete</button></form>
            </div>
        </section>
    @empty
        <section class="panel"><p class="muted" style="margin:0">{{ $filter === 'unread' ? 'No unread messages.' : 'No messages yet.' }}</p></section>
    @endforelse

    @if ($messages->hasPages()) <div style="margin-top:14px">{{ $messages->links('admin.partials.pagination') }}</div> @endif
@endsection
