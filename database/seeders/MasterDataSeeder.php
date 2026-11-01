<?php

namespace Database\Seeders;

use App\Models\DocSpec;
use App\Models\Item;
use App\Models\Party;
use App\Models\PermitType;
use App\Models\PermitTypeStep;
use App\Models\Regulation;
use App\Models\Template;
use App\Models\TemplateLine;
use App\Services\ProjectBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data master dari modul Odoo Izinara (bagian, peraturan, 22 jenis izin dengan 89 langkah standar & dokumen wajib,
 * 17 template) dan 2.864 item code dari item master Indoguna (pemetaan-item-ke-izin.xlsx).
 */
class MasterDataSeeder extends Seeder
{
    private function json(string $name): array
    {
        return json_decode(file_get_contents(database_path("data/$name.json")), true);
    }

    public function run(): void
    {
        $parties = [];
        foreach ($this->json('parties') as $p) {
            $parties[$p['code']] = Party::updateOrCreate(['code' => $p['code']], $p)->id;
        }

        $regs = [];
        foreach ($this->json('regulations') as $r) {
            $regs[$r['code']] = Regulation::updateOrCreate(['code' => $r['code']], $r + ['checked_at' => '2026-11-01'])->id;
        }

        foreach ($this->json('permit_types') as $t) {
            $type = PermitType::updateOrCreate(['code' => $t['code']], [
                'name' => $t['name'], 'sequence' => $t['sequence'], 'authority' => $t['authority'], 'system' => $t['system'],
                'party_id' => $parties[$t['party']], 'scope' => $t['scope'], 'lead_days' => $t['lead_days'],
                'validity_months' => $t['validity_months'], 'legal_basis' => $t['legal_basis'],
                'description' => $t['description'], 'prerequisites' => ProjectBuilder::PREREQUISITES[$t['code']] ?? [],
            ]);
            $type->regulations()->sync(array_map(fn ($c) => $regs[$c], $t['regulations']));
            $type->steps()->delete();
            foreach ($t['steps'] as $s) {
                $step = PermitTypeStep::create([
                    'permit_type_id' => $type->id, 'sequence' => $s['sequence'], 'name' => $s['name'],
                    'party_id' => $parties[$s['party']], 'duration_days' => $s['duration_days'],
                    'instructions' => $s['instructions'], 'deliverable' => $s['deliverable'] ?: null,
                    'is_submission' => (bool) preg_match('/^(Ajukan|Daftarkan|Submit|Input)/i', $s['name']),
                ]);
                $step->regulations()->sync(array_map(fn ($c) => $regs[$c], $s['regulations'] ?: $t['regulations']));
                foreach ($s['docs'] as $d) {
                    DocSpec::create(['permit_type_step_id' => $step->id, 'name' => $d['name'], 'criteria' => $d['criteria'],
                        'check_expiry' => $d['check_expiry'], 'verifier_party_id' => $parties[$d['verifier']] ?? null]);
                }
            }
        }

        $types = PermitType::pluck('id', 'code');
        $templates = [];
        foreach ($this->json('templates') as $t) {
            $tpl = Template::updateOrCreate(['code' => $t['code']], [
                'name' => $t['name'], 'food_group' => $t['food_group'], 'sequence' => $t['sequence'], 'description' => $t['description'],
            ]);
            $templates[$t['code']] = $tpl->id;
            $tpl->lines()->delete();
            foreach ($t['lines'] as $l) {
                TemplateLine::create(['template_id' => $tpl->id, 'permit_type_id' => $types[$l['type']],
                    'sequence' => $l['sequence'], 'mandatory' => $l['mandatory']]);
            }
        }

        // Kelompok khusus proyek sertifikasi halal (SHLN untuk produk impor, sertifikat halal BPJPH untuk produk
        // yang dipotong/dikemas ulang Indoguna, pemisahan produk non-halal).
        $halal = Template::updateOrCreate(['code' => 'T_HALAL'], ['name' => 'Sertifikasi halal (SHLN & BPJPH)', 'food_group' => 'halal',
            'sequence' => 95, 'description' => 'Proyek khusus memperoleh/memperpanjang sertifikat halal: registrasi SHLN di BPJPH dan sertifikasi halal produk kemas ulang.']);
        $halal->lines()->delete();
        foreach ([['SHLN', 10, true], ['HALAL_SH', 20, true], ['NONHALAL', 30, false]] as [$code, $seq, $mand]) {
            TemplateLine::create(['template_id' => $halal->id, 'permit_type_id' => $types[$code], 'sequence' => $seq, 'mandatory' => $mand]);
        }
        // Item code tidak dimuat di sini. Muat item master lengkap dari menu Item code > "Muat item master Indoguna",
        // atau php artisan db:seed --class=ItemMasterSeeder.
    }
}
