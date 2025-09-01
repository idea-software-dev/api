<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\BadgeController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
*/

Route::group(['prefix' => 'v1'], function () {
    Route::get('/status', function () {
        return response()->json(['status' => 'API is running']);
    });

    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', function (Request $request) {
            $user = $request->user();

            return response()->json(
                [
                    'id' => $user->id,
                    'nickname' => $user->nickname,
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'country_id' => $user->country_id,
                    'dob_day' => Carbon::parse($user->dob)->day ?? null,
                    'dob_month' => Carbon::parse($user->dob)->month ?? null,
                    'dob_year' => Carbon::parse($user->dob)->year ?? null,
                ],
                Response::HTTP_OK
            );
        });
    });

    Route::get('/badges', [BadgeController::class, 'index']);
});
