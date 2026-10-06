<?php

use App\Http\Controllers\Api\JobController;
use App\Http\Middleware\AuthenticateJobApi;
use Illuminate\Support\Facades\Route;

// Read-only company feed. Legacy branded feeds and registration are retired.
Route::get('/jobs', [JobController::class, 'index'])
    ->middleware(AuthenticateJobApi::class)
    ->name('api.jobs.index');

Route::get('/jobs/{id}', [JobController::class, 'show'])
    ->whereNumber('id')
    ->middleware(AuthenticateJobApi::class)
    ->name('api.jobs.show');
