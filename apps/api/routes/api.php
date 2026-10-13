<?php

use App\Http\Controllers\Api\V1\AssessmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/assessments', [AssessmentController::class, 'store'])
        ->middleware('throttle:assessment-create');

    Route::get('/assessments/{assessment}', [AssessmentController::class, 'show'])
        ->whereUuid('assessment')
        ->middleware('throttle:assessment-status');
});
