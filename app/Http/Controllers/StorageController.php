<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StorageController extends Controller
{
    public function imageUrl(Request $request, $id)
    {
        // $name = $request->input('name');
        $name = $id;
        $url = env('IMAGE_URL') . '/' . $name;

        $response = Http::get($url);

        if ($response->successful()) {
            return response($response->body(), 200)
                    ->header('Content-Type', $response->header('Content-Type'));
        }

        return response()->json(['message' => 'Image not found'], 404);
    }

    public function upload(Request $request)
    {
        $url = env('REMOTE_STORAGE_URL') . '/' . 'upload';
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png|max:2048'
        ]);

        $file = $request->file('image');

        $response = Http::attach(
            'file', file_get_contents($file), $file->getClientOriginalName()
        )->post($url);

        if ($response->successful()) {
            $data = $response->json();

            // e.g. remote server returns: { "url": "https://files.example.com/images/filename.jpg" }
            return response()->json([
                'message' => 'Uploaded successfully',
                'image_url' => $data['url'] ?? null
            ]);
        }

        return response()->json(['message' => 'Failed to upload image'], 500);
    }
    
}
