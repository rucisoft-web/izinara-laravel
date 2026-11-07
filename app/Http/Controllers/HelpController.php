<?php

namespace App\Http\Controllers;

use App\Models\PermitType;
use App\Models\Project;
use App\Models\Regulation;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class HelpController extends Controller
{
    /** Tingkat hierarki peraturan, untuk pengelompokan di landing page. */
    public const LEVELS = [
        'UU' => 'Undang-Undang',
        'PP' => 'Peraturan Pemerintah',
        'Perpres' => 'Peraturan Presiden',
        'Permen' => 'Peraturan Menteri',
        'Badan' => 'Peraturan / Keputusan Kepala Badan',
    ];

    public static function level(Regulation $r): string
    {
        return match (true) {
            str_starts_with($r->name, 'UU') => 'UU',
            str_starts_with($r->name, 'PP') => 'PP',
            str_starts_with($r->name, 'Perpres') => 'Perpres',
            str_starts_with($r->name, 'Per BPOM'), str_starts_with($r->name, 'Perbapanas'), str_contains($r->name, 'BPJPH'), str_starts_with($r->name, 'Keputusan') => 'Badan',
            default => 'Permen',
        };
    }

    public function landing()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }
        $order = array_keys(self::LEVELS);
        $regs = Regulation::withCount(['permitTypes', 'steps'])->orderBy('id')->get()
            ->groupBy(fn ($r) => self::level($r))
            ->sortBy(fn ($v, $k) => array_search($k, $order));

        return view('landing', [
            'regs' => $regs,
            'regCount' => Regulation::count(),
            'checkedAt' => Regulation::max('checked_at'),
            'permitTypes' => PermitType::with('regulations')->orderBy('sequence')->get(),
            'groups' => Template::orderBy('sequence')->get(),
            'projectCount' => Project::count(),
            'hasDemo' => User::where('username', 'sari.manajer')->exists(),
        ]);
    }

    public function index()
    {
        return view('help.index', ['cfg' => config('bantuan')]);
    }

    public function show(string $topic)
    {
        $t = config('bantuan.topik.'.$topic);
        abort_unless($t, 404);

        return view('help.show', ['key' => $topic, 't' => $t, 'cfg' => config('bantuan')]);
    }

    /** Topik bantuan untuk layar yang sedang dibuka (dipakai tombol Bantuan di layout). */
    public static function topicForCurrent(): string
    {
        $name = Route::currentRouteName();
        if ($name === 'projects.show') {
            return match (request('tab')) {
                'gantt' => 'gantt',
                'diskusi' => 'diskusi',
                'peraturan' => 'peraturan',
                'dokumen' => 'lampiran',
                default => 'proyek-detail',
            };
        }
        foreach (config('bantuan.topik') as $key => $t) {
            if (in_array($name, $t['routes'], true)) {
                return $key;
            }
        }

        return 'konsep';
    }
}
