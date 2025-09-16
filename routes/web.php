<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\OnboardingController;

Route::get('/', fn() => view('home'))->name('home');
Route::get('/auth/google/redirect', [SocialController::class,'googleRedirect'])->name('google.redirect');
Route::get('/auth/google/callback', [SocialController::class,'googleCallback'])->name('google.callback');

Route::get('/auth/facebook/redirect', [SocialController::class,'facebookRedirect'])->name('facebook.redirect');
Route::get('/auth/facebook/callback', [SocialController::class,'facebookCallback'])->name('facebook.callback');

Route::post('/logout', [SocialController::class,'logout'])->middleware('auth')->name('logout');
Route::get('/login', function () {
    return view('auth.login');
})->name('login');
Route::get('/debug-google-redirect', function () {
    return config('services.google.redirect');
});

Route::middleware('auth')->group(function () {
    // đã có:
    Route::get('/select-role',  [RoleController::class, 'show'])->name('role.select');
    Route::post('/select-role', [RoleController::class, 'store'])->name('role.store');

    // Onboarding
    Route::get('/onboarding/name',  [OnboardingController::class, 'showName'])->name('onb.name.show');
    Route::post('/onboarding/name', [OnboardingController::class, 'storeName'])->name('onb.name.store');

    Route::get('/onboarding/skills',  [OnboardingController::class, 'showSkills'])->name('onb.skills.show');
    Route::post('/onboarding/skills', [OnboardingController::class, 'storeSkills'])->name('onb.skills.store');
});
