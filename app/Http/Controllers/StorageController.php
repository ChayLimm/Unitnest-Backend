<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
        $url = env('REMOTE_STORAGE_URL') . 'upload';
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png|max:2048'
        ]);

        $file = $request->file('image');

        $filename = time() . '_' . $file->getClientOriginalName();

        $disk = Storage::disk('external');

        $path = $disk->putFileAs('', $file, $filename);

        $url = $disk->url($path);

        Log::info("File uploaded to external storage", ['path' => $path, 'url' => $url]);

        return response()->json([
            'message' => 'Uploaded successfully',
            'path' => $path,
            'url' => $url,
        ]);
    }
}
