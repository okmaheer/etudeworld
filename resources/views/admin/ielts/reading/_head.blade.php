{{-- Skill tabs + test name, shared by the Reading admin pages --}}
<div class="module-tabs">
    @foreach ($test->builtModules() as $key)
        <a href="{{ $test->adminModuleUrl($key) }}" class="module-tab {{ $key === ($module ?? 'reading') ? 'is-active' : '' }}">
            @include('admin.partials.icon', ['name' => \App\Models\IeltsTest::MODULES[$key]['icon']]) {{ \App\Models\IeltsTest::MODULES[$key]['label'] }}
        </a>
    @endforeach
</div>
<p class="module-context">
    <strong>{{ $test->name }}</strong>
    <span class="badge badge-blue">{{ $test->formatShort() }}</span>
    <span class="badge {{ $test->type === 'paid' ? 'badge-amber' : 'badge-teal' }}">{{ ucfirst($test->type) }}</span>
    <span class="badge {{ $test->status === 'active' ? 'badge-green' : 'badge-grey' }}">{{ ucfirst($test->status) }}</span>
</p>
