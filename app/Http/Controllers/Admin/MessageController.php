<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

/** Admin → Messages: what visitors wrote in the Contact Us form. */
class MessageController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('show') === 'unread' ? 'unread' : 'all';
        $query = ContactMessage::orderByDesc('created_at');
        if ($filter === 'unread') $query->where('is_read', false);

        return view('admin.messages.index', [
            'messages' => $query->paginate(20)->withQueryString(),
            'filter'   => $filter,
            'total'    => ContactMessage::count(),
            'unread'   => ContactMessage::where('is_read', false)->count(),
        ]);
    }

    public function toggle(ContactMessage $message)
    {
        $message->update(['is_read' => ! $message->is_read]);

        return back();
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();

        return back()->with('success', 'The message was deleted.');
    }
}
