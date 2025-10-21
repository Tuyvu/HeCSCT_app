<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InferenceController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/', [InferenceController::class, 'show'])->name('inference.show');

// Route để xử lý logic khi form được gửi
// [cite: 19, 50, 56]
Route::post('/run-inference', [InferenceController::class, 'run'])->name('inference.run');