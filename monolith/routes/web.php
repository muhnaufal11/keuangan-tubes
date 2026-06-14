<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PemasukanController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\RekeningController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UtangController;
use App\Http\Controllers\Admin\LandingPageController;

use App\Models\Setting;

use App\Http\Controllers\PublicController;

Route::get('/', [PublicController::class, 'index'])->name('home');

// AUTH
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
// Lupa / Reset Password
Route::get('/password/forgot', [AuthController::class, 'showForgotForm'])->name('password.request');
Route::post('/password/forgot', [AuthController::class, 'sendResetLink'])->name('password.email');
// Verify security question answer and reset password
Route::post('/password/answer', [AuthController::class, 'verifySecurityAnswer'])->name('password.answer');
Route::get('/password/reset/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.update');

// HELP / LIVE CHAT (User)
Route::middleware('auth')->group(function () {
    Route::get('/help', [App\Http\Controllers\HelpController::class, 'index'])->name('help.index');
    // Popup API (Must be before {id} wildcard)
    Route::get('/help/active', [App\Http\Controllers\HelpController::class, 'active'])->name('help.active');

    Route::post('/help/start', [App\Http\Controllers\HelpController::class, 'start'])->name('help.start');
    Route::get('/help/{id}', [App\Http\Controllers\HelpController::class, 'show'])->name('help.show');
    Route::get('/help/messages/{id}', [App\Http\Controllers\HelpController::class, 'messages'])->name('help.messages');
    Route::post('/help/messages', [App\Http\Controllers\HelpController::class, 'sendMessage'])->name('help.send');

    Route::post('/help/popup-send', [App\Http\Controllers\HelpController::class, 'popupSend'])->name('help.popup.send');
});

// DASHBOARD & FITUR (Harus Login)
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/tarik-tunai', [DashboardController::class, 'tarikTunai'])->name('dashboard.tarik');

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::delete('/notifications/{notification}', [App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/api/notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'getUnreadCount'])->name('api.notifications.unread');

    Route::resource('rekening', RekeningController::class);

    // --- PERBAIKAN DI SINI (Tambahkan Route Kategori) ---
    Route::post('/pemasukan/kategori', [PemasukanController::class, 'storeKategori'])->name('pemasukan.store_kategori');
    Route::resource('pemasukan', PemasukanController::class);

    Route::post('/pengeluaran/kategori', [PengeluaranController::class, 'storeKategori'])->name('pengeluaran.store_kategori');
    Route::resource('pengeluaran', PengeluaranController::class);
    Route::resource('transfer', TransferController::class);

    Route::post('/utang/bayar', [UtangController::class, 'bayar'])->name('utang.bayar');
    Route::get('/utang/{id}/riwayat', [UtangController::class, 'getRiwayat'])->name('utang.riwayat');
    Route::resource('utang', UtangController::class);
    Route::get('/laporan', [App\Http\Controllers\LaporanController::class, 'index'])->name('laporan.index');

});

// ADMIN PANEL (Harus Admin)
Route::prefix('admin')->middleware(['auth','is_admin'])->group(function () {
    Route::get('/', [App\Http\Controllers\AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/insights', [App\Http\Controllers\AdminController::class, 'userInsights'])->name('admin.insights');
    Route::get('/users', [App\Http\Controllers\AdminController::class, 'usersIndex'])->name('admin.users.index');
    Route::get('/users/{user}', [App\Http\Controllers\AdminController::class, 'usersShow'])->name('admin.users.show');
    Route::post('/users/{user}/ban', [App\Http\Controllers\AdminController::class, 'ban'])->name('admin.users.ban');
    Route::post('/users/{user}/reset-password', [App\Http\Controllers\AdminController::class, 'resetPassword'])->name('admin.users.reset_password');

    // Default Categories CRUD
    Route::get('/default-categories', [App\Http\Controllers\Admin\DefaultCategoryController::class, 'index'])->name('admin.default_categories.index');
    Route::get('/default-categories/create', [App\Http\Controllers\Admin\DefaultCategoryController::class, 'create'])->name('admin.default_categories.create');
    Route::post('/default-categories/sync', [App\Http\Controllers\Admin\DefaultCategoryController::class, 'syncToUsers'])->name('admin.default_categories.sync');
    Route::get('/default-categories/select-users', [App\Http\Controllers\Admin\DefaultCategoryController::class, 'selectUsersForSync'])->name('admin.default_categories.select_users');
    Route::post('/default-categories/bulk-sync', [App\Http\Controllers\Admin\DefaultCategoryController::class, 'bulkSync'])->name('admin.default_categories.bulk_sync');
    Route::post('/default-categories', [App\Http\Controllers\Admin\DefaultCategoryController::class, 'store'])->name('admin.default_categories.store');
    Route::get('/default-categories/{defaultCategory}/edit', [App\Http\Controllers\Admin\DefaultCategoryController::class, 'edit'])->name('admin.default_categories.edit');
    Route::put('/default-categories/{defaultCategory}', [App\Http\Controllers\Admin\DefaultCategoryController::class, 'update'])->name('admin.default_categories.update');
    Route::delete('/default-categories/{defaultCategory}', [App\Http\Controllers\Admin\DefaultCategoryController::class, 'destroy'])->name('admin.default_categories.destroy');

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('admin.notifications.index');
    Route::get('/notifications/create', [App\Http\Controllers\Admin\NotificationController::class, 'create'])->name('admin.notifications.create');
    Route::post('/notifications', [App\Http\Controllers\Admin\NotificationController::class, 'store'])->name('admin.notifications.store');
    Route::delete('/notifications/{notification}', [App\Http\Controllers\Admin\NotificationController::class, 'destroy'])->name('admin.notifications.destroy');

    // Activity Logs
    Route::get('/logs', [App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('admin.logs.index');

    // Database Backups
    Route::get('/backups', [App\Http\Controllers\Admin\BackupController::class, 'index'])->name('admin.backups.index');
    Route::post('/backups', [App\Http\Controllers\Admin\BackupController::class, 'store'])->name('admin.backups.store');
    Route::get('/backups/{backup}/download', [App\Http\Controllers\Admin\BackupController::class, 'download'])->name('admin.backups.download');
    Route::delete('/backups/{backup}', [App\Http\Controllers\Admin\BackupController::class, 'destroy'])->name('admin.backups.destroy');
    Route::post('/backups/restore', [App\Http\Controllers\Admin\BackupController::class, 'restore'])->name('admin.backups.restore');

    // Tips (CRUD)
    Route::get('/tips', [App\Http\Controllers\Admin\TipController::class, 'index'])->name('admin.tips.index');
    Route::get('/tips/create', [App\Http\Controllers\Admin\TipController::class, 'create'])->name('admin.tips.create');
    Route::post('/tips', [App\Http\Controllers\Admin\TipController::class, 'store'])->name('admin.tips.store');
    Route::get('/tips/{tip}/edit', [App\Http\Controllers\Admin\TipController::class, 'edit'])->name('admin.tips.edit');
    Route::put('/tips/{tip}', [App\Http\Controllers\Admin\TipController::class, 'update'])->name('admin.tips.update');
    Route::delete('/tips/{tip}', [App\Http\Controllers\Admin\TipController::class, 'destroy'])->name('admin.tips.destroy');

    // System (maintenance)
    Route::get('/system/maintenance', [App\Http\Controllers\Admin\SystemController::class, 'showMaintenance'])->name('admin.system.maintenance');
    Route::post('/system/maintenance/toggle', [App\Http\Controllers\Admin\SystemController::class, 'toggleMaintenance'])->name('admin.system.maintenance.toggle');

    // Landing Page
    Route::get('/landing', [LandingPageController::class, 'index'])->name('admin.landing.index');
    Route::post('/landing', [LandingPageController::class, 'update'])->name('admin.landing.update');

    // Help / Live Chat (Admin)
    // Popup API (Must be before {id} wildcard)
    Route::get('/help/active', [App\Http\Controllers\Admin\HelpController::class, 'active'])->name('admin.help.active');
    Route::post('/popup-send', [App\Http\Controllers\Admin\HelpController::class, 'popupSend'])->name('admin.help.popup.send');

    Route::get('/help', [App\Http\Controllers\Admin\HelpController::class, 'index'])->name('admin.help.index');
    Route::get('/help/{id}', [App\Http\Controllers\Admin\HelpController::class, 'show'])->name('admin.help.show');
    Route::post('/help/messages', [App\Http\Controllers\Admin\HelpController::class, 'sendMessage'])->name('admin.help.send');
    Route::post('/help/start/{user}', [App\Http\Controllers\Admin\HelpController::class, 'startSession'])->name('admin.help.start');
    Route::post('/help/{id}/close', [App\Http\Controllers\Admin\HelpController::class, 'close'])->name('admin.help.close');

    
});
