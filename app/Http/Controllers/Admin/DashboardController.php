<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\CelpipTest;
use App\Models\IeltsTest;
use App\Models\ToeflTest;
use App\Services\AnalyticsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(AnalyticsService $analytics)
    {
        return view('admin.dashboard', [
            'gaConnected'   => $analytics->isConnected(),
            'liveVisitors'  => $analytics->liveVisitors(),
            'todayVisitors' => $analytics->visitorsToday(),
            'monthVisitors' => $analytics->visitorsThisMonth(),
            // People with paid access right now
            'paidClients'   => Schema::hasTable('clients') ? \App\Models\Client::active()->distinct()->count('email') : 0,
            'blogsPublished' => Blog::where('status', 'published')->count(),
            'blogsDraft'     => Blog::where('status', 'draft')->count(),
            'recentBlogs'    => Blog::latest()->take(5)->get(),
            // One card per exam: IELTS, CELPIP and TOEFL
            'examCards'      => collect(['ielts' => ['IELTS', IeltsTest::class], 'celpip' => ['CELPIP', CelpipTest::class], 'toefl' => ['TOEFL', ToeflTest::class]])
                ->map(fn ($x, $key) => [
                    'key' => $key, 'name' => $x[0],
                    'active' => $x[1]::where('status', 'active')->count(),
                    'draft'  => $x[1]::where('status', 'draft')->count(),
                    'recent' => $x[1]::latest()->take(5)->get(),
                ])->values(),
        ]);
    }
}
