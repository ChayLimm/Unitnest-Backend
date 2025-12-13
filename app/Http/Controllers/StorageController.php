<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StorageController extends Controller
{
    public function __construct(
        private StorageService $storageService
    ) {}

    /**
     * Upload image to external storage
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $result = $this->storageService->upload(
                $request->file('image')
            );

            return response()->json([
                'message' => 'Uploaded successfully',
                'url' => $result['url'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get image from external storage via HTTP proxy
     */
    public function imageUrl(string $id): Response
    {
        return $this->storageService->get($id);
    }

    /**
     * Delete image from external storage
     */
    public function destroy(string $path): JsonResponse
    {
        $path = urldecode($path);
        
        if (!$this->storageService->exists($path)) {
            return response()->json([
                'message' => 'File not found'
            ], 404);
        }

        $deleted = $this->storageService->delete($path);

        if ($deleted) {
            return response()->json([
                'message' => 'File deleted successfully'
            ], 200);
        }

        return response()->json([
            'message' => 'Failed to delete file'
        ], 500);
    }

    /**
     * List all uploaded images
     */
    public function index(): JsonResponse
    {
        $files = $this->storageService->listFiles();
        
        $images = array_map(function ($file) {
            return [
                'path' => $file,
                'url' => $this->storageService->url($file),
            ];
        }, $files);

        return response()->json([
            'images' => $images,
            'count' => count($images)
        ]);
    }
}