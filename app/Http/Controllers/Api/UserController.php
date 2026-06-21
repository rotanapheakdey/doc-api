<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
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

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:dg,vdg,file_dept,department,staff',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // ✅ If role is DG or file_dept, department_id must be null
        if (in_array($request->role, ['dg', 'file_dept'])) {
            $request->merge(['department_id' => null]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'department_id' => $request->department_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'user' => $user->load('department')
        ], 201);
    }

    /**
     * Display the specified user.
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
     * Update the specified user.
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

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|min:6',
            'role' => 'sometimes|in:dg,vdg,file_dept,department,staff',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // ✅ If role is DG or file_dept, department_id must be null
        if ($request->has('role') && in_array($request->role, ['dg', 'file_dept'])) {
            $user->department_id = null;
        }

        // Update fields
        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('password')) $user->password = Hash::make($request->password);
        if ($request->has('role')) {
            $user->role = $request->role;

            // ✅ If new role is DG or file_dept, clear department
            if (in_array($request->role, ['dg', 'file_dept'])) {
                $user->department_id = null;
            }
        }

        // ✅ Only update department_id if role is NOT dg or file_dept
        if ($request->has('department_id') && !in_array($user->role, ['dg', 'file_dept'])) {
            $user->department_id = $request->department_id;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => $user->load('department')
        ], 200);
    }

    /**
     * Remove the specified user.
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

        // ✅ Fix: Get authenticated user properly
        $authenticatedUser = auth('sanctum')->user();

        // ✅ Prevent deleting yourself
        if ($authenticatedUser && $authenticatedUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ], 200);
    }

    /**
     * Get all departments for dropdown.
     */
    public function getDepartments()
    {
        $departments = Department::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'departments' => $departments
        ], 200);
    }
}
