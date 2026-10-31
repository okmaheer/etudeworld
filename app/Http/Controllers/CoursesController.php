<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseRequest;
use App\Support\Countries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CoursesController extends Controller
{
    public function index()
    {
        $courses = Course::where('is_published', true)->orderBy('sort_order')->orderBy('id')->get()->groupBy('exam')
            // The highlighted (best value) card goes second, so it sits in the middle of the first three cards
            ->map(function ($group) {
                $i = $group->search(fn ($c) => $c->is_featured);
                if ($i === false || $group->count() < 3) return $group;
                $feat = $group->get($i);
                $rest = $group->reject(fn ($c, $k) => $k === $i)->values();

                return $rest->slice(0, 1)->concat([$feat])->concat($rest->slice(1))->values();
            });

        return view('courses.index', ['courses' => $courses, 'sections' => Course::sections()]);
    }

    /** "Get a Quote": saves the student's custom-course request for the admin. */
    public function requestQuote(Request $request)
    {
        // Hidden field that only robots fill in: pretend it worked
        if (filled($request->input('website'))) {
            return redirect(route('courses.index') . '#custom-course')->with('course_request_sent', true);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name'      => ['required', 'string', 'max:120'],
            'country'   => ['required', Rule::in(Countries::names())],
            'phone'     => ['required', 'string', 'max:30', 'regex:/^[0-9 \-]{5,30}$/'],
            'exam'      => ['required', Rule::in(array_keys(Course::EXAMS))],
            'classes'   => ['required', 'integer', 'min:1', 'max:100'],
            'modules'   => ['required', 'array', 'min:1'],
            'modules.*' => ['string', Rule::in(array_keys(CourseRequest::MODULES))],
        ], [
            'phone.regex'      => 'Please type your number with digits only.',
            'modules.required' => 'Please choose at least one module.',
        ]);
        if ($validator->fails()) {
            // come back to the form, not the top of the page
            return redirect(route('courses.index') . '#custom-course')->withErrors($validator)->withInput();
        }
        $data = $validator->validated();

        CourseRequest::create([
            'name' => trim($data['name']), 'country' => $data['country'], 'dial_code' => Countries::dial($data['country']),
            'phone' => trim($data['phone']), 'exam' => $data['exam'], 'classes' => $data['classes'],
            'modules' => implode(',', $data['modules']), 'status' => 'new',
        ]);

        return redirect(route('courses.index') . '#custom-course')->with('course_request_sent', true);
    }
}
