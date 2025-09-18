<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\OnboardingController;
// routes/web.php
use App\Http\Controllers\JobController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\FreelancerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
Route::get('/', fn() => view('home'))->name('home');
Route::get('/auth/google/redirect', [SocialController::class, 'googleRedirect'])->name('google.redirect');
Route::get('/auth/google/callback', [SocialController::class, 'googleCallback'])->name('google.callback');

Route::get('/auth/facebook/redirect', [SocialController::class, 'facebookRedirect'])->name('facebook.redirect');
Route::get('/auth/facebook/callback', [SocialController::class, 'facebookCallback'])->name('facebook.callback');

Route::post('/logout', [SocialController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/login', function () {
    return view('auth.login');
})->name('login');
// Github Login
Route::get('auth/github/redirect', [SocialController::class, 'githubRedirect'])->name('github.redirect');
Route::get('auth/github/callback', [SocialController::class, 'githubCallback']);


Route::middleware('auth')->group(function () {
    // đã có:
    Route::get('/select-role', [RoleController::class, 'show'])->name('role.select');
    Route::post('/select-role', [RoleController::class, 'store'])->name('role.store');

    // Onboarding
    Route::get('/onboarding/name', [OnboardingController::class, 'showName'])->name('onb.name.show');
    Route::post('/onboarding/name', [OnboardingController::class, 'storeName'])->name('onb.name.store');

    Route::get('/onboarding/skills', [OnboardingController::class, 'showSkills'])->name('onb.skills.show');
    Route::post('/onboarding/skills', [OnboardingController::class, 'storeSkills'])->name('onb.skills.store');
});
Route::middleware('auth')->group(function () {
    // Freelancer vào chat (job_id)
    Route::get('/jobs/{job}/chat', [MessageController::class, 'chat'])->name('chat.job');

    // Chủ job vào chat với freelancer cụ thể
    Route::get('/jobs/{job}/chat/{freelancer}', [MessageController::class, 'chatWithFreelancer'])->name('chat.with');

    // Gửi tin nhắn
    Route::post('/messages/send', [MessageController::class, 'send'])->name('messages.send');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::get('/settings', [SettingsController::class, 'index'])
        ->name('settings.index');

    Route::put('/settings/my-info', [SettingsController::class, 'updateMyInfo'])
        ->name('settings.myinfo.update');

    Route::put('/settings/security', [SettingsController::class, 'updateSecurity'])
        ->name('settings.security.update');

    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])
        ->name('settings.notifications.update');

    // tuỳ chọn: đổi gói
    Route::post('/settings/membership/change', [SettingsController::class, 'changeMembership'])
        ->name('settings.membership.change');
});

// Hiển thị danh sách công việc
Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');

// Hiển thị danh sách freelancer
Route::get('/freelancers', [FreelancerController::class, 'index'])->name('freelancers.index');

// Hiển thị trang liên hệ
Route::get('/contact', function () {
    return view('contact');
});
// routes/web.php
Route::middleware('auth')->prefix('settings')->name('settings.')->group(function () {
    Route::redirect('/', '/settings/my-info'); // mặc định

    Route::get('/my-info',       [SettingsController::class, 'myInfo'])->name('myinfo');
    Route::put('/my-info',       [SettingsController::class, 'updateMyInfo'])->name('myinfo.update');

    Route::get('/billing',       [SettingsController::class, 'billing'])->name('billing');

    Route::get('/security',      [SettingsController::class, 'security'])->name('security');
    Route::put('/security',      [SettingsController::class, 'updateSecurity'])->name('security.update');

    Route::get('/membership',    [SettingsController::class, 'membership'])->name('membership');
    Route::post('/membership',   [SettingsController::class, 'changeMembership'])->name('membership.change');

    Route::get('/teams',         [SettingsController::class, 'teams'])->name('teams');
    Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
    Route::put('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');

    Route::get('/members',       [SettingsController::class, 'members'])->name('members');
    Route::get('/tax',           [SettingsController::class, 'tax'])->name('tax');
    Route::get('/connected',     [SettingsController::class, 'connected'])->name('connected');
    Route::get('/appeals',       [SettingsController::class, 'appeals'])->name('appeals');
});
