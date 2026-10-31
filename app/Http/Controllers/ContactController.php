<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\InfoPage;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function show()
    {
        $page = InfoPage::where('slug', 'contact-us')->first();

        // Keep the same sum if the visitor already has one (so a wrong answer can be retried)
        $check = session('contact_check');
        if (! $check || empty($check['a'])) {
            $check = ['a' => random_int(2, 9), 'b' => random_int(2, 9), 'at' => time()];
            $check['sum'] = $check['a'] + $check['b'];
            session(['contact_check' => $check]);
        }
        $a = $check['a'];
        $b = $check['b'];

        return view('contact', [
            'question' => "$a + $b",
            'page'    => $page,
            'extra'   => $page && $page->is_published && trim(strip_tags((string) $page->body, '<img><iframe><table>')) !== '' ? $page->body : null,
            'contact' => SiteSetting::contact(),
            'social'  => SiteSetting::group('site')['social'] ?? [],
            'email'   => SiteSetting::group('site')['contact_email'] ?? '',
        ]);
    }

    public function send(Request $request)
    {
        $back = route('contact') . '#send-message';
        // Hidden field that only robots fill in: pretend it worked
        if (filled($request->input('website'))) {
            return redirect($back)->with('message_sent', true);
        }

        // Human check: answer to the simple sum, and the form must have been open for at least 3 seconds
        $check = session('contact_check');
        $tooFast = ! $check || (time() - ($check['at'] ?? 0)) < 3;
        $wrongSum = ! $check || trim((string) $request->input('human')) !== (string) ($check['sum'] ?? '');
        if ($tooFast || $wrongSum) {
            return redirect($back)->withInput()->withErrors([
                'human' => $wrongSum ? 'Please answer the small sum correctly to show you are not a robot.' : 'That was very fast. Please check your message and send it again.',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'email:rfc', 'max:190'],
            'phone'   => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-\s()]{5,40}$/'],
            'message' => ['required', 'string', 'min:5', 'max:3000'],
        ], ['phone.regex' => 'Please type your number with digits, spaces, + or - only.']);
        if ($validator->fails()) {
            return redirect($back)->withErrors($validator)->withInput();
        }
        $d = $validator->validated();

        ContactMessage::create([
            'name' => trim($d['name']), 'email' => strtolower(trim($d['email'])), 'phone' => trim((string) ($d['phone'] ?? '')) ?: null,
            'message' => trim($d['message']), 'is_read' => false,
        ]);

        session()->forget('contact_check');

        return redirect($back)->with('message_sent', true);
    }
}
