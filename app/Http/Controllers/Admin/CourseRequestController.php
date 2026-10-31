<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseRequest;
use Illuminate\Http\Request;

/** Admin → Courses → Course Requests */
class CourseRequestController extends Controller
{
    public function index(Request $request)
    {
        $filter = array_key_exists($request->query('show'), CourseRequest::STATUSES) ? $request->query('show') : 'all';
        $query = CourseRequest::orderByDesc('created_at');
        if ($filter !== 'all') $query->where('status', $filter);

        return view('admin.courses.requests', [
            'requests' => $query->paginate(25)->withQueryString(),
            'filter'   => $filter,
            'counts'   => CourseRequest::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function status(Request $request, CourseRequest $courseRequest)
    {
        $data = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(CourseRequest::STATUSES))]]);
        $courseRequest->update(['status' => $data['status']]);

        return back()->with('success', 'Request from ' . $courseRequest->name . ' marked as ' . strtolower(CourseRequest::STATUSES[$data['status']]) . '.');
    }

    public function destroy(CourseRequest $courseRequest)
    {
        $courseRequest->delete();

        return back()->with('success', 'The request was deleted.');
    }
}
