<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->get();
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Rules\Password::defaults()],
            'phone' => 'nullable|string|max:20',
            'role_id' => 'required|exists:roles,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create(collect($validated)->except('image')->toArray());

        if ($request->hasFile('image')) {
            $result = $this->storageService->upload($request->file('image'));
            $user->update([
                'profile_image_url' => $result['url'],
            ]);
        }

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        $user->load(['role', 'buildings', 'bakongAccounts', 'settings', 'tenantContracts', 'tenantPayments']);
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['sometimes', Rules\Password::defaults()],
            'phone' => 'nullable|string|max:20',
            'role_id' => 'sometimes|exists:roles,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update(collect($validated)->except('image')->toArray());

        if ($request->hasFile('image')) {
            $result = $this->storageService->upload($request->file('image'));
            $user->update([
                'profile_image_url' => $result['url'],
            ]);
        }

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return response()->json($user);
    }

    public function register(Request $request, UserRegistrationService $service){ 
        $response = $service->register($request->all());
        return $response;
    }
}