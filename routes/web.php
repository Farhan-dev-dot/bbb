<?php

use App\Http\Controllers\api\MbarangController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
