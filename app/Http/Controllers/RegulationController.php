<?php

namespace App\Http\Controllers;

use App\Models\Regulation;
use Illuminate\Http\Request;

class RegulationController extends Controller
{
    public function index(Request $request)
    {
        $q = Regulation::with('replacedBy')->withCount(['tasks', 'steps', 'permitTypes']);
        if ($s = $request->query('status')) $q->where('status', $s);
        return view('regulations.index', ['regulations' => $q->orderBy('year', 'desc')->orderBy('name')->get(), 'all' => Regulation::orderBy('name')->get()]);
    }

    private function rules(?Regulation $r = null): array
    {
        return ['code' => 'required|string|max:40|unique:regulations,code'.($r ? ','.$r->id : ''), 'name' => 'required|string|max:255',
            'title' => 'required|string|max:500', 'issuer' => 'nullable|string|max:255', 'year' => 'nullable|string|max:10',
            'status' => 'required|in:'.implode(',', array_keys(Regulation::STATUSES)), 'note' => 'nullable|string',
            'url' => 'nullable|url|max:500', 'replaced_by_id' => 'nullable|exists:regulations,id', 'checked_at' => 'nullable|date'];
    }

    public function store(Request $request)
    {
        $this->requireManager();
        Regulation::create($request->validate($this->rules()));
        return back()->with('ok', 'Peraturan ditambahkan.');
    }

    /** Ubah peraturan; bila dicabut dan diganti, referensi di kegiatan yang belum selesai ikut diganti. */
    public function update(Request $request, Regulation $regulation)
    {
        $this->requireManager();
        $data = $request->validate($this->rules($regulation));
        $regulation->update($data);
        $moved = 0;
        if ($regulation->status === 'dicabut' && $regulation->replaced_by_id && $request->boolean('replace_refs')) {
            foreach ($regulation->tasks()->where('status', '!=', 'selesai')->get() as $task) {
                $task->regulations()->detach($regulation->id);
                $task->regulations()->syncWithoutDetaching([$regulation->replaced_by_id]);
                $moved++;
            }
            foreach ($regulation->steps as $step) {
                $step->regulations()->detach($regulation->id);
                $step->regulations()->syncWithoutDetaching([$regulation->replaced_by_id]);
            }
            foreach ($regulation->permitTypes as $type) {
                $type->regulations()->detach($regulation->id);
                $type->regulations()->syncWithoutDetaching([$regulation->replaced_by_id]);
            }
        }
        return back()->with('ok', 'Peraturan diperbarui.'.($moved ? " Referensi di $moved kegiatan terbuka diganti ke peraturan pengganti." : ''));
    }
}
