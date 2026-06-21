<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_customers' => 120,
            'active_updates' => 5,
            'pending_issues' => 3,
            'revenue' => '15,000,000 تومان'
        ];

        return view('back.dashboard', compact('stats'));
    }
}
