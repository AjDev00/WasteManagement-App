<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Catch-all for React routes
Route::get('{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '^(?!api).*$');

