<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class StorageService
{
    /**
     * Upload file to public storage
     */
    public function upload(UploadedFile $file): array
    {
        $filename = time() . '_' . $file->getClientOriginalName();

        // Store in public disk under 'images' folder
        $path = $file->storeAs('images', $filename, 'public');
        
        // Get the public URL
        $url = Storage::disk('public')->url($path);

        Log::info('File uploaded to public storage', [
            'path' => $path,
            'url'  => $url,
        ]);

        return [
            'path' => $path,
            'url'  => $url,
            'filename' => $filename,
        ];
    }

    /**
     * Fetch file from storage
     */
    public function get(string $path): Response
    {
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File not found');
        }

        $fileContent = Storage::disk('public')->get($path);
        $mimeType = Storage::disk('public')->mimeType($path);

        return response($fileContent, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000');
    }

    /**
     * Delete file from storage
     */
    public function delete(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }

    /**
     * Get the full URL for a given path
     */
    public function url(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    /**
     * Check if file exists
     */
    public function exists(string $path): bool
    {
        return Storage::disk('public')->exists($path);
    }
}