<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\License;
use App\Models\PermitType;
use App\Models\Supplier;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function index(Request $request)
    {
        $q = License::with('permitType.party', 'supplier', 'project', 'items');
        if ($t = $request->query('jenis')) $q->where('permit_type_id', $t);
        $licenses = $q->get()->sortBy(fn ($l) => $l->expires_at?->timestamp ?? PHP_INT_MAX);
        if ($s = $request->query('status')) $licenses = $licenses->filter(fn ($l) => $l->state() === $s);
        return view('licenses.index', ['licenses' => $licenses, 'types' => PermitType::orderBy('sequence')->get(),
            'suppliers' => Supplier::orderBy('name')->get(), 'items' => Item::orderBy('code')->limit(300)->get()]);
    }

    public function store(Request $request)
    {
        $this->requireNotViewer();
        $data = $request->validate(['permit_type_id' => 'required|exists:permit_types,id', 'number' => 'required|string|max:255',
            'issued_at' => 'nullable|date', 'expires_at' => 'nullable|date', 'supplier_id' => 'nullable|exists:suppliers,id',
            'quota_kg' => 'nullable|numeric', 'note' => 'nullable|string|max:255', 'items' => 'array', 'items.*' => 'exists:items,id']);
        $items = $data['items'] ?? [];
        unset($data['items']);
        License::create($data)->items()->sync($items);
        return back()->with('ok', 'Izin ditambahkan ke register.');
    }

    /** Buat proyek perpanjangan dari izin yang akan habis. */
    public function renew(License $license)
    {
        $this->requireManager();
        return redirect()->route('projects.create', [
            'nama' => 'Perpanjangan '.$license->permitType->name.' '.$license->number,
            'pemicu' => 'perpanjangan', 'tenggat' => $license->expires_at?->subDays(7)->toDateString(),
        ]);
    }
}
