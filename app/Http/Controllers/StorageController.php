<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StorageController extends Controller
{
    public function imageUrl(Request $request)
    {
        $name = $request->input('name');
        $url = env('IMAGE_URL') . '/' . $name;

        $response = Http::get($url);

        if ($response->successful()) {
            return response($response->body(), 200)
                    ->header('Content-Type', $response->header('Content-Type'));
        }

        return response()->json(['message' => 'Image not found'], 404);
    }
    
}
