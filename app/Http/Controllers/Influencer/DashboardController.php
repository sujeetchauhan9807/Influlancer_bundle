<?php

namespace App\Http\Controllers\Influencer;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:influencer');
    }

    public function stats(Request $request)
    {
        $influencer = $request->get('loggedInInfluencer'); // Logged-in influencer
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate   = $request->end_date   ? Carbon::parse($request->end_date) : null;

        // $filterByDate = function ($query) use ($startDate, $endDate, $influencer) {
        //     if ($startDate && $endDate) {
        //         $query->whereBetween('created_at', [$startDate, $endDate]);
        //     }
        //     $query->where('')
        // };
    }
}
