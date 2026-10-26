<?php

use App\Http\Controllers\Front\PublicWebsiteController;
use App\Http\Controllers\Front\WebsiteEnquiryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicWebsiteController::class, 'index'])->name('home');
Route::get('/privacy-policy', [PublicWebsiteController::class, 'privacyPolicy'])->name('privacy-policy');
Route::post('/website/enquiries', [WebsiteEnquiryController::class, 'store'])
    ->middleware('throttle:website-enquiries')->name('website.enquiries.store');
