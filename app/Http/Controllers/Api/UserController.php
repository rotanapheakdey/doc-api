<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * List all users with optional filtering.
     */
    public function index(Request $request)
    {
        $query = User::with('department');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->boolean('unassigned')) {
            $query->whereNull('department_id');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'users' => $users
        ], 200);
    }

    /**
     * Create a new user with tiered authorization boundaries.
     */
    public function store(Request $request)
    {
        $caller = auth('sanctum')->user();

        if (!$caller || !$caller->canManageUsers()) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can create users'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:super_admin,dg,file_dept,vdg,staff,department',
            'department_id' => 'nullable|exists:departments,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Tiered Privilege Enforcement:
        // Lower admins (dg, file_dept) CANNOT create super_admin, dg, or file_dept
        if (!$caller->isSuperAdmin()) {
            if (in_array($request->role, ['super_admin', 'dg', 'file_dept'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not have privilege to create accounts with role: ' . $request->role
                ], 403);
            }
        }

        // Administrative roles cannot have a department
        $departmentId = $request->department_id;
        if (in_array($request->role, ['super_admin', 'dg', 'file_dept'])) {
            $departmentId = null;
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
            'department_id' => $departmentId,
            'avatar' => $avatarPath,
        ]);

        // Audit Logging
        AuditLog::create([
            'user_id' => $caller->id,
            'document_id' => null,
            'action' => $user->role === 'super_admin' ? 'super_admin_created' : 'user_created',
            'notes' => "User {$user->email} ({$user->role}) created by {$caller->name} ({$caller->role})",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'user' => $user->load('department')
        ], 201);
    }

    /**
     * View user details.
     */
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

    /**
     * Update user details with privilege boundaries and audit trail.
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $caller = auth('sanctum')->user();
        $isOwnProfile = $caller && $caller->id === $user->id;
        $canManageUsers = $caller && $caller->canManageUsers();

        if (!$isOwnProfile && !$canManageUsers) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update your own profile'
            ], 403);
        }

        // Tiered Privilege Enforcement:
        if (!$caller->isSuperAdmin()) {
            // Lower admins cannot modify super_admin, dg, or file_dept accounts (unless updating own profile)
            if (!$isOwnProfile && in_array($user->role, ['super_admin', 'dg', 'file_dept'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You cannot modify higher administrative tier accounts.'
                ], 403);
            }

            // Lower admins cannot elevate any user to super_admin, dg, or file_dept
            if ($request->has('role') && in_array($request->role, ['super_admin', 'dg', 'file_dept'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You cannot elevate accounts to role: ' . $request->role
                ], 403);
            }
        }

        // Final active SuperAdmin protection: cannot demote self
        if ($user->isSuperAdmin() && $request->has('role') && $request->role !== 'super_admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'The system must never allow demoting the final active SuperAdmin.'
                ], 422);
            }
        }

        $rules = [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|min:6',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        if ($canManageUsers) {
            $rules['role'] = 'sometimes|in:super_admin,dg,file_dept,vdg,staff,department';
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

        $oldRole = $user->role;
        $oldDeptId = $user->department_id;

        if ($canManageUsers) {
            if ($request->has('role')) {
                $user->role = $request->role;

                if (in_array($request->role, ['super_admin', 'dg', 'file_dept'])) {
                    $user->department_id = null;
                }
            }

            if ($request->has('department_id') && !in_array($user->role, ['super_admin', 'dg', 'file_dept'])) {
                $user->department_id = $request->department_id;
            }
        }

        $user->save();

        // Audit Logging for Role / Department Changes
        if ($oldRole !== $user->role) {
            AuditLog::create([
                'user_id' => $caller->id,
                'document_id' => null,
                'action' => ($user->role === 'super_admin' || $oldRole === 'super_admin') ? 'super_admin_role_changed' : 'user_role_changed',
                'notes' => "User {$user->email} role changed from '{$oldRole}' to '{$user->role}' by {$caller->name}",
            ]);
        }

        if ($oldDeptId != $user->department_id) {
            $action = $user->department_id ? ($oldDeptId ? 'user_department_changed' : 'user_department_assigned') : 'user_department_removed';
            AuditLog::create([
                'user_id' => $caller->id,
                'document_id' => null,
                'action' => $action,
                'notes' => "User {$user->email} department changed from " . ($oldDeptId ?: 'none') . " to " . ($user->department_id ?: 'none') . " by {$caller->name}",
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => $user->load('department')
        ], 200);
    }

    /**
     * Delete user with safeguards against self-deletion and final SuperAdmin deletion.
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $caller = auth('sanctum')->user();

        if (!$caller || !$caller->canManageUsers()) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can delete users'
            ], 403);
        }

        // SuperAdmin cannot delete their own account
        if ($caller->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        // The system must never allow deletion of the final active SuperAdmin
        if ($user->isSuperAdmin()) {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'The system must never allow deletion of the final active SuperAdmin.'
                ], 422);
            }
        }

        // Tiered Privilege Enforcement: Lower admins cannot delete administrative accounts
        if (!$caller->isSuperAdmin()) {
            if (in_array($user->role, ['super_admin', 'dg', 'file_dept'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not have privilege to delete administrative accounts.'
                ], 403);
            }
        }

        // Record Audit Log
        AuditLog::create([
            'user_id' => $caller->id,
            'document_id' => null,
            'action' => $user->isSuperAdmin() ? 'super_admin_removed' : 'user_deleted',
            'notes' => "User {$user->email} ({$user->role}) deleted by {$caller->name} ({$caller->role})",
        ]);

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        if ($user->signature) {
            Storage::disk('public')->delete($user->signature);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ], 200);
    }

    /**
     * Backward-compatible department list
     */
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

    public function updateSignature(Request $request, $id)
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
                'message' => 'You can only update your own signature'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'signature' => 'required|image|mimes:png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('signature') && $request->file('signature')->isValid()) {
            if ($user->signature) {
                Storage::disk('public')->delete($user->signature);
            }

            $signaturePath = $request->file('signature')->store('signatures', 'public');
            $user->signature = $signaturePath;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Signature updated successfully',
            'signature_url' => $user->signature_url
        ], 200);
    }

    public function removeSignature($id)
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
                'message' => 'You can only remove your own signature'
            ], 403);
        }

        if ($user->signature) {
            Storage::disk('public')->delete($user->signature);
            $user->signature = null;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Signature removed successfully',
            'signature_url' => $user->signature_url
        ], 200);
    }
}
