<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::middleware('guest')->group(function () {
	Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
	Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function () {
	Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
	Route::get('/password', [AuthController::class, 'showChangePassword'])->name('password.edit');
	Route::post('/password', [AuthController::class, 'updatePassword'])->name('password.update');

	Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');
	Route::get('/ai', fn() => view('ai'))->name('ai');
	Route::get('/cashflow', fn() => view('cashflow'))->name('cashflow');
	Route::get('/projects', fn() => view('projects'))->name('projects');
	Route::get('/accounts', fn() => view('accounts'))->name('accounts');
	Route::get('/notes', fn() => view('notes'))->name('notes');
	Route::get('/hashtags', fn() => view('hashtags'))->name('hashtags');
});
