<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\LegacyController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ThanksController;
use App\Http\Controllers\WarehousingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/fleet-and-warehousing', [PageController::class, 'fleet'])->name('fleet');
Route::get('/depots', [PageController::class, 'depots'])->name('depots');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

Route::get('/quote', [QuoteController::class, 'create'])->name('quotes.create');
Route::get('/warehousing-enquiry', [WarehousingController::class, 'create'])->name('warehousing.create');
Route::get('/careers', [CareerController::class, 'index'])->name('careers.index');
Route::get('/careers/apply', [CareerController::class, 'create'])->name('careers.apply');
Route::get('/careers/{job}', [CareerController::class, 'show'])->name('careers.show');

Route::middleware('throttle:forms')->group(function () {
    Route::post('/quote', [QuoteController::class, 'store'])->name('quotes.store');
    Route::post('/warehousing-enquiry', [WarehousingController::class, 'store'])->name('warehousing.store');
    Route::post('/careers/apply', [CareerController::class, 'store'])->name('careers.store');
    Route::post('/contact', [EnquiryController::class, 'store'])->name('contact.store');
});
Route::get('/thanks', ThanksController::class)->name('thanks');

// Signed links only: a customer's quote (emailed by traffic) and an applicant's documents page (emailed by HR).
Route::middleware('signed')->group(function () {
    Route::get('/your-quote/{quote}', [QuoteController::class, 'response'])->name('quotes.response');
    Route::post('/your-quote/{quote}', [QuoteController::class, 'respond'])->middleware('throttle:forms')->name('quotes.respond');
    Route::get('/careers/documents/{application}', [CareerController::class, 'documents'])->name('careers.documents');
    Route::post('/careers/documents/{application}', [CareerController::class, 'storeDocuments'])->middleware('throttle:forms')->name('careers.documents.store');
});

Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/photo-credits', [PageController::class, 'credits'])->name('credits');
Route::get('/sitemap', [PageController::class, 'sitemapPage'])->name('sitemap.page');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

// The old site's addresses: /page.php?id=26 and the like.
Route::get('/page.php', LegacyController::class)->name('legacy.page');
Route::permanentRedirect('/index.php', '/');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::middleware('area:quotes')->group(function () {
        Route::get('/quotes', [Admin\QuoteController::class, 'index'])->name('quotes.index');
        Route::get('/quotes/export', [Admin\QuoteController::class, 'export'])->name('quotes.export');
        Route::get('/quotes/{quote}', [Admin\QuoteController::class, 'show'])->name('quotes.show');
        Route::patch('/quotes/{quote}', [Admin\QuoteController::class, 'update'])->name('quotes.update');
        Route::post('/quotes/{quote}/price', [Admin\QuoteController::class, 'price'])->name('quotes.price');
        Route::post('/quotes/{quote}/notes', [Admin\QuoteController::class, 'note'])->name('quotes.note');
        Route::get('/quotes/{quote}/files/{file}', [Admin\QuoteController::class, 'file'])->name('quotes.file');
    });

    Route::middleware('area:warehousing')->group(function () {
        Route::get('/warehousing', [Admin\WarehousingController::class, 'index'])->name('warehousing.index');
        Route::get('/warehousing/export', [Admin\WarehousingController::class, 'export'])->name('warehousing.export');
        Route::get('/warehousing/{enquiry}', [Admin\WarehousingController::class, 'show'])->name('warehousing.show');
        Route::patch('/warehousing/{enquiry}', [Admin\WarehousingController::class, 'update'])->name('warehousing.update');
        Route::post('/warehousing/{enquiry}/notes', [Admin\WarehousingController::class, 'note'])->name('warehousing.note');
    });

    Route::middleware('area:recruitment')->group(function () {
        Route::get('/jobs', [Admin\JobPostingController::class, 'index'])->name('jobs.index');
        Route::get('/jobs/create', [Admin\JobPostingController::class, 'create'])->name('jobs.create');
        Route::post('/jobs', [Admin\JobPostingController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/{job}/edit', [Admin\JobPostingController::class, 'edit'])->name('jobs.edit');
        Route::put('/jobs/{job}', [Admin\JobPostingController::class, 'update'])->name('jobs.update');
        Route::get('/applications', [Admin\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/export', [Admin\ApplicationController::class, 'export'])->name('applications.export');
        Route::get('/applications/{application}', [Admin\ApplicationController::class, 'show'])->name('applications.show');
        Route::patch('/applications/{application}', [Admin\ApplicationController::class, 'update'])->name('applications.update');
        Route::patch('/applications/{application}/checks', [Admin\ApplicationController::class, 'checks'])->name('applications.checks');
        Route::post('/applications/{application}/documents-request', [Admin\ApplicationController::class, 'requestDocuments'])->name('applications.request-documents');
        Route::post('/applications/{application}/hire', [Admin\ApplicationController::class, 'hire'])->name('applications.hire');
        Route::post('/applications/{application}/notes', [Admin\ApplicationController::class, 'note'])->name('applications.note');
        Route::get('/applications/{application}/cv', [Admin\ApplicationController::class, 'cv'])->name('applications.cv');
        Route::get('/applications/{application}/documents/{document}', [Admin\ApplicationController::class, 'document'])->name('applications.document');
    });

    Route::middleware('area:compliance')->group(function () {
        Route::get('/staff', [Admin\StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/export', [Admin\StaffController::class, 'export'])->name('staff.export');
        Route::get('/staff/create', [Admin\StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [Admin\StaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{staff}', [Admin\StaffController::class, 'show'])->name('staff.show');
        Route::get('/staff/{staff}/edit', [Admin\StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{staff}', [Admin\StaffController::class, 'update'])->name('staff.update');
        Route::post('/staff/{staff}/notes', [Admin\StaffController::class, 'note'])->name('staff.note');
    });

    Route::middleware('area:enquiries')->group(function () {
        Route::get('/enquiries', [Admin\EnquiryController::class, 'index'])->name('enquiries.index');
        Route::get('/enquiries/{enquiry}', [Admin\EnquiryController::class, 'show'])->name('enquiries.show');
        Route::patch('/enquiries/{enquiry}', [Admin\EnquiryController::class, 'update'])->name('enquiries.update');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('/users', [Admin\UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    });
});
