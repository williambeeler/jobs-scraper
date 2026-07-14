<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $today = Carbon::today();
        $listings = JobListing::query()
            ->whereBetween('created_at', [
                $today->copy()->startOfDay(),
                $today->copy()->endOfDay(),
            ])
            ->orderByDesc('posted_at')
            ->orderByDesc('last_seen_at')
            ->get();

        return view('home', [
            'today' => $today,
            'listings' => $listings,
        ]);
    }
}
