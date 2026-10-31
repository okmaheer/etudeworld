<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Admin → Courses: the cards shown on /courses. */
class CourseController extends Controller
{
    public function index()
    {
        return view('admin.courses.index', [
            'courses'  => Course::orderBy('exam')->orderBy('sort_order')->orderBy('id')->get()->groupBy('exam'),
            'sections' => Course::sections(),
        ]);
    }

    public function create(Request $request)
    {
        $exam = array_key_exists($request->query('exam'), Course::EXAMS) ? $request->query('exam') : 'ielts';

        return view('admin.courses.form', ['course' => new Course([
            'exam' => $exam, 'price' => 20, 'currency' => 'USD', 'button_label' => 'Purchase Now', 'is_published' => true,
            'sort_order' => (int) Course::max('sort_order') + 1,
        ])]);
    }

    public function store(Request $request)
    {
        $course = Course::create($this->validated($request));

        return redirect()->route('admin.courses.index')->with('success', '“' . $course->title . '” was added.');
    }

    public function edit(Course $course)
    {
        return view('admin.courses.form', compact('course'));
    }

    public function update(Request $request, Course $course)
    {
        $course->update($this->validated($request, $course));

        return redirect()->route('admin.courses.index')->with('success', '“' . $course->title . '” was saved.');
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()->route('admin.courses.index')->with('success', '“' . $course->title . '” was deleted.');
    }

    /** Adds the standard IELTS / CELPIP / TOEFL courses (only when there are none yet). */
    public function seed()
    {
        Course::seedDefaults();

        return redirect()->route('admin.courses.index')->with('success', 'The standard courses were added.');
    }

    /** The title and text above the IELTS / CELPIP / TOEFL cards. */
    public function saveSections(Request $request)
    {
        $request->validate(['sections.*.title' => ['nullable', 'string', 'max:120'], 'sections.*.text' => ['nullable', 'string', 'max:400']]);
        $out = [];
        foreach (array_keys(Course::EXAMS) as $k) {
            $out[$k] = ['title' => trim((string) $request->input("sections.$k.title")), 'text' => trim((string) $request->input("sections.$k.text"))];
        }
        SiteSetting::put('courses', $out);

        return redirect()->route('admin.courses.index')->with('success', 'The section headings were saved.');
    }

    private function validated(Request $request, ?Course $course = null): array
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('title'))]);
        $data = $request->validate([
            'exam'         => ['required', Rule::in(array_keys(Course::EXAMS))],
            'title'        => ['required', 'string', 'max:160'],
            'slug'         => ['required', 'string', 'max:160', Rule::unique('course_plans', 'slug')->ignore($course?->id)],
            'summary'      => ['nullable', 'string', 'max:400'],
            'badge'        => ['nullable', 'string', 'max:40'],
            'chips'        => ['nullable', 'string', 'max:600'],
            'features'     => ['nullable', 'string', 'max:3000'],
            'note'         => ['nullable', 'string', 'max:200'],
            'price'        => ['required', 'numeric', 'min:0', 'max:99999'],
            'currency'     => ['required', 'string', 'alpha', 'size:3'],
            'button_label' => ['nullable', 'string', 'max:40'],
            'button_url'   => ['nullable', 'string', 'max:300'],
            'sort_order'   => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $data['currency'] = strtoupper($data['currency']);
        $data['button_label'] = trim((string) ($data['button_label'] ?? '')) ?: 'Purchase Now';
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }
}
