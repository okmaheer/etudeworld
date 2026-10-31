<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Admin → Manage Paid Clients: who may open the paid tests of which exam, and until when. */
class ClientController extends Controller
{
    public function create()
    {
        return view('admin.clients.form', ['exams' => Client::EXAMS]);
    }

    public function store(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'name'  => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-\s]*$/'],
            'exam'  => ['required', Rule::in(array_keys(Client::EXAMS))],
            'days'  => ['required', 'integer', 'between:1,30'],
        ], [
            'phone.regex' => 'The contact number may only contain digits, spaces, + ( ) and -.',
            'days.between' => 'Duration must be from 1 to 30 days.',
            'exam.in' => 'Please choose one test type.',
        ]);

        // One running access per email and exam: end the old one first (or wait until it expires)
        $running = Client::active()->where('email', $data['email'])->where('exam', $data['exam'])->first();
        if ($running) {
            return back()->withInput()->withErrors(['email' => $data['email'] . ' already has ' . Client::EXAMS[$data['exam']] . ' access until ' . $running->ends_at->format('j M Y') . '. End that access first if you want to start a new one.']);
        }

        $client = Client::create([
            'email' => $data['email'], 'name' => trim($data['name']), 'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'exam' => $data['exam'], 'days' => (int) $data['days'],
            'starts_at' => now(), 'ends_at' => Client::endFor((int) $data['days']),
        ]);

        return redirect()->route('admin.clients.index')->with('success', $client->name . ' now has ' . Client::EXAMS[$client->exam] . ' premium access until ' . $client->ends_at->format('j M Y') . '.');
    }

    public function index(Request $request)
    {
        $filter = $request->query('show', 'all');
        $q = Client::query();
        if ($filter === 'active') $q->active();
        elseif ($filter === 'expired') $q->where('ends_at', '<', now());
        if ($search = trim((string) $request->query('q', ''))) {
            $q->where(fn ($w) => $w->where('email', 'like', '%' . mb_strtolower($search) . '%')->orWhere('name', 'like', '%' . $search . '%'));
        }
        $clients = $q->orderByDesc('starts_at')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('admin.clients.index', [
            'clients' => $clients, 'filter' => $filter, 'search' => $search,
            'activeCount' => Client::active()->count(), 'expiredCount' => Client::where('ends_at', '<', now())->count(), 'allCount' => Client::count(),
        ]);
    }

    /** Stop the access right now (the record stays in the list as Expired). */
    public function end(Client $client)
    {
        if ($client->isActive()) $client->update(['ends_at' => now()->subSecond()]);

        return redirect()->back()->with('success', 'Access for ' . $client->email . ' was ended.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->back()->with('success', $client->email . ' was removed.');
    }
}
