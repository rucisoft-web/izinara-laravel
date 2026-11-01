<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\GanttController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\MemoController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RegulationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WbsTemplateController;
use App\Http\Controllers\WorkloadController;
use Illuminate\Support\Facades\Route;

// Wizard instalasi (hanya aktif sebelum terpasang, lihat EnsureInstalled)
Route::get('/install', [InstallController::class, 'show']);
Route::post('/install', [InstallController::class, 'store']);

// Halaman publik: landing page & pusat bantuan (bisa dibuka calon pengguna tanpa login)
Route::get('/', [HelpController::class, 'landing'])->name('landing');
Route::get('/panduan', [HelpController::class, 'index'])->name('help.index');
Route::get('/panduan/{topic}', [HelpController::class, 'show'])->name('help.show');

Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/tugas-saya', [TaskController::class, 'mine'])->name('tasks.mine');

    // Proyek perizinan
    Route::get('/proyek', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/proyek/baru', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/proyek', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/proyek/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/proyek/{project}/ubah', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/proyek/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/proyek/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/proyek/{project}/jadwal-ulang', [ProjectController::class, 'reschedule'])->name('projects.reschedule');
    Route::post('/proyek/{project}/baseline', [ProjectController::class, 'baseline'])->name('projects.baseline');
    Route::post('/proyek/{project}/pic', [ProjectController::class, 'updatePics'])->name('projects.pics');
    Route::post('/proyek/{project}/salin-template', [ProjectController::class, 'copyTemplate'])->name('projects.copy-template');
    Route::post('/proyek/{project}/simpan-template', [WbsTemplateController::class, 'storeFromProject'])->name('projects.save-template');
    Route::get('/proyek/{project}/gantt.json', [GanttController::class, 'project'])->name('projects.gantt-data');
    Route::post('/proyek/{project}/komentar', [CommentController::class, 'storeProject'])->name('projects.comment');

    // Tugas / kegiatan
    Route::post('/proyek/{project}/tugas', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tugas/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::put('/tugas/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tugas/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tugas/{task}/tanggal', [TaskController::class, 'updateDates'])->name('tasks.dates');
    Route::post('/tugas/{task}/peraturan', [TaskController::class, 'updateRegulations'])->name('tasks.regulations');
    Route::post('/tugas/{task}/komentar', [CommentController::class, 'storeTask'])->name('tasks.comment');
    Route::post('/tugas/{task}/lampiran', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::post('/tugas/{task}/dokumen', [DocumentController::class, 'store'])->name('documents.store');
    Route::post('/tugas/{task}/izin-terbit', [TaskController::class, 'issueLicense'])->name('tasks.issue');
    Route::post('/dokumen/{document}/verifikasi', [DocumentController::class, 'verify'])->name('documents.verify');
    Route::post('/dokumen/{document}/ajukan', [DocumentController::class, 'submit'])->name('documents.submit');
    Route::post('/dokumen/{document}/komentar', [CommentController::class, 'storeDocument'])->name('documents.comment');
    Route::post('/komentar/{comment}/selesai', [CommentController::class, 'resolve'])->name('comments.resolve');
    Route::get('/lampiran/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
    Route::get('/lampiran/{attachment}/unduh', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('/lampiran/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    // Komunikasi antar bagian
    Route::get('/memo', [MemoController::class, 'index'])->name('memos.index');
    Route::post('/memo', [MemoController::class, 'store'])->name('memos.store');
    Route::get('/memo/{memo}', [MemoController::class, 'show'])->name('memos.show');
    Route::post('/memo/{memo}/balas', [MemoController::class, 'reply'])->name('memos.reply');
    Route::get('/kotak-masuk', [NoticeController::class, 'index'])->name('notices.index');
    Route::get('/kotak-masuk/{notice}', [NoticeController::class, 'open'])->name('notices.open');
    Route::post('/kotak-masuk/baca-semua', [NoticeController::class, 'readAll'])->name('notices.read-all');

    // Gantt, beban kerja, proyeksi
    Route::get('/gantt', [GanttController::class, 'portfolio'])->name('gantt.portfolio');
    Route::get('/beban-kerja', [WorkloadController::class, 'index'])->name('workload');
    Route::get('/proyeksi', [ForecastController::class, 'index'])->name('forecast');

    // Register izin
    Route::get('/register-izin', [LicenseController::class, 'index'])->name('licenses.index');
    Route::post('/register-izin', [LicenseController::class, 'store'])->name('licenses.store');
    Route::post('/register-izin/{license}/perpanjang', [LicenseController::class, 'renew'])->name('licenses.renew');

    // Template WBS
    Route::get('/template-wbs', [WbsTemplateController::class, 'index'])->name('wbs-templates.index');
    Route::get('/template-wbs/{wbsTemplate}', [WbsTemplateController::class, 'show'])->name('wbs-templates.show');
    Route::put('/template-wbs/{wbsTemplate}', [WbsTemplateController::class, 'update'])->name('wbs-templates.update');
    Route::delete('/template-wbs/{wbsTemplate}', [WbsTemplateController::class, 'destroy'])->name('wbs-templates.destroy');

    // Master data
    Route::get('/peraturan', [RegulationController::class, 'index'])->name('regulations.index');
    Route::post('/peraturan', [RegulationController::class, 'store'])->name('regulations.store');
    Route::put('/peraturan/{regulation}', [RegulationController::class, 'update'])->name('regulations.update');
    Route::get('/jenis-izin', [MasterController::class, 'permitTypes'])->name('permit-types.index');
    Route::get('/jenis-izin/{permitType}', [MasterController::class, 'permitType'])->name('permit-types.show');
    Route::put('/langkah/{step}', [MasterController::class, 'updateStep'])->name('steps.update');
    Route::get('/kelompok-izin', [MasterController::class, 'templates'])->name('templates.index');
    Route::get('/item-code', [MasterController::class, 'items'])->name('items.index');
    Route::post('/item-code/impor', [MasterController::class, 'importItems'])->name('items.import');
    Route::get('/pemasok', [MasterController::class, 'suppliers'])->name('suppliers.index');
    Route::post('/pemasok', [MasterController::class, 'storeSupplier'])->name('suppliers.store');

    // Pengguna & bagian
    Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
    Route::post('/pengguna', [UserController::class, 'store'])->name('users.store');
    Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
    Route::put('/bagian/{party}', [UserController::class, 'updateParty'])->name('parties.update');
});
