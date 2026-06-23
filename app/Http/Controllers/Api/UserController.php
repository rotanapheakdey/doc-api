<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('department')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'users' => $users
        ], 200);
    }

    public function store(Request $request)
    {
        $authenticatedUser = auth('sanctum')->user();

        if (!$authenticatedUser || !in_array($authenticatedUser->role, ['dg', 'file_dept'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only Director General or File Department can create users'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:dg,vdg,file_dept,department,staff',
            'department_id' => 'nullable|exists:departments,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if (in_array($request->role, ['dg', 'file_dept'])) {
            $request->merge(['department_id' => null]);
        }

        $avatarPath = null;
        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'department_id' => $request->department_id,
            'avatar' => $avatarPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'user' => $user->load('department')
        ], 201);
    }

    public function show($id)
    {
        $user = User::with('department')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => $user
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $authenticatedUser = auth('sanctum')->user();

        $isOwnProfile = $authenticatedUser && $authenticatedUser->id === $user->id;
        $canManageUsers = $authenticatedUser && in_array($authenticatedUser->role, ['dg', 'file_dept']);

        if (!$isOwnProfile && !$canManageUsers) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update your own profile'
            ], 403);
        }

        $rules = [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|min:6',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        if ($canManageUsers) {
            $rules['role'] = 'sometimes|in:dg,vdg,file_dept,department,staff';
            $rules['department_id'] = 'nullable|exists:departments,id';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle avatar upload
        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;
        }

        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('password')) $user->password = Hash::make($request->password);

        if ($canManageUsers) {
            if ($request->has('role')) {
                $user->role = $request->role;

                if (in_array($request->role, ['dg', 'file_dept'])) {
                    $user->department_id = null;
                }
            }

            if ($request->has('department_id') && !in_array($user->role, ['dg', 'file_dept'])) {
                $user->department_id = $request->department_id;
            }
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => $user->load('department')
        ], 200);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $authenticatedUser = auth('sanctum')->user();

        if ($authenticatedUser && $authenticatedUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        if (!$authenticatedUser || !in_array($authenticatedUser->role, ['dg', 'file_dept'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can delete users'
            ], 403);
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ], 200);
    }

    public function getDepartments()
    {
        $departments = Department::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'departments' => $departments
        ], 200);
    }

    public function updateAvatar(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $authenticatedUser = auth('sanctum')->user();

        if (!$authenticatedUser || $authenticatedUser->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update your own avatar'
            ], 403);
        }

        // Handle avatar removal
        if ($request->has('remove_avatar') && $request->remove_avatar === true) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
                $user->avatar = null;
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Avatar removed successfully',
                'avatar_url' => $user->avatar_url
            ], 200);
        }

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Avatar updated successfully',
            'avatar_url' => $user->avatar_url
        ], 200);
    }

    public function removeAvatar($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $authenticatedUser = auth('sanctum')->user();

        if (!$authenticatedUser || $authenticatedUser->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only remove your own avatar'
            ], 403);
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Avatar removed successfully',
            'avatar_url' => $user->avatar_url
        ], 200);
    }
}
