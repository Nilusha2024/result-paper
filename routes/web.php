<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResultPaperController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\XMLController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\NewFormeController;
use App\Http\Controllers\BarController;
use App\Services\EngEventService;
use Illuminate\Support\Facades\Redirect;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// web.php
Route::get('/result-paper/public', function () {
    return Redirect::to('/login');
});


// Authenticated routes
Route::middleware('auth')->group(function () {

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Everything else 

    // Result paper routes
    Route::get('/result_paper', [ResultPaperController::class, 'index'])->name('result_paper');
    Route::get('/result_paper/test_feed', [ResultPaperController::class, 'fetchAllResultData'])->name('result_paper_test_feed');

    //events
    Route::get('/events_result', [EventController::class, 'index'])->name('events.index');

    Route::get('/result_date_selection', [EventController::class, 'result_date_selection'])->name('events.result_date_selection');
    Route::post('/events_result_check', [EventController::class, 'eventCheck'])->name('events.result_check');
    Route::post('/download-events', [EventController::class, 'downloadEvents'])->name('download-events');
    // Route::get('/events_result/{event}', [EventController::class, 'show'])->name('events.show');

    //download
    Route::match(['get', 'post'], '/events/download-all', [EventController::class, 'download'])->name('events.downloadAll');
    // Route::get('/events/download', [EventController::class, 'download'])->name('events.download');

    // API test routes
    // ---------------

    // download all aussie meetings and events
    // Feed routes
    // -----------

    Route::get('/result_paper_feeds', [ResultPaperController::class, 'index'])->name('result_paper.feeds');

    Route::post('/store_aussie_feed', [ResultPaperController::class, 'storeAllAussieFeedData'])->name('store_aussie_feed');
    Route::post('/store_english_feed', [ResultPaperController::class, 'storeAllEnglishFeedDataV2'])->name('store_english_feed');

    Route::get('/kil_form', [XMLController::class, 'index']);
    Route::get('/xml/download/{filename}', [XMLController::class, 'download']);

    Route::get('/new_form', [NewFormeController::class, 'show']);
    Route::get('/xml/downloads/{filename}', [NewFormeController::class, 'new_form']);

    Route::get('/bar_form', [BarController::class, 'show']);
    Route::get('/xml/bardownload/{filename}', [BarController::class, 'bar_form']);

    // Text downloads
    // --------------

    Route::get('/english_text_download', [EventController::class, 'englishTextDownload'])->name('english_text_download');
    Route::get('/english_result_listing', [EventController::class, 'loadEngResult'])->name('english_result_listing');

    Route::post('/download_eng', [EventController::class, 'downloadEng'])->name('download_eng');

    Route::get('/kil_form', [XMLController::class, 'index']);
    Route::get('/xml/download/{filename}', [XMLController::class, 'download']);

    Route::get('/new_form', [NewFormeController::class, 'show']);
    Route::get('/xml/downloads/{filename}', [NewFormeController::class, 'new_form']);

    Route::get('/bar_form', [BarController::class, 'show'])->name('BarForm');
    Route::get('/xml/bardownload/{filename}', [BarController::class, 'bar_form']);

    //form uplode
    Route::get('/upload', [FileUploadController::class, 'index'])->name('BarFormUploadView');
    Route::post('/NewFormUpload', [App\Http\Controllers\FileUploadController::class, 'NewFormUpload'])->name('NewFormUpload');
    Route::post('/KillFormUpload', [App\Http\Controllers\FileUploadController::class, 'KillFormUpload'])->name('KillFormUpload');
    Route::post('/BarFormUpload', [App\Http\Controllers\FileUploadController::class, 'BarFormUpload'])->name('BarFormUpload');

    //form delete 
    Route::post('/deleteBarForm', [FileUploadController::class, 'deleteBarForm'])->name('deleteBarForm');
});

require __DIR__ . '/auth.php';
