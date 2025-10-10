<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::with('user')->get();
        return response()->json($settings);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:settings,user_id',
            'general_rules' => 'nullable|string',
            'contract_rules' => 'nullable|string',
            'khr_currency' => 'nullable|numeric',
        ]);

        $setting = Setting::create($validated);

        return response()->json($setting, 201);
    }

    public function show(Setting $setting)
    {
        $setting->load('user');
        return response()->json($setting);
    }

    public function update(Request $request, Setting $setting)
    {
        $validated = $request->validate([
            'general_rules' => 'nullable|string',
            'contract_rules' => 'nullable|string',
            'khr_currency' => 'nullable|numeric',
        ]);

        $setting->update($validated);

        return response()->json($setting);
    }

    public function getUserSettings($userId)
    {
        $settings = Setting::where('user_id', $userId)->first();
        
        if (!$settings) {
            return response()->json(['message' => 'Settings not found'], 404);
        }

        return response()->json($settings);
    }
}