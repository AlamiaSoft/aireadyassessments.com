<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'service' => 'AIReadyAssessment API',
    'status' => 'ok',
    'docs' => 'https://github.com/AlamiaSoft/aireadyassessments.com',
]));
