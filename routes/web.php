<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', 'login|settings|reviews');

Route::get('/', function () {
    return view('welcome');
});
