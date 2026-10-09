<?php

use App\Http\Controllers\PreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PreviewController::class, 'login'])->name('login');
Route::get('/login', fn () => redirect()->route('login'));
Route::post('/preview/reset', [PreviewController::class, 'reset'])->name('preview.reset');
Route::get('/preview/{role}/{page?}', [PreviewController::class, 'show'])->name('preview.page');
Route::post('/preview/{role}/{page}', [PreviewController::class, 'action'])->name('preview.action');
