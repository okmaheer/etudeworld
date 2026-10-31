@extends('layouts.app')

@section('title', ($page?->meta_title) ?: 'Contact Us – ' . config('app.name'))
@section('description', ($page?->meta_description) ?: 'Contact ' . config('app.name') . ' for questions about IELTS, CELPIP and TOEFL practice tests and courses. Message us on WhatsApp or use the contact form.')

@push('head')
    {!! \App\Support\SchemaMarkup::page('ContactPage', 'Contact ' . config('app.name'), 'Contact details, opening hours and a contact form.') !!}
@endpush

@section('content')
    {{-- The heading and the text under it can be changed in Admin → SEO Pages → About & contact --}}
    <section class="info-hero">
        <div class="container">
            <x-breadcrumbs :items="[['Contact Us']]" align="center" />
            <span class="eyebrow">Contact us</span>
            <h1>We’d love to hear from you</h1>
            <p>Questions about a test, a course or your account? Send us a message or reach us on WhatsApp and we will get back to you.</p>
        </div>
    </section>

    <section class="section about-section contact-section">
        <div class="container">
            <div class="contact-grid">
                {{-- Details --}}
                <div class="contact-details">
                    <div class="ci-card">
                        <img class="ci-avatar" src="{!! trim(view('partials.manager-photo')->render()) !!}" alt="{{ $contact['manager_name'] }}, Founder and Website Manager" width="72" height="72" loading="lazy">
                        <div>
                            <strong>{{ $contact['manager_name'] }}</strong>
                            <span class="ci-sub">Founder and Website Manager</span>
                            <div class="social-links ci-person-social">
                                <a href="https://www.linkedin.com/in/hamza-elt/" class="social-link social-link--linkedin" aria-label="{{ $contact['manager_name'] }} on LinkedIn" target="_blank" rel="noopener">@include('partials.social-icon', ['name' => 'linkedin'])</a>
                                <a href="https://www.facebook.com/hamXa.ahmad.006" class="social-link social-link--facebook" aria-label="{{ $contact['manager_name'] }} on Facebook" target="_blank" rel="noopener">@include('partials.social-icon', ['name' => 'facebook'])</a>
                            </div>
                        </div>
                    </div>

                    @if ($contact['phone'])
                        <div class="ci-card">
                            <span class="ci-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg></span>
                            <div>
                                <span class="ci-label">Contact number</span>
                                <a href="{{ $contact['tel'] }}" class="ci-value">{{ $contact['phone'] }}</a>
                                @if ($contact['whatsapp'])
                                    <a href="{{ $contact['whatsapp'] }}" class="ci-wa" target="_blank" rel="noopener">
                                        <svg viewBox="0 0 32 32" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M16 3C8.8 3 3 8.8 3 16c0 2.3.6 4.5 1.7 6.4L3 29l6.8-1.8A13 13 0 0 0 16 29c7.2 0 13-5.8 13-13S23.2 3 16 3zm0 23.700c-2 0-3.9-.6-5.600-1.600l-.4-.2-4 1 1.100-3.900-.3-.4A10.700 10.700 0 1 1 16 26.700zm5.900-8c-.3-.2-1.900-.9-2.200-1-.3-.1-.5-.2-.7.200-.2.300-.8 1-1 1.200-.2.200-.4.200-.7.100-.3-.2-1.400-.5-2.600-1.600-1-.9-1.600-1.900-1.800-2.200-.2-.3 0-.5.100-.7l.5-.6c.1-.2.200-.3.300-.5.100-.2 0-.4 0-.6l-1-2.300c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.6.100-.9.400-.3.300-1.200 1.100-1.200 2.800s1.200 3.200 1.400 3.500c.2.200 2.400 3.700 5.800 5.100.8.400 1.500.6 2 .7.800.3 1.600.2 2.200.1.700-.1 1.900-.8 2.200-1.500.3-.7.300-1.400.2-1.500l-.6-.3z"/></svg>
                                        Chat on WhatsApp
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($email)
                        <div class="ci-card">
                            <span class="ci-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span>
                            <div><span class="ci-label">Email</span><a href="mailto:{{ $email }}" class="ci-value">{{ $email }}</a></div>
                        </div>
                    @endif

                    <div class="ci-card">
                        <span class="ci-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                        <div class="ci-hours">
                            <span class="ci-label">Availability hours</span>
                            <ul>
                                @foreach ($contact['hours'] as $day => $text)
                                    @php $closed = preg_match('/not available|closed/i', $text); @endphp
                                    <li class="{{ $closed ? 'is-closed' : 'is-open' }}"><span>{{ $day }}</span><strong>{{ $text }}</strong></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="ci-card">
                        <span class="ci-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 21s7-6.200 7-11.500A7 7 0 0 0 5 9.500C5 14.800 12 21 12 21z"/><circle cx="12" cy="9.500" r="2.500"/></svg></span>
                        <div>
                            <span class="ci-label">Address</span>
                            <span class="ci-value">{{ $contact['address'] }}</span>
                            <a href="{{ $contact['map_link'] }}" class="ci-wa ci-wa--map" target="_blank" rel="noopener">View on Google Maps</a>
                        </div>
                    </div>

                    <div class="ci-social">
                        <span class="ci-label">Follow us</span>
                        <div class="social-links">
                            @foreach (['facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok'] as $net => $netName)
                                <a href="{{ \App\Models\SiteSetting::href($social[$net] ?? '') }}" class="social-link social-link--{{ $net }}" aria-label="{{ $netName }}" target="_blank" rel="noopener">@include('partials.social-icon', ['name' => $net])</a>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Form --}}
                <div class="contact-form" id="send-message">
                    <h2>Send us a message</h2>
                    @if (session('message_sent'))
                        <div class="cc-success" role="status">
                            <strong>Thank you! Your message was sent.</strong>
                            <p>We will reply as soon as we can.</p>
                        </div>
                    @else
                        <p class="contact-form__intro">Fill in the form and we will get back to you.</p>
                        <form method="POST" action="{{ route('contact.send') }}" novalidate>
                            @csrf
                            <div class="cc-hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                            <div class="cc-field">
                                <label for="cf-name">Name</label>
                                <input id="cf-name" name="name" type="text" maxlength="120" required value="{{ old('name') }}" autocomplete="name">
                                @error('name') <p class="cc-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="cc-row">
                                <div class="cc-field">
                                    <label for="cf-email">Email</label>
                                    <input id="cf-email" name="email" type="email" maxlength="190" required value="{{ old('email') }}" autocomplete="email">
                                    @error('email') <p class="cc-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="cc-field">
                                    <label for="cf-phone">Contact number <small class="muted">(optional)</small></label>
                                    <input id="cf-phone" name="phone" type="tel" maxlength="40" value="{{ old('phone') }}" placeholder="+92 300 1234567" autocomplete="tel">
                                    @error('phone') <p class="cc-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="cc-field">
                                <label for="cf-message">Message</label>
                                <textarea id="cf-message" name="message" rows="6" maxlength="3000" required>{{ old('message') }}</textarea>
                                @error('message') <p class="cc-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="cc-field cc-human">
                                <label for="cf-human">Are you human? What is {{ $question }} ?</label>
                                <input id="cf-human" name="human" type="text" inputmode="numeric" autocomplete="off" required maxlength="3" style="max-width:120px">
                                @error('human') <p class="cc-error">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">Send message</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($extra)
        <section class="section section--compact"><div class="container-narrow"><div class="article-body" style="margin-top:0">{!! $extra !!}</div></div></section>
    @endif
@endsection
