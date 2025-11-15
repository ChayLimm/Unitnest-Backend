<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\BakongAccountController;
use App\Http\Controllers\bot\BotController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\ConsumptionController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentItemController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Models\PaymentItem;
use App\Models\Service;
use App\Models\Telegrambot;
use App\Services\OllamaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\BakongController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\TelegramBotController;

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

Route::get('images/{id}',[StorageController::class, 'imageUrl'] );
Route::post('images/upload',[StorageController::class, 'upload'] );

Route::middleware('auth:sanctum')->group(function () {
    // Route::get('images',[StorageController::class, 'imageUrl'] );
    Route::get('/v1/bakong', [BakongController::class, 'index']);
    Route::post('/v1/bakong/generate-khqr', [BakongController::class, 'generateKHQR']);
    Route::post('/v1/bakong/check-transaction-status', [BakongController::class, 'checkTransactionStatus']);
});

Route::post('/bot', [BotController::class, 'handleAccess']);

// Route::post('/agent', [AgentController::class, 'handleWebhook']);
Route::post('/agent/setup', [AgentController::class, 'setUpWebhook']);  // setting bot (flexible)
Route::post('/agent/webhook/{token}', [AgentController::class, 'handleWebhook']);   // incoming webhook update 



Route::get('/test', function () {
    return response()->json(['message' => 'Test endpoint is working!']);
});
Route::post('/ollama', [AgentController::class, 'generateResponse']);

// Bakong routes
Route::get('/v1/bakong', [BakongController::class, 'index']);
Route::post('/v1/bakong/generate-khqr', [BakongController::class, 'generateKHQR']);
Route::post('/v1/bakong/check-transaction-status', [BakongController::class, 'checkTransactionStatus']);

///ai
Route::apiResource('users', UserController::class);
Route::apiResource('bakong-accounts', BakongAccountController::class);
Route::apiResource('buildings',  BuildingController::class);
Route::apiResource('settings', SettingController::class);
Route::apiResource('roles', RoleController::class);
Route::apiResource('room-types', RoomTypeController::class);
Route::apiResource('rooms', RoomController::class);
Route::apiResource('contracts', ContractController::class);
Route::apiResource('services', ServiceController::class);
Route::apiResource('consumptions', ConsumptionController::class);
Route::apiResource('transactions', TransactionController::class);
Route::apiResource('payments', PaymentController::class);
Route::apiResource('payment-items', PaymentController::class);
Route::apiResource('notifications', NotificationController::class);
Route::apiResource('telegrambots', TelegramBotController::class);
// Custom route
Route::get('buildings/landlord/{landlordId}', [BuildingController::class, 'getByLandlord']);
Route::get('settings/user/{userId}', [SettingController::class, 'getUserSettings']);
Route::get('rooms/building/{buildingId}', [RoomController::class, 'getByBuilding']);
Route::patch('rooms/{room}/status', [RoomController::class, 'updateStatus']);
Route::get('contracts/active', [ContractController::class, 'getActiveContracts']);
Route::get('contracts/tenant/{tenantId}', [ContractController::class, 'getTenantContracts']);
Route::get('consumptions/room/{roomId}', [ConsumptionController::class, 'getRoomConsumptions']);
Route::get('payments/tenant/{tenantId}', [PaymentController::class, 'getTenantPayments']);
Route::patch('payments/{payment}/status', [PaymentController::class, 'updateStatus']);
Route::get('payment-items/payment/{paymentId}', [PaymentItemController::class, 'getPaymentItems']);
Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
Route::get('notifications/unread', [NotificationController::class, 'getUnreadNotifications']);
Route::post('proccess-Payment',[PaymentController::class,'processPayment']);

// Receipt PDF
Route::get('receipts/', [ReceiptController::class, 'generateRentalReceipt']);
Route::get('receipts/test-receipt', [ReceiptController::class, 'testReceipt']);