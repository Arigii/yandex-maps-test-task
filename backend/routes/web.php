<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['message' => 'API is running']));

Route::get('/login', function () {
    return redirect(rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/').'/login');
})->name('login');
