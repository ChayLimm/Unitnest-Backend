<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

//Decrption
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use App\Models\Telegrambot;
class TelegramBotController extends Controller
{
    public function index()
    {
        $telegramBots = TelegramBot::with('user')->get();
        return response()->json($telegramBots);
    }
    public function show($id)
    {
        $telegramBot = TelegramBot::find($id);
        
        if (!$telegramBot) {
            return response()->json(['message' => 'Telegram Bot not found'], 404);
        }

        $telegramBot->token = $this->decryptToken($telegramBot->token);

        return response()->json($telegramBot);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'bot_id' => 'required|string|unique:telegram_bots,bot_id',
            'image_url' => 'nullable|string',
            'about' => 'nullable|string',
            'description' => 'nullable|string',
            'username' => 'required|string|unique:telegram_bots,username',
            'token' => 'required|string|unique:telegram_bots,token',
        ]);

        //encrypt
        $validated['token'] = $this->encryptToken($validated['token']);

        $stored = TelegramBot::create($validated);

        return response()->json($stored, 201);
    }

    public function getTelegramBot($id)
    {
        $TelegramBot = TelegramBot::find($id);
        
        if (!$TelegramBot) {
            return response()->json(['message' => 'Telegram Bot not found'], 404);
        }

        $TelegramBot->token = $this->decryptToken($TelegramBot->token);

        return response()->json($TelegramBot);
    }
    
    public function update(Request $request, $id)
    {
        $TelegramBot = TelegramBot::find($id);
        
        if (!$TelegramBot) {
            return response()->json(['message' => 'Telegram Bot not found'], 404);
        }

        $validated = $request->validate([
            'image_url' => 'nullable|string',
            'about' => 'nullable|string',
            'description' => 'nullable|string',
            'username' => 'sometimes|string|unique:telegram_bots,username,'.$TelegramBot->id,
            'token' => 'sometimes|string|unique:telegram_bots,token,'.$TelegramBot->id,
        ]);

        if (isset($validated['token'])) {
            //encrypt
            $validated['token'] = $this->encryptToken($validated['token']);
        }

        $TelegramBot->update($validated);

        return response()->json($TelegramBot);
    }

    public function destroy($id)
    {
        $TelegramBot = TelegramBot::find($id);
        
        if (!$TelegramBot) {
            return response()->json(['message' => 'Telegram Bot not found'], 404);
        }

        $TelegramBot->delete();
        return response()->json(null, 204);
    }

    private function decryptToken($encryptedValue)
    {
        try {
            return Crypt::decryptString($encryptedValue);
        } catch (DecryptException $e) {
            // Handle decryption error
            return null;
        }
    }
    private function encryptToken($plainValue)
    {
        return Crypt::encryptString($plainValue);
    }
}
