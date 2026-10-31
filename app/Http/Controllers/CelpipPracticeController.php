<?php

namespace App\Http\Controllers;

use App\Models\CelpipTest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CelpipPracticeController extends Controller
{
    /** /celpip/general – the five sections. */
    public function hub(string $format)
    {
        $tests = $this->tests($format);

        return view('celpip.hub', [
            'format'  => $format,
            'label'   => CelpipTest::FORMATS[$format],
            'tests'   => $tests,
            'modules' => $this->modulesFor($format),
        ]);
    }

    /** /celpip/general/mock-tests – all full mock tests. */
    public function mockList(string $format)
    {
        return view('celpip.list', [
            'format' => $format,
            'label'  => CelpipTest::FORMATS[$format],
            'module' => null,
            'tests'  => $this->tests($format),
        ]);
    }

    /** /celpip/general/{module} – practice tests for one skill. */
    public function moduleList(string $format, string $module)
    {
        abort_unless(in_array($module, $this->modulesFor($format), true), 404);

        return view('celpip.list', [
            'format' => $format,
            'label'  => CelpipTest::FORMATS[$format],
            'module' => $module,
            'tests'  => $this->tests($format),
        ]);
    }

    /** /celpip/general/mock-tests/{slug} – a complete test, one section after another. */
    public function mock(string $format, string $slug)
    {
        $test = $this->findTest($format, $slug);

        if ($test->isLocked()) {
            return response()->view('celpip.locked', ['test' => $test, 'format' => $format, 'module' => null], 403);
        }

        // Writing and Speaking are evaluated by the teacher, so the student must be logged in.
        if ($this->needsLogin($test->examModules(), $test)) {
            return $this->toLogin();
        }

        return view('celpip.exam', [
            'test'    => $test,
            'format'  => $format,
            'mode'    => 'mock',
            'modules' => $test->examModules(),
            'backUrl' => route('celpip.practice.mocks', $format),
        ]);
    }

    /** /celpip/general/{module}/{slug} – practise one skill of one test. */
    public function practice(string $format, string $module, string $slug)
    {
        abort_unless(in_array($module, $this->modulesFor($format), true), 404);
        $test = $this->findTest($format, $slug);

        if ($test->isLocked()) {
            return response()->view('celpip.locked', ['test' => $test, 'format' => $format, 'module' => $module], 403);
        }

        if (! $test->moduleReady($module)) {
            return view('celpip.unavailable', ['test' => $test, 'format' => $format, 'module' => $module]);
        }

        if ($this->needsLogin([$module], $test)) {
            return $this->toLogin();
        }

        return view('celpip.exam', [
            'test'    => $test,
            'format'  => $format,
            'mode'    => 'practice',
            'modules' => [$module],
            'backUrl' => route('celpip.practice.module', [$format, $module]),
        ]);
    }

    // ───────────────────────── helpers ─────────────────────────

    /** Writing and Speaking need a logged-in student (Listening and Reading don't). */
    private function needsLogin(array $modules, CelpipTest $test): bool
    {
        return ! Auth::guard('student')->check()
            && collect($modules)->contains(fn ($m) => in_array($m, ['writing', 'speaking'], true) && $test->moduleReady($m));
    }

    private function toLogin()
    {
        return redirect()->route('student.login', ['return' => request()->fullUrl(), 'reason' => 'test']);
    }

    private function tests(string $format): Collection
    {
        abort_unless(isset(CelpipTest::FORMATS[$format]), 404);

        return CelpipTest::with('modules')->where('format', $format)->visible()->orderBy('id')->get();
    }

    private function findTest(string $format, string $slug): CelpipTest
    {
        return CelpipTest::with('modules')->where('format', $format)->where('slug', $slug)->visible()->firstOrFail();
    }

    /** Sections available in this format, in exam order. */
    private function modulesFor(string $format): array
    {
        return array_values(array_filter(
            array_keys(CelpipTest::PUBLIC_MODULES),
            fn ($m) => $format !== 'ls' || CelpipTest::MODULES[$m]['ls']
        ));
    }
}
