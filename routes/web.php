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
use App\Http\Controllers\PortfolioController;
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
use App\Http\Controllers\OrgsController;
use App\Models\Account;
use App\Http\Controllers\UpgradeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\JobPaymentController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
// Thêm controller mới của Admin
use App\Http\Controllers\Admin\JobController as AdminJobController;
// Thêm controller quản lý xét duyệt
use App\Http\Controllers\Admin\AdminVerificationController;
use App\Http\Controllers\PaymentController as PublicPaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;

use App\Http\Controllers\Auth\AccountPasswordResetController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\ConnectedServicesController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ProfileAiController;
use App\Http\Controllers\JobCompletionController;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\JobReportController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\JobPaymentController as AdminJobPaymentController;




Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/profile/about/ai-build', [ProfileAiController::class, 'buildAbout'])
        ->name('profile.about.ai');
});

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

    // Tất cả đoạn chat
    Route::get('/chat', [MessageController::class, 'chatAll'])->name('chat.all');

    Route::get('/chat/messages/{partnerId}/{jobId}', [MessageController::class, 'getMessages']);

    Route::get('/jobs/{job}/chat', [MessageController::class, 'chat'])->name('chat.job');
    Route::get('/portfolios/{username}/chat', [MessageController::class, 'chatWithUser'])->name('chat.username');

    // Chủ job vào chat với freelancer cụ thể
    Route::get('/jobs/{job}/chat/{freelancer}', [MessageController::class, 'chatWithFreelancer'])->name('chat.with');
    Route::get('/chat/box/{boxId}/messages', [MessageController::class, 'getBoxMessages']);

    // Gửi tin nhắn
    Route::post('/messages/send', [MessageController::class, 'send'])->name('messages.send');

    Route::get('/chat/box/{boxId}/messages', [MessageController::class, 'getBoxMessages']);
    Route::get('/chat/list', [MessageController::class, 'getChatList'])->name('messages.chat_list');

    Route::get('/header/summary', [NotificationController::class, 'headerSummary'])
        ->name('header.summary');
    Route::get('/notifications/header-data', [NotificationController::class, 'headerData'])
        ->name('notifications.headerData');
    Route::get('/chat/header', [NotificationController::class, 'headerList'])
        ->name('chat.header');

    Route::post('/notifications/mark-read', [NotificationController::class, 'markNotificationsRead'])
        ->name('notifications.markRead');

    Route::post('/chat/mark-box-read', [NotificationController::class, 'markBoxMessagesRead'])
        ->name('chat.markBoxRead');



    Route::get('/jobs/apply/{job}', [JobController::class, 'apply'])->name('jobs.apply');
    Route::post('/jobs/{job}/comments', [JobController::class, 'store'])->name('comments.store');
    Route::post('/jobs/report/{job}', [JobController::class, 'report'])->name('jobs.report');



    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::get('/settings', [SettingsController::class, 'index'])
        ->name('settings.index');
    Route::patch('/profile/about', [ProfileController::class, 'updateAbout'])
        ->name('profile.about.update');
    Route::put('/settings/my-info', [SettingsController::class, 'updateMyInfo'])
        ->name('settings.myinfo.update');

    Route::put('/settings/security', [SettingsController::class, 'updateSecurity'])
        ->name('settings.security.update');

    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])
        ->name('settings.notifications.update');

    // tuỳ chọn: đổi gói
    Route::post('/settings/membership/change', [SettingsController::class, 'changeMembership'])
        ->name('settings.membership.change');

    Route::post('/jobs/{job_id}/payment/create', [JobPaymentController::class, 'createPaymentLink'])
        ->name('job-payments.create');

    // PayOS redirect
    Route::get('/payments/job/success', [JobPaymentController::class, 'paymentSuccess'])
        ->name('job-payments.success');
    Route::get('/payments/job/cancel', [JobPaymentController::class, 'paymentCancel'])
        ->name('job-payments.cancel');

    Route::delete('/client/jobs/{job}', [JobController::class, 'destroy'])
        ->name('client.jobs.destroy');

    Route::get('submitted_jobs', [JobController::class, 'submitted_jobs'])->name('submitted_jobs')->middleware('role:F_BASIC|F_PLUS');
    Route::get('/jobs/{job}/user-tasks', [JobController::class, 'userTasks'])
        ->name('jobs.user_tasks');

    Route::post('/tasks/{task}/submit', [TaskController::class, 'submit'])->name('tasks.submit');
    Route::get('/jobs/{jobId}/drive/{taskId?}', [TaskController::class, 'getVirtualDrive'])->name('jobs.drive.data');
    Route::delete('/tasks/{task}/files/delete', [TaskController::class, 'deleteFile'])->name('tasks.files.delete');
    Route::get('/tasks/files/download/{filename}', [TaskController::class, 'downloadFile'])->name('tasks.files.download');

});
// Hiển thị danh sách công việc
Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');

Route::get('/jobs/{job}', [JobController::class, 'show'])->name('jobs.show');



// Hiển thị danh sách freelancer
Route::get('/freelancers', [FreelancerController::class, 'index'])->name('freelancers.index');
Route::get('/orgs', [OrgsController::class, 'index'])->name('orgs.index');

// Hiển thị danh sách portfolio
Route::get('/portfolios', [PortfolioController::class, 'index'])->name('portfolios.index');

Route::get('/portfolios/{user}', [PortfolioController::class, 'show'])
    ->name('portfolios.show');


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

    Route::get('/submitted_jobs', [SettingsController::class, 'submitted_jobs'])->name('submitted_jobs')->middleware('role:F_BASIC|F_PLUS');

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
Route::middleware(['auth', 'role:CLIENT|BUSS'])   // cả CLIENT và BUSS
    ->prefix('client')
    ->name('client.')
    ->group(function () {

        Route::get('/jobs/create', [JobPostController::class, 'create'])->name('jobs.create');
        Route::get('/jobs/ai-form', [JobAIFormController::class, 'page'])->name('jobs.ai_form');
        Route::post('/jobs/ai-form/build', [JobAIFormController::class, 'build'])->name('jobs.ai_build');
        Route::post('/jobs', [JobPostController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/new', [JobPostController::class, 'choose'])->name('jobs.choose');
        Route::get('/jobs/wizard/step/{n}', [JobWizardController::class, 'show'])->name('jobs.wizard.step');
        Route::post('/jobs/wizard/step/{n}', [JobWizardController::class, 'store'])->name('jobs.wizard.store');
        Route::post('/jobs/wizard/submit', [JobWizardController::class, 'submit'])->name('jobs.wizard.submit');
        Route::post('/jobs/ai/submit', [JobAIFormController::class, 'submit'])->name('jobs.ai.submit');
        Route::get('/jobs/mine', [MyJobsController::class, 'index'])->name('jobs.mine');

        Route::patch('/jobs/{job_id}/applications/{user_id}', [MyJobsController::class, 'update'])
            ->name('jobs.applications.update');

        // Rời doanh nghiệp
        Route::delete('/settings/company/{org}/leave', [CompanyController::class, 'leaveOrg'])
            ->name('company.members.leave');
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
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');

    // Routes quản lý tài khoản
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::put('/accounts/{id}', [AccountController::class, 'update'])->name('accounts.update');
    Route::delete('/accounts/{id}', [AccountController::class, 'destroy'])->name('accounts.destroy');
    Route::delete('/accounts', [AccountController::class, 'destroyMultiple'])->name('accounts.destroy-multiple');
    Route::post('/accounts/update-status', [AccountController::class, 'updateStatusMultiple'])->name('accounts.update-status-multiple');
    Route::post('/accounts/update-status-all', [AccountController::class, 'updateStatusAll'])->name('accounts.update-status-all');

    // Routes quản lý thanh toán
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/export', [AdminPaymentController::class, 'export'])->name('payments.export');
    Route::get('/payments/{id}', [AdminPaymentController::class, 'show'])->name('payments.show');
    Route::delete('/payments/{id}', [AdminPaymentController::class, 'destroy'])->name('payments.destroy');

    // Routes quản lý thanh toán job
    Route::get('/job-payments', [AdminJobPaymentController::class, 'index'])->name('job-payments.index');
    Route::get('/job-payments/{id}', [AdminJobPaymentController::class, 'show'])->name('job-payments.show');
    Route::patch('/job-payments/{id}', [AdminJobPaymentController::class, 'updateStatus'])->name('job-payments.update');
    Route::delete('/job-payments/{id}', [AdminJobPaymentController::class, 'destroy'])->name('job-payments.destroy');

    // Routes quản lý lịch sử hoạt động admin
    Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');
    Route::get('/logs/{id}', [AdminLogController::class, 'show'])->name('logs.show');

    // Routes cài đặt hệ thống
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/clear-cache', [AdminSettingsController::class, 'clearCache'])->name('settings.clear-cache');
    Route::post('/settings/optimize', [AdminSettingsController::class, 'optimizeApp'])->name('settings.optimize');
    Route::post('/settings/clear-logs', [AdminSettingsController::class, 'clearLogs'])->name('settings.clear-logs');

    // ==========================================================
    Route::get('/jobs/pending', [AdminJobController::class, 'pendingJobs'])->name('jobs.pending');
    Route::get('/jobs/history', [AdminJobController::class, 'history'])->name('jobs.history');
    Route::get('/jobs/{job}/details', [AdminJobController::class, 'getJobDetails'])->name('jobs.details');
    Route::post('/jobs/{job}/approve', [AdminJobController::class, 'approve'])->name('jobs.approve');
    Route::post('/jobs/{job}/reject', [AdminJobController::class, 'reject'])->name('jobs.reject');
    // THÊM ROUTE DUYỆT/TỪ CHỐI HÀNG LOẠT
    Route::post('/jobs/batch-approve', [AdminJobController::class, 'batchApprove'])->name('jobs.batch-approve');
    Route::post('/jobs/batch-reject', [AdminJobController::class, 'batchReject'])->name('jobs.batch-reject');
    // THÊM ROUTE RESET JOBS
    Route::post('/jobs/reset-all', [AdminJobController::class, 'resetAllJobs'])->name('jobs.reset-all');
    // ==========================================================

    // THÊM ROUTES QUẢN LÝ LOẠI TÀI KHOẢN
    Route::post('/account-types', [AccountController::class, 'storeAccountType'])->name('account-types.store');
    Route::get('/account-types', [AccountController::class, 'getAccountTypes'])->name('account-types.index');
    Route::put('/account-types/{id}', [AccountController::class, 'updateAccountType'])->name('account-types.update');
    Route::delete('/account-types/{id}', [AccountController::class, 'destroyAccountType'])->name('account-types.destroy');

    // THÊM ROUTE EXPORT PAYMENTS
    Route::get('/payments/export', [AdminPaymentController::class, 'export'])->name('payments.export');

    // TEST ROUTE
    Route::get('/test-simple', function () {
        return 'Server hoạt động bình thường!';
    });


    Route::get('/test-accounts', function () {
        $start = microtime(true);
        $accounts = App\Models\Account::limit(5)->get();
        $end = microtime(true);
        return response()->json([
            'count' => $accounts->count(),
            'time' => ($end - $start) * 1000 . 'ms',
            'accounts' => $accounts
        ]);
    });

    // THÊM ROUTES QUẢN LÝ MEMBERSHIP PLANS
    Route::get('/membership-plans', [AdminPaymentController::class, 'getMembershipPlans'])->name('membership-plans.index');
    Route::post('/membership-plans', [AdminPaymentController::class, 'storeMembershipPlan'])->name('membership-plans.store');
    Route::delete('/membership-plans/{id}', [AdminPaymentController::class, 'deleteMembershipPlan'])->name('membership-plans.destroy');
    Route::put('/membership-plans/{id}', [AdminPaymentController::class, 'updateMembershipPlan'])->name('membership-plans.update');
    Route::get('/membership-plans/{id}', [AdminPaymentController::class, 'getMembershipPlan'])->name('membership-plans.show');

    // ==========================================================
    // THÊM ROUTES QUẢN LÝ XÁC MINH DOANH NGHIỆP
    // ==========================================================
    Route::get('/verifications', [AdminVerificationController::class, 'index'])->name('verifications.index');
    Route::get('/verifications/{verification}', [AdminVerificationController::class, 'show'])->name('verifications.show');
    Route::get('/verifications/{verification}/details', [AdminVerificationController::class, 'getDetails'])->name('verifications.details');
    Route::post('/verifications/{verification}/approve', [AdminVerificationController::class, 'approve'])->name('verifications.approve');
    Route::post('/verifications/{verification}/reject', [AdminVerificationController::class, 'reject'])->name('verifications.reject');
    // Bulk operations
    Route::post('/verifications/bulk-approve', [AdminVerificationController::class, 'bulkApprove'])->name('verifications.bulk-approve');
    Route::post('/verifications/bulk-reject', [AdminVerificationController::class, 'bulkReject'])->name('verifications.bulk-reject');
    // ==========================================================

    // ==========================================================
    // ROUTES QUẢN LÝ DANH MỤC
    // ==========================================================
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{id}', [CategoryController::class, 'show'])->name('categories.show');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    // ==========================================================

    // ==========================================================
    // ROUTES QUẢN LÝ ĐÁNH GIÁ
    // ==========================================================
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/reviews/{id}', [ReviewController::class, 'show'])->name('reviews.show');
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/destroy-multiple', [ReviewController::class, 'destroyMultiple'])->name('reviews.destroy-multiple');
    // ==========================================================

    // ==========================================================
    // ROUTES QUẢN LÝ BÁO CÁO JOB
    // ==========================================================
    Route::get('/job-reports', [JobReportController::class, 'index'])->name('job-reports.index');
    Route::get('/job-reports/{jobId}/details', [JobReportController::class, 'getDetails'])->name('job-reports.details');
    Route::delete('/job-reports/{id}', [JobReportController::class, 'destroy'])->name('job-reports.destroy');
    Route::delete('/job-reports/job/{jobId}', [JobReportController::class, 'destroyByJob'])->name('job-reports.destroy-by-job');
    Route::post('/job-reports/job/{jobId}/toggle-lock', [JobReportController::class, 'toggleLockByJob'])->name('job-reports.toggle-lock');
    Route::post('/job-reports/bulk-lock', [JobReportController::class, 'bulkLock'])->name('job-reports.bulk-lock');
    Route::post('/job-reports/bulk-unlock', [JobReportController::class, 'bulkUnlock'])->name('job-reports.bulk-unlock');
    // ==========================================================

    // ==========================================================
    // ROUTES QUẢN LÝ KỸ NĂNG
    // ==========================================================
    Route::get('/skills', [SkillController::class, 'index'])->name('skills.index');
    Route::get('/skills/{id}', [SkillController::class, 'show'])->name('skills.show');
    Route::post('/skills', [SkillController::class, 'store'])->name('skills.store');
    Route::put('/skills/{id}', [SkillController::class, 'update'])->name('skills.update');
    Route::delete('/skills/{id}', [SkillController::class, 'destroy'])->name('skills.destroy');
    // ==========================================================
});


Route::get('/payment/success', [CheckoutController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/cancel', [CheckoutController::class, 'paymentCancel'])->name('payment.cancel');

Route::get('/settings/company', [CompanyController::class, 'index'])->name('settings.company');
Route::post('/settings/company', [CompanyController::class, 'store'])->name('settings.company.store');
Route::post('/settings/company/members/add', [CompanyController::class, 'addMemberByUsername'])
    ->name('company.members.add');
Route::post('/settings/company/members/invite', [CompanyController::class, 'inviteByUsername'])
    ->name('company.members.invite');
Route::get('/invite/{token}', [CompanyController::class, 'acceptInvite'])
    ->name('company.invite.accept');

Route::delete('/settings/company/{org}/member/{account}', [CompanyController::class, 'removeMember'])
    ->name('company.members.remove');

Route::post(
    '/settings/company/{org}/verify-request',
    [CompanyController::class, 'requestVerification']
)->name('company.verify.request');

Route::post('/settings/company/verification', [CompanyController::class, 'submitVerification'])->name('company.verification.submit');
Route::delete('/settings/company/{org}/leave', [CompanyController::class, 'leaveOrg'])->name('company.members.leave');

Route::get('/search', [SearchController::class, 'search']);


use App\Http\Controllers\CloudinaryTestController;

Route::get('/upload', [CloudinaryTestController::class, 'index']);
Route::post('/upload', [CloudinaryTestController::class, 'upload'])->name('cloudinary.upload');

Route::get('/forgot-password', [AccountPasswordResetController::class, 'showRequestForm'])
    ->name('password.request');

Route::post('/forgot-password', [AccountPasswordResetController::class, 'sendLink'])
    ->middleware('throttle:6,1')
    ->name('password.email');

Route::get('/reset-password/{token}', [AccountPasswordResetController::class, 'showResetForm'])
    ->name('password.reset');

Route::post('/reset-password', [AccountPasswordResetController::class, 'reset'])
    ->name('password.update');


Route::get('/settings/connected', [ConnectedServicesController::class, 'index'])
    ->name('settings.connected');

// Hủy liên kết
Route::delete('/settings/connected/unlink/{provider}', [ConnectedServicesController::class, 'unlink'])
    ->name('settings.connected.unlink');

// OAuth
// routes/web.php
use App\Http\Controllers\OAuthController;

Route::get('/oauth/{provider}/redirect', [OAuthController::class, 'redirect'])
    ->name('oauth.redirect');

Route::get('/oauth/{provider}/callback', [OAuthController::class, 'callback'])
    ->name('oauth.callback');


// LEGAL PAGES
Route::view('/terms', 'legal.terms')->name('legal.terms');
Route::view('/privacy', 'legal.privacy')->name('legal.privacy');

Route::post('/profile/avatar', [PortfolioController::class, 'upload'])
    ->name('profile.avatar.upload');

Route::patch('/portfolios/location', [PortfolioController::class, 'updateLocation'])
    ->name('portfolios.location.update');

Route::get('/settings/billing', [BillingController::class, 'index'])->name('settings.billing');
Route::post('/settings/billing/add-card', [BillingController::class, 'addCard'])->name('settings.billing.addCard');
Route::delete('/settings/billing/card', [BillingController::class, 'deleteCard'])->name('settings.billing.deleteCard');
Route::get('/api/momo/bankcodes', [BillingController::class, 'bankcodes'])->name('momo.bankcodes');

Route::middleware('auth')->post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
Route::post('/profile/about/ai-build', [ProfileAiController::class, 'buildAbout'])->name('profile.about.ai');
Route::post('/profile/about/ai-build', [ProfileAiController::class, 'buildAbout'])->name('profile.about.ai');
Route::post('/profiles/{profile:profile_id}/skills', [PortfolioController::class, 'updateSkills'])->name('profiles.skills.update');
Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

Route::post('/client/tasks', [\App\Http\Controllers\TaskController::class, 'store'])
    ->name('client.tasks.store')->middleware('auth');
Route::patch('/client/jobs/{job}/complete', [JobCompletionController::class, 'complete'])
    ->name('client.jobs.complete')
    ->middleware(['auth']);

Route::patch('/client/tasks/extend', [\App\Http\Controllers\TaskController::class, 'extendDueDate'])
    ->name('client.tasks.extend')
    ->middleware(['auth']);
Route::post('/settings/billing/withdraw', [BillingController::class, 'withdraw'])
    ->name('settings.billing.withdraw')
    ->middleware('auth');

// Bulk trước
Route::patch('/client/jobs/{job_id}/applications/bulk', [MyJobsController::class, 'bulkUpdate'])
    ->whereNumber('job_id')
    ->name('client.jobs.applications.bulk');

// Update 1 người sau, ràng buộc user_id là số
Route::patch('/client/jobs/{job_id}/applications/{user_id}', [MyJobsController::class, 'update'])
    ->whereNumber('job_id')
    ->whereNumber('user_id')
    ->name('client.jobs.applications.update');


use App\Http\Controllers\StripeCheckoutController;

Route::post('/checkout/stripe', [StripeCheckoutController::class, 'createCheckout'])->name('stripe.checkout');
Route::get('/checkout/stripe/success', [StripeCheckoutController::class, 'success'])->name('stripe.success');
Route::get('/checkout/stripe/cancel', [StripeCheckoutController::class, 'cancel'])->name('stripe.cancel');

// routes/api.php (webhook)
Route::post('/stripe/webhook', [StripeCheckoutController::class, 'webhook'])->name('stripe.webhook');



// ✅ Thêm cặp route “tổng quát” (dùng cho nút Liên kết có ?mode=link)
Route::get('/oauth/{provider}/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
Route::get('/oauth/{provider}/callback', [OAuthController::class, 'callback'])->name('oauth.callback');

use App\Http\Controllers\ChatBotController;
Route::post('/chat', [ChatBotController::class, 'handle']);
Route::post('/chat/reset', [ChatBotController::class, 'reset']);