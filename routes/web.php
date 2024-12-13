<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;

Route::get('/', function () {
    return view('welcome');
});


Route::get('test-redis', function () {
    try {
            Redis::set('key', 'value');
            return Redis::get('key');

    } catch (\Exception $e) {
        return 'Redis connection error: ' . $e->getMessage();
    }
});
