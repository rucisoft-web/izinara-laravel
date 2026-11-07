<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Notice;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\Template;
use App\Models\User;
use App\Models\WbsTemplate;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IzinaraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('username', 'admin')->first();
    }

    private function newProject(array $extra = []): Project
    {
        $tpl = Template::where('code', 'T_HALAL')->first() ?? Template::first();
        $this->actingAs($this->admin())->post('/proyek', array_merge([
            'name' => 'Uji sertifikasi halal', 'trigger' => array_key_first(Project::TRIGGERS), 'priority' => 'normal',
            'template_id' => $tpl->id, 'start_date' => '2026-11-02', 'target_date' => '2027-01-30', 'source' => 'otomatis',
        ], $extra))->assertSessionHasNoErrors()->assertRedirect();

        return Project::latest('id')->first();
    }

    public function test_guest_sees_landing_and_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Izinara');
        $this->get('/login')->assertOk();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_with_username(): void
    {
        $this->post('/login', ['login' => 'admin', 'password' => 'admin'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_project_is_built_with_wbs_regulations_and_schedule(): void
    {
        $p = $this->newProject();
        $this->assertGreaterThan(3, $p->tasks()->count());
        $this->assertNotNull($p->fresh()->planned_end);
        $this->assertTrue($p->tasks()->where('kind', 'milestone')->exists());
        $task = $p->tasks()->where('kind', 'task')->whereNotNull('permit_type_step_id')->first();
        $this->assertNotEmpty($task->effectiveRegulations());
        foreach (['ringkasan', 'gantt', 'wbs', 'pic', 'dokumen', 'diskusi', 'peraturan', 'riwayat'] as $tab) {
            $this->get('/proyek/'.$p->id.'?tab='.$tab)->assertOk();
        }
        $this->getJson('/proyek/'.$p->id.'/gantt.json')->assertOk();
    }

    public function test_copy_wbs_template_to_new_project(): void
    {
        $src = $this->newProject();
        $this->post('/proyek/'.$src->id.'/simpan-template', ['name' => 'Template uji'])->assertRedirect();
        $tpl = WbsTemplate::latest('id')->first();
        $this->assertNotNull($tpl);
        $copy = $this->newProject(['name' => 'Salinan', 'source' => 'wbs', 'wbs_template_id' => $tpl->id]);
        $this->assertSame($src->tasks()->count(), $copy->tasks()->count());
    }

    public function test_comment_mention_notifies_party_members(): void
    {
        $p = $this->newProject();
        $task = $p->tasks()->where('kind', 'task')->first();
        $party = Party::first();
        $member = User::factory()->create(['username' => 'uji.anggota', 'party_id' => $party->id, 'role' => 'pic']);
        $this->post('/tugas/'.$task->id.'/komentar', ['body' => 'Dokumen belum lengkap @'.$party->code, 'kind' => 'perlu_tindakan'])
            ->assertRedirect();
        $this->assertTrue(Notice::where('user_id', $member->id)->exists());
    }

    public function test_attachment_upload_and_download(): void
    {
        Storage::fake('local');
        $p = $this->newProject();
        $task = $p->tasks()->where('kind', 'task')->first();
        $this->post('/tugas/'.$task->id.'/lampiran', ['files' => [
            UploadedFile::fake()->create('coa.pdf', 120, 'application/pdf'),
            UploadedFile::fake()->create('packing.xlsx', 30),
        ]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(2, Attachment::where('task_id', $task->id)->count());
        $this->get('/lampiran/'.Attachment::first()->id.'/unduh')->assertOk();
        $this->post('/tugas/'.$task->id.'/lampiran', ['files' => [UploadedFile::fake()->create('virus.exe', 10)]])
            ->assertSessionHasErrors();
    }

    public function test_memo_between_departments(): void
    {
        $parties = Party::take(2)->get();
        $this->actingAs($this->admin())->post('/memo', ['to_party_id' => $parties[1]->id, 'subject' => 'Mohon CoA',
            'body' => 'Mohon dikirim', 'priority' => 'normal'])->assertRedirect();
        $this->get('/memo')->assertOk();
    }

    public function test_main_pages_render(): void
    {
        $this->newProject();
        foreach (['/dashboard', '/tugas-saya', '/proyek', '/proyek/baru', '/gantt', '/beban-kerja', '/proyeksi', '/register-izin',
            '/template-wbs', '/peraturan', '/jenis-izin', '/kelompok-izin', '/item-code', '/pemasok', '/pengguna', '/memo', '/kotak-masuk', '/panduan'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
