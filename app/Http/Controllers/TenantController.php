<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantController extends Controller
{
    /**
     * Get all tenants for the current landlord
     */
    public function index(Request $request): JsonResponse
    {
        // Get current authenticated user (landlord)
        $landlordId = Auth::id();
        
        // Start query for this landlord's tenants
        $query = Tenant::where('landlord_id', $landlordId);
        
        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Filter by criteria
        if ($request->has('has_telegram') && $request->has_telegram == 'true') {
            $query->whereNotNull('telegram_id');
        }
        
        if ($request->has('has_identification') && $request->has_identification == 'true') {
            $query->whereNotNull('identify_id');
        }
        
        // Order by
        $orderBy = $request->get('order_by', 'created_at');
        $orderDirection = $request->get('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);
        
        // Pagination
        $perPage = $request->get('per_page', 15);
        $tenants = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $tenants->items(),
            'pagination' => [
                'total' => $tenants->total(),
                'per_page' => $tenants->perPage(),
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'from' => $tenants->firstItem(),
                'to' => $tenants->lastItem(),
            ],
            'message' => 'Tenants retrieved successfully'
        ]);
    }

    /**
     * Create a new tenant
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        // Get current authenticated user (landlord)
        // $landlordId = Auth::id();
        
        // Merge landlord_id with validated data
        // $data = array_merge($request->validated(), [
        //     'landlord_id' => $landlordId,
        // ]);
        $data = $request->validated();
        
        $tenant = Tenant::create($data);
        
        return response()->json([
            'success' => true,
            'data' => $tenant,
            'message' => 'Tenant created successfully'
        ], 201);
    }

    /**
     * Get a specific tenant
     */
    public function show(string $id): JsonResponse
    {
        // Get current authenticated user (landlord)
        $landlordId = Auth::id();
        
        $tenant = Tenant::where('landlord_id', $landlordId)->find($id);
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found or you do not have permission to access it.'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $tenant,
            'message' => 'Tenant retrieved successfully'
        ]);
    }

    /**
     * Update a tenant
     */
    public function update(UpdateTenantRequest $request, string $id): JsonResponse
    {
        // Get current authenticated user (landlord)
        $landlordId = Auth::id();
        
        $tenant = Tenant::where('landlord_id', $landlordId)->find($id);
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found or you do not have permission to update it.'
            ], 404);
        }
        
        $tenant->update($request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $tenant->fresh(),
            'message' => 'Tenant updated successfully'
        ]);
    }

    /**
     * Delete a tenant
     */
    public function destroy(string $id): JsonResponse
    {
        // Get current authenticated user (landlord)
        $landlordId = Auth::id();
        
        $tenant = Tenant::where('landlord_id', $landlordId)->find($id);
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found or you do not have permission to delete it.'
            ], 404);
        }
        
        $tenant->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Tenant deleted successfully.'
        ]);
    }

    /**
     * Get all tenants by landlord_id (specific landlord)
     */
    public function getByLandlord($landlordId, Request $request): JsonResponse
    {
        $tenants = Tenant::where('landlord_id', $landlordId)->get();
        
        return response()->json([
            'success' => true,
            'data' => $tenants,
            'count' => $tenants->count(),
            'message' => 'Tenants retrieved successfully'
        ]);
    }

    /**
     * Simple search endpoint
     */
    public function search(Request $request): JsonResponse
    {
        $landlordId = Auth::id();
        $search = $request->get('q', '');
        
        if (empty($search)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No search query provided'
            ]);
        }
        
        $tenants = Tenant::where('landlord_id', $landlordId)
            ->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('identify_id', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $tenants,
            'count' => $tenants->count(),
            'message' => 'Search results retrieved successfully'
        ]);
    }
}