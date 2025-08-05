<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BadgeController;

/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
*/

Route::group(['prefix' => 'v1'], function () {
    Route::get('/status', function () {
        return response()->json(['status' => 'API is running']);
    });

    Route::get('/badges', [BadgeController::class, 'index']);

    // Add your API routes here
    // Example: Route::get('/example', [ExampleController::class, 'index']);
});
