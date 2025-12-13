<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class StorageService
{
    protected string $imageBaseUrl;
    protected string $disk;

    public function __construct()
    {
        $this->imageBaseUrl = config('services.image.url') ?? env('IMAGE_URL', '');
        $this->disk = 'external';
    }

    /**
     * Upload file to external storage
     */
    public function upload(UploadedFile $file): array
    {
        try {
            $filename = time() . '_' . $file->getClientOriginalName();
            
            $disk = Storage::disk($this->disk);
            
            // Upload file to external storage
            $path = $disk->putFileAs('', $file, $filename);
            $url = $disk->url($path);

            Log::info('File uploaded to external storage', [
                'path' => $path,
                'url' => $url,
                'filename' => $filename,
            ]);

            return [
                'path' => $path,
                'url' => $url,
                'filename' => $filename,
            ];
        } catch (Exception $e) {
            Log::error('Error uploading file to external storage', [
                'error' => $e->getMessage(),
                'filename' => $file->getClientOriginalName(),
            ]);

            throw new Exception('Failed to upload file: ' . $e->getMessage());
        }
    }

    /**
     * Fetch file from external storage via HTTP
     */
    public function get(string $filename): Response
    {
        try {
            $url = $this->imageBaseUrl . '/' . $filename;
            
            Log::info('Fetching image from external storage', [
                'url' => $url,
                'filename' => $filename,
            ]);

            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                Log::warning('Image not found on external storage', [
                    'filename' => $filename,
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                
                abort(404, 'Image not found');
            }

            $contentType = $response->header('Content-Type') ?? 'image/jpeg';

            return response($response->body(), 200)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'public, max-age=31536000');
                
        } catch (Exception $e) {
            Log::error('Error fetching file from external storage', [
                'error' => $e->getMessage(),
                'filename' => $filename,
            ]);

            abort(500, 'Error fetching image');
        }
    }

    /**
     * Delete file from external storage
     */
    public function delete(string $path): bool
    {
        try {
            $disk = Storage::disk($this->disk);
            
            if (!$disk->exists($path)) {
                Log::warning('Attempted to delete non-existent file', [
                    'path' => $path,
                ]);
                return false;
            }

            $deleted = $disk->delete($path);

            if ($deleted) {
                Log::info('File deleted from external storage', [
                    'path' => $path,
                ]);
            }

            return $deleted;
        } catch (Exception $e) {
            Log::error('Error deleting file from external storage', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);

            return false;
        }
    }

    /**
     * Get the full URL for a given path
     */
    public function url(string $path): string
    {
        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Check if file exists on external storage
     */
    public function exists(string $path): bool
    {
        try {
            return Storage::disk($this->disk)->exists($path);
        } catch (Exception $e) {
            Log::error('Error checking file existence on external storage', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);

            return false;
        }
    }

    /**
     * List all files in external storage
     */
    public function listFiles(string $directory = ''): array
    {
        try {
            $disk = Storage::disk($this->disk);
            return $disk->files($directory);
        } catch (Exception $e) {
            Log::error('Error listing files from external storage', [
                'error' => $e->getMessage(),
                'directory' => $directory,
            ]);

            return [];
        }
    }
}