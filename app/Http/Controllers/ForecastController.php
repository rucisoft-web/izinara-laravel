<?php

namespace App\Http\Controllers;

use App\Models\PermitType;
use App\Services\Forecaster;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    public function index(Request $request, Forecaster $forecaster)
    {
        $extra = max(-0.5, min(2, (float) $request->query('tambahan', 0) / 100));
        $extraCap = max(0, min(10, (int) $request->query('tambah_pic', 0)));
        $factors = $forecaster->slipFactors();
        return view('forecast', [
            'factors' => $factors,
            'types' => PermitType::orderBy('sequence')->get()->keyBy('id'),
            'portfolio' => $forecaster->portfolio($extra),
            'monthly' => $forecaster->monthly(12, $extra),
            'workload' => $forecaster->workload(12, $extra, $extraCap),
            'extra' => $extra, 'extraCap' => $extraCap,
        ]);
    }
}
