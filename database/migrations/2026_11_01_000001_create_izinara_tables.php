<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Struktur data Izinara.
 *
 * Master: bagian, peraturan, jenis izin + langkah standar + dokumen wajib, template per kelompok, item code, pemasok.
 * Proyek: proyek perizinan (dari kebutuhan sampai izin terbit), PIC per bagian, paket izin & tugas (WBS),
 * dependensi, milestone, referensi peraturan per tugas, komentar, dokumen, register izin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name');
            $t->unsignedInteger('sequence')->default(10);
            $t->text('responsibility')->nullable();
            $t->unsignedTinyInteger('weekly_capacity')->default(25)->comment('Kapasitas hari-orang per minggu');
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->string('username', 60)->nullable()->unique()->after('name');
            $t->foreignId('party_id')->nullable()->after('email')->constrained('parties')->nullOnDelete();
            $t->string('role', 20)->default('pic')->after('party_id')->comment('admin, manajer, pic, viewer');
            $t->string('job_title')->nullable()->after('role');
            $t->string('phone', 40)->nullable()->after('job_title');
            $t->unsignedTinyInteger('weekly_capacity')->default(5)->after('phone')->comment('Hari kerja efektif per minggu');
            $t->boolean('active')->default(true)->after('weekly_capacity');
        });

        Schema::create('regulations', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('name');
            $t->string('title', 500);
            $t->string('issuer')->nullable();
            $t->string('year', 10)->nullable();
            $t->string('status', 30)->default('berlaku')->comment('berlaku, berlaku_diubah, perlu_konfirmasi, dicabut');
            $t->text('note')->nullable();
            $t->string('url', 500)->nullable();
            $t->foreignId('replaced_by_id')->nullable()->constrained('regulations')->nullOnDelete();
            $t->date('checked_at')->nullable();
            $t->timestamps();
        });

        Schema::create('permit_types', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('name');
            $t->unsignedInteger('sequence')->default(10);
            $t->string('authority')->nullable();
            $t->string('system')->nullable();
            $t->foreignId('party_id')->constrained('parties');
            $t->string('scope', 20)->default('product')->comment('company, supplier, product, shipment');
            $t->unsignedInteger('lead_days')->default(7);
            $t->unsignedInteger('validity_months')->default(0);
            $t->text('legal_basis')->nullable();
            $t->text('description')->nullable();
            $t->json('prerequisites')->nullable()->comment('Kode jenis izin yang harus selesai lebih dulu');
            $t->timestamps();
        });

        Schema::create('permit_type_regulation', function (Blueprint $t) {
            $t->foreignId('permit_type_id')->constrained()->cascadeOnDelete();
            $t->foreignId('regulation_id')->constrained()->cascadeOnDelete();
            $t->primary(['permit_type_id', 'regulation_id']);
        });

        Schema::create('permit_type_steps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('permit_type_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('sequence')->default(10);
            $t->string('name');
            $t->foreignId('party_id')->constrained('parties');
            $t->unsignedInteger('duration_days')->default(1);
            $t->text('instructions')->nullable();
            $t->string('deliverable')->nullable();
            $t->boolean('is_submission')->default(false)->comment('Langkah pengajuan ke instansi');
            $t->timestamps();
        });

        Schema::create('permit_type_step_regulation', function (Blueprint $t) {
            $t->foreignId('permit_type_step_id')->constrained()->cascadeOnDelete();
            $t->foreignId('regulation_id')->constrained()->cascadeOnDelete();
            $t->string('article')->nullable()->comment('Pasal/bagian yang dirujuk');
            $t->primary(['permit_type_step_id', 'regulation_id']);
        });

        Schema::create('doc_specs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('permit_type_step_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->text('criteria')->nullable();
            $t->boolean('check_expiry')->default(false);
            $t->foreignId('verifier_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('templates', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('name');
            $t->string('food_group', 20);
            $t->unsignedInteger('sequence')->default(10);
            $t->text('description')->nullable();
            $t->timestamps();
        });

        Schema::create('template_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('template_id')->constrained()->cascadeOnDelete();
            $t->foreignId('permit_type_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('sequence')->default(10);
            $t->boolean('mandatory')->default(true);
            $t->unique(['template_id', 'permit_type_id']);
        });

        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('kind', 20)->default('exporter')->comment('exporter, establishment, local');
            $t->string('country', 60)->nullable();
            $t->string('establishment_no', 60)->nullable();
            $t->foreignId('parent_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('items', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('name');
            $t->string('origin', 60)->nullable();
            $t->string('category', 40)->nullable();
            $t->string('item_group', 60)->nullable();
            $t->string('subgroup', 60)->nullable();
            $t->string('hs_code', 20)->nullable();
            $t->boolean('non_halal')->default(false);
            $t->string('product_kind', 60)->nullable();
            $t->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
            $t->index('template_id');
        });

        Schema::create('wbs_templates', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('name');
            $t->string('food_group', 20)->nullable();
            $t->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $t->text('description')->nullable();
            $t->json('payload')->comment('Struktur WBS: paket, tugas, milestone, durasi, dependensi, peraturan, dokumen');
            $t->unsignedBigInteger('source_project_id')->nullable();
            $t->unsignedInteger('times_used')->default(0);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('projects', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name');
            $t->string('trigger', 20)->default('pengapalan')->comment('pengapalan, produk_baru, perpanjangan, perubahan');
            $t->text('need_description')->nullable()->comment('Uraian kebutuhan izin');
            $t->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('wbs_template_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 20)->default('rencana')->comment('rencana, berjalan, tertahan, selesai, batal');
            $t->string('priority', 10)->default('normal')->comment('rendah, normal, tinggi, kritis');
            $t->string('po_ref', 60)->nullable();
            $t->date('etd')->nullable();
            $t->date('eta')->nullable();
            $t->date('start_date');
            $t->date('target_date')->comment('Tenggat semua izin terbit');
            $t->date('baseline_end')->nullable();
            $t->date('planned_end')->nullable();
            $t->date('actual_end')->nullable();
            $t->unsignedTinyInteger('progress')->default(0);
            $t->json('covered_permits')->nullable()->comment('Izin yang sudah dimiliki dan dipakai ulang');
            $t->timestamps();
        });

        Schema::create('item_project', function (Blueprint $t) {
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->foreignId('item_id')->constrained()->cascadeOnDelete();
            $t->decimal('qty_kg', 12, 2)->default(0);
            $t->primary(['project_id', 'item_id']);
        });

        Schema::create('project_pics', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->foreignId('party_id')->constrained('parties');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('backup_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->unique(['project_id', 'party_id']);
        });

        Schema::create('tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('tasks')->cascadeOnDelete();
            $t->string('kind', 12)->default('task')->comment('phase, package, task, milestone');
            $t->string('wbs', 20)->nullable();
            $t->unsignedInteger('sequence')->default(10);
            $t->string('name');
            $t->foreignId('permit_type_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('permit_type_step_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $t->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedInteger('duration_days')->default(1);
            $t->date('planned_start')->nullable();
            $t->date('planned_end')->nullable();
            $t->date('baseline_start')->nullable();
            $t->date('baseline_end')->nullable();
            $t->date('actual_start')->nullable();
            $t->date('actual_end')->nullable();
            $t->date('not_before')->nullable()->comment('Tidak boleh mulai sebelum tanggal ini (mis. ETA)');
            $t->unsignedTinyInteger('progress')->default(0);
            $t->string('status', 20)->default('belum')->comment('belum, berjalan, menunggu, selesai, terhambat');
            $t->boolean('is_critical')->default(false);
            $t->integer('slack_days')->default(0);
            $t->text('instructions')->nullable();
            $t->string('deliverable')->nullable();
            $t->text('blocker_note')->nullable();
            $t->timestamps();
            $t->index(['project_id', 'sequence']);
            $t->index(['assignee_id', 'status']);
        });

        Schema::create('task_dependencies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $t->foreignId('depends_on_id')->constrained('tasks')->cascadeOnDelete();
            $t->integer('lag_days')->default(0);
            $t->unique(['task_id', 'depends_on_id']);
        });

        Schema::create('regulation_task', function (Blueprint $t) {
            $t->foreignId('task_id')->constrained()->cascadeOnDelete();
            $t->foreignId('regulation_id')->constrained()->cascadeOnDelete();
            $t->string('article')->nullable();
            $t->primary(['task_id', 'regulation_id']);
        });

        Schema::create('task_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained()->cascadeOnDelete();
            $t->foreignId('doc_spec_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('path')->nullable();
            $t->string('original_name')->nullable();
            $t->unsignedInteger('size')->default(0);
            $t->string('status', 20)->default('belum')->comment('belum, diajukan, terverifikasi, ditolak');
            $t->date('expiry_date')->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('verified_at')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
        });

        Schema::create('memos', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('task_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('task_document_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('from_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $t->foreignId('to_party_id')->constrained('parties');
            $t->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('subject');
            $t->text('body');
            $t->string('priority', 10)->default('normal');
            $t->date('due_date')->nullable();
            $t->string('status', 20)->default('terbuka')->comment('terbuka, dijawab, selesai');
            $t->timestamp('first_response_at')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('task_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('task_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('task_document_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('memo_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('kind', 20)->default('komentar')->comment('komentar, perlu_tindakan, info');
            $t->text('body');
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('comment_mentions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_comment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('party_id')->nullable()->constrained('parties')->cascadeOnDelete();
        });

        Schema::create('notices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('type', 30);
            $t->string('title');
            $t->text('body')->nullable();
            $t->string('url', 500)->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'read_at']);
        });

        Schema::create('attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained()->cascadeOnDelete();
            $t->foreignId('task_document_id')->nullable()->constrained()->nullOnDelete();
            $t->string('path');
            $t->string('original_name');
            $t->string('mime', 120)->nullable();
            $t->string('extension', 12)->nullable();
            $t->unsignedBigInteger('size')->default(0);
            $t->string('description')->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('licenses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('permit_type_id')->constrained();
            $t->string('number');
            $t->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $t->date('issued_at')->nullable();
            $t->date('expires_at')->nullable();
            $t->decimal('quota_kg', 14, 2)->nullable();
            $t->decimal('used_kg', 14, 2)->default(0);
            $t->string('note')->nullable();
            $t->timestamps();
        });

        Schema::create('item_license', function (Blueprint $t) {
            $t->foreignId('license_id')->constrained()->cascadeOnDelete();
            $t->foreignId('item_id')->constrained()->cascadeOnDelete();
            $t->primary(['license_id', 'item_id']);
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('task_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('action', 60);
            $t->text('detail')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'item_license', 'licenses', 'attachments', 'notices', 'comment_mentions', 'task_comments', 'memos', 'task_documents', 'regulation_task',
            'task_dependencies', 'tasks', 'project_pics', 'item_project', 'projects', 'items', 'suppliers',
            'template_lines', 'wbs_templates', 'templates', 'doc_specs', 'permit_type_step_regulation', 'permit_type_steps',
            'permit_type_regulation', 'permit_types', 'regulations'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('party_id');
            $t->dropColumn(['username', 'role', 'job_title', 'phone', 'weekly_capacity', 'active']);
        });
        Schema::dropIfExists('parties');
    }
};
