<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\bot\BotController;
use App\Services\OllamaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\BakongController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::post('logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('images',[StorageController::class, 'imageUrl'] );
    Route::get('/v1/bakong', [BakongController::class, 'index']);
    Route::post('/v1/bakong/generate-khqr', [BakongController::class, 'generateKHQR']);
    Route::post('/v1/bakong/check-transaction-status', [BakongController::class, 'checkTransactionStatus']);
});

Route::post('/bot', [BotController::class, 'handleAccess']);


Route::get('/test', function () {
    return response()->json(['message' => 'Test endpoint is working!']);
});
Route::post('/ollama', [AgentController::class, 'generateResponse']);
