<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');
Route::get('/projects', fn() => view('projects'))->name('projects');
Route::get('/notes', fn() => view('notes'))->name('notes');
Route::get('/hashtags', fn() => view('hashtags'))->name('hashtags');
