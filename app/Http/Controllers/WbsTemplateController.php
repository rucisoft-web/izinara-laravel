<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Template;
use App\Models\WbsTemplate;
use App\Services\WbsTemplateService;
use Illuminate\Http\Request;

class WbsTemplateController extends Controller
{
    public function index()
    {
        return view('wbs.index', ['templates' => WbsTemplate::with('template', 'creator')->orderBy('food_group')->orderBy('name')->get()]);
    }

    public function show(WbsTemplate $wbsTemplate)
    {
        return view('wbs.show', ['tpl' => $wbsTemplate, 'groups' => Template::GROUPS]);
    }

    public function storeFromProject(Request $request, Project $project, WbsTemplateService $svc)
    {
        $this->requireManager();
        $data = $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']);
        $tpl = $svc->saveFromProject($project, $data['name'], $data['description'] ?? null);
        return redirect()->route('wbs-templates.show', $tpl)->with('ok', 'Template WBS disimpan. Pakai lewat "Salin dari template" saat membuat proyek baru.');
    }

    public function update(Request $request, WbsTemplate $wbsTemplate)
    {
        $this->requireManager();
        $wbsTemplate->update($request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string',
            'food_group' => 'nullable|in:'.implode(',', array_keys(Template::GROUPS))]));
        return back()->with('ok', 'Template diperbarui.');
    }

    public function destroy(WbsTemplate $wbsTemplate)
    {
        $this->requireManager();
        $wbsTemplate->delete();
        return redirect()->route('wbs-templates.index')->with('ok', 'Template dihapus.');
    }
}
