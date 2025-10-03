<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\OnboardingController;
// routes/web.php
use App\Http\Controllers\JobController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\FreelancerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Client\JobPostController;
use App\Http\Controllers\Client\JobAIFormController;
use App\Http\Controllers\Client\JobWizardController;
use App\Http\Controllers\Client\MyJobsController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Models\Account;
use App\Http\Controllers\UpgradeController;
// Routes đăng ký 
Route::middleware('guest')->group(function () {
    Route::get('/register/role', [RegisterController::class, 'showRole'])->name('register.role.show');
    Route::post('/register/role', [RegisterController::class, 'storeRole'])->name('register.role.store');

    // Form đăng ký (đã có sẵn)
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register.show');
    Route::post('/register', [RegisterController::class, 'register'])->name('register');
});

// Routes đăng nhập bằng google và github
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/auth/google/redirect', [SocialController::class, 'googleRedirect'])->name('google.redirect');
Route::get('/auth/google/callback', [SocialController::class, 'googleCallback'])->name('google.callback');
Route::get('auth/github/redirect', [SocialController::class, 'githubRedirect'])->name('github.redirect');
Route::get('auth/github/callback', [SocialController::class, 'githubCallback']);

Route::post('/logout', [SocialController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/login', function () {
    return view('auth.login');
})->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])
    ->name('login');
Route::middleware('auth')->group(function () {
    // đã có:
    Route::get('/select-role', [RoleController::class, 'show'])->name('role.select');
    Route::post('/select-role', [RoleController::class, 'store'])->name('role.store');

    // Onboarding
    Route::get('/onboarding/name', [OnboardingController::class, 'showName'])->name('onb.name.show');
    Route::post('/onboarding/name', [OnboardingController::class, 'storeName'])->name('onb.name.store');

    Route::get('/onboarding/skills', [OnboardingController::class, 'showSkills'])->name('onb.skills.show');
    Route::post('/onboarding/skills', [OnboardingController::class, 'storeSkills'])->name('onb.skills.store');
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

Route::get('/jobs/{job}', [JobController::class, 'show'])->name('jobs.show');

// Hiển thị danh sách freelancer
Route::get('/freelancers', [FreelancerController::class, 'index'])->name('freelancers.index');

// Hiển thị trang liên hệ
Route::get('/contact', function () {
    return view('contact');
});
// routes/web.php
Route::middleware('auth')->prefix('settings')->name('settings.')->group(function () {
    Route::redirect('/', '/settings/my-info'); // mặc định

    Route::get('/my-info', [SettingsController::class, 'myInfo'])->name('myinfo');
    Route::put('/my-info', [SettingsController::class, 'updateMyInfo'])->name('myinfo.update');

    Route::get('/billing', [SettingsController::class, 'billing'])->name('billing');

    Route::get('/security', [SettingsController::class, 'security'])->name('security');
    Route::put('/security', [SettingsController::class, 'updateSecurity'])->name('security.update');

    Route::get('/membership', [SettingsController::class, 'membership'])->name('membership');
    Route::post('/membership', [SettingsController::class, 'changeMembership'])->name('membership.change');

    Route::get('/teams', [SettingsController::class, 'teams'])->name('teams');
    Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
    Route::put('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');

    Route::get('/members', [SettingsController::class, 'members'])->name('members');
    Route::get('/tax', [SettingsController::class, 'tax'])->name('tax');
    Route::get('/connected', [SettingsController::class, 'connected'])->name('connected');
    Route::get('/appeals', [SettingsController::class, 'appeals'])->name('appeals');
});
// Routes Xác minh Email
Route::get('/email/verify', function () {
    return view('auth.verify-email'); // tạo view thông báo “hãy kiểm tra email”
})->middleware('auth')->name('verification.notice');

// Route gửi lại email xác minh
// Route gửi lại email xác minh (dùng custom verification)
Route::post('/email/verification-notification', function () {
    $user = Auth::user();

    if ($user->email_verified_at) {
        return back()->with('status', 'already-verified');
    }

    // Cách 1: dùng container
    app(RegisterController::class)->sendCustomVerification($user);

    // Cách 2 (tương đương): (new RegisterController)->sendCustomVerification($user);

    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');


Route::get('/email/verify-token/{token}', function ($token) {
    $account = Account::where('email_verify_token', $token)->first();

    if (!$account) {
        return redirect('/')->with('status', 'Token không hợp lệ hoặc đã dùng rồi.');
    }

    $account->email_verified_at = now();
    $account->email_verify_token = null; // xoá token để không dùng lại
    $account->save();

    return redirect('/')->with('status', 'Xác minh email thành công!');
})->name('verification.token');

Route::middleware(['auth', 'role:CLIENT'])->prefix('client')->name('client.')->group(function () {
    Route::get('/jobs/create', [JobPostController::class, 'create'])->name('jobs.create');
    Route::get('/jobs/ai-form', [JobAIFormController::class, 'page'])->name('jobs.ai_form');
    Route::post('/jobs/ai-form/build', [JobAIFormController::class, 'build'])->name('jobs.ai_build');
    Route::post('/jobs', [JobPostController::class, 'store'])->name('jobs.store');
    Route::get('/jobs/new', [JobPostController::class, 'choose'])->name('jobs.choose'); // trang chọn
    Route::get('/jobs/wizard/step/{n}', [JobWizardController::class, 'show'])->name('jobs.wizard.step');
    Route::post('/jobs/wizard/step/{n}', [JobWizardController::class, 'store'])->name('jobs.wizard.store');
    Route::post('/jobs/wizard/submit', [JobWizardController::class, 'submit'])->name('jobs.wizard.submit');
    Route::get('/jobs/mine', [MyJobsController::class, 'index'])->name('jobs.mine');
});
// routes/web.php (hoặc routes/api.php)
Route::get('/checkout', function () {
    return view('checkout');
});

Route::get('/success.html', function () {
    return view('success');
});

Route::get('/cancel.html', function () {
    return view('cancel');
});

Route::post('/create-payment-link', [CheckoutController::class, 'createPaymentLink'])->name('create.payment.link');

Route::prefix('/order')->group(function () {
    Route::post('/create', [OrderController::class, 'createOrder']);
    Route::get('/{id}', [OrderController::class, 'getPaymentLinkInfoOfOrder']);
    Route::put('/{id}', [OrderController::class, 'cancelPaymentLinkOfOrder']);
});

Route::prefix('/payment')->group(function () {
    Route::post('/payos', [PaymentController::class, 'handlePayOSWebhook']);
});

Route::get('/settings/upgrade', [UpgradeController::class, 'show'])->name('settings.upgrade');
Route::post('/settings/upgrade', [UpgradeController::class, 'upgrade'])->name('settings.upgrade.post');

Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', fn() => 'Đây là trang Admin Dashboard')->name('dashboard');
        // ... các route khác
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/create', [AccountController::class, 'create'])->name('accounts.create');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::put('/accounts/{id}', [AccountController::class, 'update'])->name('accounts.update');
    });

Route::get('/payment/success', [CheckoutController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/cancel', [CheckoutController::class, 'paymentCancel'])->name('payment.cancel');

Route::get('/settings/company',  [CompanyController::class, 'index'])->name('settings.company');
Route::post('/settings/company', [CompanyController::class, 'store'])->name('settings.company.store');
Route::post('/settings/company/members/add', [CompanyController::class, 'addMemberByUsername'])
        ->name('company.members.add');
Route::post('/settings/company/members/invite', [CompanyController::class, 'inviteByUsername'])
        ->name('company.members.invite');
Route::get('/invite/{token}', [CompanyController::class, 'acceptInvite'])
    ->name('company.invite.accept');

Route::delete('/settings/company/{org}/member/{account}', [CompanyController::class, 'removeMember'])
    ->name('company.members.remove');

Route::post('/settings/company/{org}/verify-request',
        [CompanyController::class, 'requestVerification']
    )->name('company.verify.request');

Route::post('/settings/company/verification', [CompanyController::class, 'submitVerification'])
    ->name('company.verification.submit');
