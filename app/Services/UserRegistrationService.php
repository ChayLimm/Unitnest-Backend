<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Setting;
use App\Models\BakongAccount;

use App\Services\TelegramBotService;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRegistrationService{
    public function register(array $data){
        try{
            DB::beginTransaction();

            $role = Role::where('role_name', 'Landlord')->firstOrFail();

            // Create User
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phonenumber' => $data['phonenumber'],
                'device_id' => $data['device_id'],
                'role_id' => $role->id,
            ]);

            // Telegram bot setup
            $telegramBotService = new TelegramBotService();
            $botResponse = $telegramBotService->setUpBot($data['token'], $user->id);
            

            $bakongAccount = BakongAccount::create([
                'landlord_id' => $user->id,
                'bakong_id' => $data['bakong_id'],
                'bakong_name' => $data['bakong_name'],
                'bakong_location' => $data['bakong_location'],
            ]);

            $setting = Setting::create([
                'user_id' => $user->id,
                'water_price' => $data['water_price'],
                'electricity_price' => $data['electricity_price'],
                'general_rules' => $data['general_rules'] ?? 'These are the general rules.',
                'contract_rules' => $data['contract_rules'] ?? 'These are the contract rules.',
                'khr_currency' => $data['khr_currency'] ?? 4000,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'User registration successful',
                'data' => [
                    'user' => $user,
                    'telegram' => $botResponse,
                    'bakongAccount' => $bakongAccount,
                    'setting' => $setting,
                ],
            ], 201);
        }
        catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                'message' => 'User registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}