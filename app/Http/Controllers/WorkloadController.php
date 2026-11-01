<?php

namespace App\Http\Controllers;

use App\Services\Forecaster;
use Illuminate\Http\Request;

class WorkloadController extends Controller
{
    public function index(Request $request, Forecaster $forecaster)
    {
        $weeks = min(26, max(4, (int) $request->query('minggu', 12)));
        return view('workload', ['data' => $forecaster->workload($weeks), 'weeks' => $weeks]);
    }
}
