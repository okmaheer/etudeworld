<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseBooking;
use Illuminate\Http\Request;

/** Admin → Courses → Course Bookings: students whose payment was confirmed by the payment gateway. */
class CourseBookingController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $query = CourseBooking::orderByDesc('paid_at');
        if ($search !== '') {
            $like = '%' . mb_strtolower($search) . '%';
            $query->where(fn ($w) => $w->whereRaw('lower(student_name) like ?', [$like])->orWhereRaw('lower(student_email) like ?', [$like])->orWhereRaw('lower(course_title) like ?', [$like]));
        }

        return view('admin.courses.bookings', [
            'bookings' => $query->paginate(25)->withQueryString(),
            'search'   => $search,
            'total'    => CourseBooking::count(),
        ]);
    }
}
