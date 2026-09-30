<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/docs');
});

Route::get('/docs', function () {
    return file_get_contents(public_path('docs/index.html'));
});

Route::get('/api/docs', function () {
    return redirect('/docs');
});
