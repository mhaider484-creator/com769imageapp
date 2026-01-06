<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/version', fn () => [
    'deployed_at' => now()->toDateTimeString()
]);

