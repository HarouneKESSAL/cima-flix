<?php

use App\Helpers\ApiResponse;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\MoviesController;
use App\Http\Controllers\TvController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::middleware('auth:sanctum')->get('user', [AuthController::class, 'user']);
    });
    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {

        // Movies routes
        Route::get('/', [MoviesController::class, 'index']);
        Route::get('/movies/{id}', [MoviesController::class, 'show']);

        // TV shows routes
        Route::get('/tv', [TvController::class, 'index']);
        Route::get('/tv/{id}', [TvController::class, 'show']);

        // Favorites routes
        Route::get('/content/top5', [FavoriteController::class, 'getTop5InGenre']);
        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites', [FavoriteController::class, 'store']);
        Route::delete('/favorites', [FavoriteController::class, 'destroy']);
        Route::get('/search', [FavoriteController::class, 'search']);
        Route::get('/{type}/{id}/trailer', [FavoriteController::class, 'getAllTrailerLinks'])
            ->where('type', 'movie|tv');

    });
});


// Not found route
Route::fallback(function () {
    return ApiResponse::error('Not found', 'general:not-found', 404);
});
