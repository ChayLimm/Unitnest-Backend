<?php

namespace App\Http\Controllers;

use App\Services\StorageService;
use Illuminate\Http\Request;

class StorageController extends Controller
{
    public function __construct(
        private StorageService $storageService
    ) {}

    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        $result = $this->storageService->upload(
            $request->file('image')
        );

        return response()->json([
            'message' => 'Uploaded successfully',
            'path'    => $result['path'],
            'url'     => $result['url'],
        ], 201);
    }

    public function show(string $path)
    {
        // Decode the path if it's URL encoded
        $path = urldecode($path);
        
        return $this->storageService->get($path);
    }

    public function destroy(string $path)
    {
        $path = urldecode($path);
        
        if (!$this->storageService->exists($path)) {
            return response()->json([
                'message' => 'File not found'
            ], 404);
        }

        $this->storageService->delete($path);

        return response()->json([
            'message' => 'File deleted successfully'
        ], 200);
    }
}