<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Muat 2.864 item code dari item master bawaan (pemetaan-item-ke-izin.xlsx, Oktober 2026). */
class ItemMasterSeeder extends Seeder
{
    public function run(?array $only = null): void
    {
        $templates = Template::pluck('id', 'code');
        $rows = json_decode(file_get_contents(database_path('data/items.json')), true);
        if ($only) $rows = array_values(array_filter($rows, fn ($r) => in_array($r[0], $only, true)));
        $now = now();
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('items')->upsert(array_map(fn ($i) => [
                'code' => $i[0], 'name' => mb_substr($i[1], 0, 250), 'origin' => $i[2], 'category' => $i[3],
                'item_group' => $i[4], 'subgroup' => $i[5], 'hs_code' => $i[6] ?: null, 'non_halal' => $i[7],
                'product_kind' => $i[8], 'template_id' => $templates[$i[9]] ?? null, 'created_at' => $now, 'updated_at' => $now,
            ], $chunk), ['code'], ['name', 'origin', 'category', 'item_group', 'subgroup', 'hs_code', 'non_halal', 'product_kind', 'template_id', 'updated_at']);
        }
    }
}
