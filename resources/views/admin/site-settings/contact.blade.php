@extends('admin.layout')

@section('title', 'Contact details')

@section('content')
    <p class="muted" style="margin:0 0 14px">Shown on the <a href="{{ route('contact') }}" target="_blank" rel="noopener">Contact Us</a> and <a href="{{ route('about') }}" target="_blank" rel="noopener">About Us</a> pages, and in the information that search engines read about your business. The email address and the social media links are in <a href="{{ route('admin.site-settings.site') }}">Logo, footer &amp; social</a>.</p>

    <form method="POST" action="{{ route('admin.site-settings.contact.save') }}">
        @csrf
        <section class="panel" style="margin-bottom:16px">
            <div class="panel-head"><h2>Contact person</h2></div>
            <div class="field-row">
                <div class="field"><label for="manager_name">Name</label><input id="manager_name" name="manager_name" type="text" maxlength="120" value="{{ old('manager_name', $c['manager_name']) }}"></div>
                <div class="field"><label for="manager_role">Role</label><input id="manager_role" name="manager_role" type="text" maxlength="120" value="{{ old('manager_role', $c['manager_role']) }}"></div>
            </div>
            <div class="field">
                <label for="phone">Contact number (also used for the WhatsApp link)</label>
                <input id="phone" name="phone" type="text" maxlength="40" value="{{ old('phone', $c['phone']) }}" placeholder="+92 315 4315382">
                @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                <p class="hint">Type it with the country code, e.g. +92 315 4315382.</p>
            </div>
        </section>

        <section class="panel" style="margin-bottom:16px">
            <div class="panel-head"><h2>Address and map</h2></div>
            <div class="field"><label for="address_line">Street address</label><input id="address_line" name="address_line" type="text" maxlength="200" value="{{ old('address_line', $c['address_line']) }}"></div>
            <div class="field-row">
                <div class="field"><label for="city">City</label><input id="city" name="city" type="text" maxlength="100" value="{{ old('city', $c['city']) }}"></div>
                <div class="field"><label for="country">Country</label><input id="country" name="country" type="text" maxlength="100" value="{{ old('country', $c['country']) }}"></div>
            </div>
            <div class="field">
                <label for="map_query">Map search text <small class="muted">(optional)</small></label>
                <input id="map_query" name="map_query" type="text" maxlength="250" value="{{ old('map_query', $c['map_query']) }}" placeholder="Leave empty to use the address above">
                <p class="hint">If the map does not land on the right spot, type a place name or full address that works when you search it on Google Maps.</p>
            </div>
        </section>

        <section class="panel" style="margin-bottom:16px">
            <div class="panel-head"><h2>Availability hours</h2></div>
            <p class="hint" style="margin-top:0">Write what to show for each day, for example “24 Hours”, “9 am – 5 pm” or “Not available”. A day that says “Not available” or “Closed” is shown in red.</p>
            <div class="ss-grid">
                @foreach ($c['hours'] as $day => $text)
                    <div class="field"><label for="h-{{ $day }}">{{ $day }}</label><input id="h-{{ $day }}" name="hours[{{ $day }}]" type="text" maxlength="60" value="{{ old('hours.' . $day, $text) }}"></div>
                @endforeach
            </div>
        </section>

        <button type="submit" class="btn btn-primary">Save contact details</button>
    </form>
@endsection

@push('head')
    <style>.ss-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; } @media (max-width: 800px) { .ss-grid { grid-template-columns: 1fr; } }</style>
@endpush
