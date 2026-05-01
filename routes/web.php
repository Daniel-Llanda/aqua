<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [UserController::class, 'dashboard'])
        ->name('dashboard');

    Route::get('/pond-info', [UserController::class, 'pondInfo'])
        ->name('pond-info');

    Route::get('/pond-info/create', [UserController::class, 'createPond'])
        ->name('pond.create');

    Route::post('/pond-info', [UserController::class, 'storePond'])
        ->name('pond.store');

    Route::get('/pond-info/{pond}/cycles/new', [UserController::class, 'startNewCycleForm'])
        ->name('pond.cycles.new');

    Route::post('/pond-info/{pond}/cycles', [UserController::class, 'startNewCycle'])
        ->name('pond.cycles.store');

    Route::get('/pond-info/{pond}/cycles/history', [UserController::class, 'cycleHistory'])
        ->name('pond.cycles.history');

    Route::get('/pond-info/{pond}/cycles/{cycle}/species-data', [UserController::class, 'speciesDataForm'])
        ->name('pond.cycle.species-data.form');

    Route::post('/pond-info/{pond}/cycles/{cycle}/species-data', [UserController::class, 'storeSpeciesData'])
        ->name('pond.cycle.species-data.store');

    Route::get('/pond-info/{pond}/cycles/{cycle}/harvest-data', [UserController::class, 'harvestDataForm'])
        ->name('pond.cycle.harvest-data.form');

    Route::post('/pond-info/{pond}/cycles/{cycle}/harvest-data', [UserController::class, 'storeHarvestData'])
        ->name('pond.cycle.harvest-data.store');

    Route::get('/telemetrylog', [UserController::class, 'telemetrylog'])
        ->name('telemetrylog');

    Route::get('/telemetrylog/report', [UserController::class, 'telemetryReport'])
        ->name('telemetrylog.report');

    Route::get('/payload', [UserController::class, 'getPayload'])
        ->name('get.payload');

    Route::post('/dashboard/alerts/sms', [UserController::class, 'sendDashboardAlert'])
        ->middleware('throttle:20,1')
        ->name('dashboard.alerts.sms');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/phone/send-otp', [ProfileController::class, 'sendPhoneOtp'])
        ->middleware('throttle:6,1')
        ->name('profile.phone.send-otp');
    Route::post('/profile/phone/verify-otp', [ProfileController::class, 'verifyPhoneOtp'])
        ->middleware('throttle:6,1')
        ->name('profile.phone.verify-otp');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// useless routes
// Just to demo sidebar dropdown links active states.
Route::get('/buttons/text', function () {
    return view('buttons-showcase.text');
})->middleware(['auth'])->name('buttons.text');

Route::get('/buttons/icon', function () {
    return view('buttons-showcase.icon');
})->middleware(['auth'])->name('buttons.icon');

Route::get('/buttons/text-icon', function () {
    return view('buttons-showcase.text-icon');
})->middleware(['auth'])->name('buttons.text-icon');

require __DIR__.'/auth.php';

Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::middleware('auth:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/users', [AdminController::class, 'user'])->name('admin.users');
        Route::get('/users/{user}/ponds/{pond}/telemetry', [AdminController::class, 'userPondTelemetry'])
            ->name('admin.users.ponds.telemetry');
        Route::get('/telemetry', [AdminController::class, 'telemetry'])->name('admin.telemetry');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.create');

    });
});
