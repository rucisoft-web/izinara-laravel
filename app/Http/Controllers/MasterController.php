<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Party;
use App\Models\PermitType;
use App\Models\PermitTypeStep;
use App\Models\Regulation;
use App\Models\Supplier;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterController extends Controller
{
    public function permitTypes()
    {
        return view('master.permit-types', ['types' => PermitType::with('party', 'regulations')->withCount('steps')->with('steps')->orderBy('sequence')->get()]);
    }

    public function permitType(PermitType $permitType)
    {
        $permitType->load('party', 'regulations', 'steps.party', 'steps.regulations', 'steps.docSpecs.verifier');
        return view('master.permit-type', ['type' => $permitType, 'parties' => Party::orderBy('sequence')->get(),
            'regulations' => Regulation::orderBy('name')->get()]);
    }

    public function updateStep(Request $request, PermitTypeStep $step)
    {
        $this->requireManager();
        $data = $request->validate(['name' => 'required|string|max:255', 'party_id' => 'required|exists:parties,id',
            'duration_days' => 'required|integer|min:1|max:365', 'instructions' => 'nullable|string', 'deliverable' => 'nullable|string|max:255',
            'regulations' => 'array', 'regulations.*' => 'exists:regulations,id', 'article' => 'array']);
        $step->update(collect($data)->except('regulations', 'article')->all());
        $step->regulations()->sync(collect($data['regulations'] ?? [])->mapWithKeys(fn ($id) => [$id => ['article' => $data['article'][$id] ?? null]])->all());
        return back()->with('ok', 'Langkah standar diperbarui. Berlaku untuk proyek baru.');
    }

    public function templates()
    {
        return view('master.templates', ['templates' => Template::with('lines.permitType')->withCount('items')->orderBy('food_group')->orderBy('sequence')->get(),
            'types' => PermitType::orderBy('sequence')->get()]);
    }

    public function items(Request $request)
    {
        $q = Item::with('template');
        if ($s = $request->query('q')) $q->where(fn ($w) => $w->where('code', 'like', "%$s%")->orWhere('name', 'like', "%$s%")->orWhere('hs_code', 'like', "$s%"));
        if ($t = $request->query('template')) $q->where('template_id', $t);
        return view('master.items', ['items' => $q->orderBy('code')->paginate(50)->withQueryString(), 'templates' => Template::orderBy('sequence')->get()]);
    }

    /**
     * Impor item code dari CSV (ITEMCODE;ITEMNAME;Country;Category;Group;SubGroup;HS;Non-halal;Jenis produk;Kode template)
     * atau dari file pemetaan bawaan (database/data/items.json, 2.864 item master bawaan).
     */
    public function importItems(Request $request)
    {
        $this->requireManager();
        $templates = Template::pluck('id', 'code');
        $rows = [];
        if ($request->boolean('bawaan')) {
            $rows = json_decode(file_get_contents(database_path('data/items.json')), true);
        } else {
            $request->validate(['file' => 'required|file|mimes:csv,txt|max:10240']);
            $fh = fopen($request->file('file')->getRealPath(), 'r');
            $first = fgets($fh);
            $sep = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
            while (($r = fgetcsv($fh, 0, $sep)) !== false) {
                if (count($r) >= 10 && $r[0]) $rows[] = [$r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], in_array(strtolower($r[7]), ['1', 'ya', 'true', 'y']), $r[8], $r[9]];
            }
        }
        $now = now();
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('items')->upsert(array_map(fn ($i) => ['code' => $i[0], 'name' => mb_substr($i[1], 0, 250), 'origin' => $i[2],
                'category' => $i[3], 'item_group' => $i[4], 'subgroup' => $i[5], 'hs_code' => $i[6] ?: null, 'non_halal' => (bool) $i[7],
                'product_kind' => $i[8], 'template_id' => $templates[$i[9]] ?? null, 'created_at' => $now, 'updated_at' => $now], $chunk),
                ['code'], ['name', 'origin', 'category', 'item_group', 'subgroup', 'hs_code', 'non_halal', 'product_kind', 'template_id', 'updated_at']);
        }
        return back()->with('ok', count($rows).' item code diimpor/diperbarui.');
    }

    public function suppliers()
    {
        return view('master.suppliers', ['suppliers' => Supplier::with('parent')->withCount('projects')->orderBy('kind')->orderBy('name')->get()]);
    }

    public function storeSupplier(Request $request)
    {
        $this->requireManager();
        Supplier::create($request->validate(['name' => 'required|string|max:255', 'kind' => 'required|in:exporter,establishment,local',
            'country' => 'nullable|string|max:60', 'establishment_no' => 'nullable|string|max:60', 'parent_id' => 'nullable|exists:suppliers,id']));
        return back()->with('ok', 'Pemasok ditambahkan.');
    }
}
