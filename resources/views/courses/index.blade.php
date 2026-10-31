@extends('layouts.app')

@section('title', 'Courses for IELTS, CELPIP and TOEFL – ' . config('app.name'))
@section('description', 'Live classes, computer based mock tests and recorded lessons for IELTS, CELPIP and TOEFL. Pick a course and purchase it online.')

@section('content')
    {{-- The heading and the text under it can be changed in Admin → SEO Pages → Courses --}}
    <section class="courses-hero">
        <div class="container">
            <x-breadcrumbs :items="[['Courses']]" align="center" />
            <span class="eyebrow">Courses</span>
            <h1>Courses to reach your target score</h1>
            <p>Learn with an expert tutor in live classes, practise on computer based mock tests, or study at your own pace with recorded lessons. Choose your exam and pick the course that suits you.</p>
            <div class="courses-jump">
                @foreach (\App\Models\Course::EXAMS as $key => $label)
                    @if ($courses->has($key)) <a href="#{{ $key }}" class="btn btn-ghost">{{ $label }} courses</a> @endif
                @endforeach
            </div>
        </div>
    </section>

    @foreach (\App\Models\Course::EXAMS as $key => $label)
        @continue(! $courses->has($key))
        <section class="section {{ $loop->even ? 'section--tint' : '' }} courses-block" id="{{ $key }}">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">{{ $label }}</span>
                    <h2>{{ $sections[$key]['title'] }}</h2>
                    <p>{{ $sections[$key]['text'] }}</p>
                </div>
                <div class="course-slider" data-course-slider>
                    <button type="button" class="cs-btn cs-prev" aria-label="Previous courses" data-cs-prev>&#8249;</button>
                    <div class="course-grid" data-cs-track tabindex="0">
                    @foreach ($courses[$key] as $c)
                        <article class="course-card {{ $c->is_featured ? 'course-card--featured' : '' }}" id="{{ $c->slug }}">
                            @if ($c->badge) <span class="course-badge">{{ $c->badge }}</span> @endif
                            <h3>{{ $c->title }}</h3>
                            @if ($c->summary) <p class="course-summary">{{ $c->summary }}</p> @endif
                            @if ($c->chipList())
                                <ul class="course-chips">
                                    @foreach ($c->chipList() as $chip) <li>{{ $chip }}</li> @endforeach
                                </ul>
                            @endif
                            @if ($c->featureList())
                                <ul class="course-features">
                                    @foreach ($c->featureList() as $f) <li>{{ $f }}</li> @endforeach
                                </ul>
                            @endif
                            @if ($c->note) <p class="course-note">{{ $c->note }}</p> @endif
                            <div class="course-foot">
                                <div class="course-price"><span class="course-price__label">Price</span> <strong>{{ $c->priceAmount() }}<small>{{ strtoupper($c->currency) }}</small></strong></div>
                                <a href="{{ $c->buttonHref() }}" class="btn btn-primary">{{ $c->button_label ?: 'Purchase Now' }}</a>
                            </div>
                        </article>
                    @endforeach
                    </div>
                    <button type="button" class="cs-btn cs-next" aria-label="Next courses" data-cs-next>&#8250;</button>
                </div>
            </div>
        </section>
    @endforeach

    {{-- Build your own course: the request goes to Admin → Courses → Course Requests --}}
    <section class="section custom-course" id="custom-course">
        <div class="container">
            <div class="custom-course__box">
                <div class="custom-course__intro">
                    <span class="eyebrow">Custom course</span>
                    <h2>Need something different? Build your own course</h2>
                    <p>Tell us which test you are preparing for, how many classes you want and which modules you need. We will contact you with a quote.</p>
                </div>
                <div class="custom-course__form">
                    @if (session('course_request_sent'))
                        <div class="cc-success" role="status">
                            <strong>Thank you! Your request was sent.</strong>
                            <p>We will contact you on the number you gave us with a quote.</p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('courses.request') }}" novalidate>
                            @csrf
                            <div class="cc-hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                            <div class="cc-field">
                                <label for="cc-name">Name</label>
                                <input id="cc-name" name="name" type="text" maxlength="120" required value="{{ old('name') }}" autocomplete="name">
                                @error('name') <p class="cc-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="cc-field">
                                <label for="cc-phone">Contact number</label>
                                <div class="cc-phone">
                                    <select name="country" id="cc-country" aria-label="Country">
                                        @foreach (\App\Support\Countries::LIST as $country => $dial)
                                            <option value="{{ $country }}" @selected(old('country', 'Pakistan') === $country)>{{ $country }} ({{ $dial }})</option>
                                        @endforeach
                                    </select>
                                    <input id="cc-phone" name="phone" type="tel" inputmode="tel" maxlength="30" required value="{{ old('phone') }}" placeholder="Phone number" autocomplete="tel-national">
                                </div>
                                @error('country') <p class="cc-error">{{ $message }}</p> @enderror
                                @error('phone') <p class="cc-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="cc-row">
                                <div class="cc-field">
                                    <label for="cc-exam">Test type</label>
                                    <select id="cc-exam" name="exam" required>
                                        @foreach (\App\Models\Course::EXAMS as $k => $label) <option value="{{ $k }}" @selected(old('exam') === $k)>{{ $label }}</option> @endforeach
                                    </select>
                                    @error('exam') <p class="cc-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="cc-field">
                                    <label for="cc-classes">Number of classes</label>
                                    <input id="cc-classes" name="classes" type="number" min="1" max="100" required value="{{ old('classes', 7) }}">
                                    @error('classes') <p class="cc-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <fieldset class="cc-field">
                                <legend>Which modules do you want classes for?</legend>
                                <div class="cc-checks">
                                    @foreach (\App\Models\CourseRequest::MODULES as $k => $label)
                                        <label><input type="checkbox" name="modules[]" value="{{ $k }}" @checked(in_array($k, (array) old('modules', [])))> {{ $label }}</label>
                                    @endforeach
                                </div>
                                @error('modules') <p class="cc-error">{{ $message }}</p> @enderror
                                @error('modules.*') <p class="cc-error">{{ $message }}</p> @enderror
                            </fieldset>
                            <button type="submit" class="btn btn-primary">Get a Quote</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($courses->isEmpty())
        <section class="section"><div class="container"><p class="muted" style="text-align:center">Courses are coming soon. Please check back shortly.</p></div></section>
    @endif
@endsection
