<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

Volt::route('/onboarding', 'onboarding-wizard');

// Auth routes
Volt::route('/login', 'auth.login')->name('login')->middleware('guest');
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->name('logout')->middleware('auth');

// Protected routes
Route::middleware(['auth'])->group(function () {
    Volt::route('/super-admin', 'super-admin-dashboard')->middleware('super_admin');
    Volt::route('/dashboard', 'agency-dashboard')->middleware('tenant');
});
