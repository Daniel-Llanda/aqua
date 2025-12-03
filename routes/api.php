<?php

use App\Http\Controllers\Api\PayloadController;
use Illuminate\Support\Facades\Route;

Route::post('/payload', [PayloadController::class, 'store']);
