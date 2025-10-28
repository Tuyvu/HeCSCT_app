<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InferenceRuleController;


Route::get('/chat', [InferenceRuleController::class, 'index'])->name('inference.show');

// Route để xử lý logic khi form được gửi
// [cite: 19, 50, 56]
// Route::post('/run-inference', [InferenceRuleController::class, 'store'])->name('inference.store');
Route::post('/infer', [InferenceRuleController::class, 'store'])->name('infer.run');