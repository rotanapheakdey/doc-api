<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Models\Document;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    /**
     * Get list of departments with member counts and optional status/search filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Department::query();

        if ($request->filled('status') && in_array($request->status, ['active', 'inactive'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $departments = $query->withCount([
                'users',
                'users as vdg_count' => function ($q) {
                    $q->where('role', 'vdg');
                },
                'users as staff_count' => function ($q) {
                    $q->whereIn('role', ['staff', 'department']);
                },
                'documents'
            ])
            ->with(['vdgs:id,name,email,role,avatar,department_id'])
            ->orderByRaw("CASE WHEN status = 'active' THEN 1 ELSE 2 END")
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'departments' => $departments,
            'data' => $departments // backward compatibility
        ], 200);
    }

    /**
     * View detailed department information including assigned VDGs and Staff.
     */
    public function show($id): JsonResponse
    {
        $department = Department::withCount([
                'users',
                'users as vdg_count' => function ($q) {
                    $q->where('role', 'vdg');
                },
                'users as staff_count' => function ($q) {
                    $q->whereIn('role', ['staff', 'department']);
                },
                'documents'
            ])
            ->with([
                'vdgs:id,name,email,role,avatar,department_id',
                'staff:id,name,email,role,avatar,department_id'
            ])
            ->find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'department' => $department,
            'data' => $department
        ], 200);
    }

    /**
     * Create a new department (SuperAdmin only).
     */
    public function store(Request $request): JsonResponse
    {
        $caller = auth('sanctum')->user();

        if (!$caller || !$caller->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only SuperAdmin can create departments.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name',
            'code' => 'nullable|string|max:50|unique:departments,code',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $department = Department::create([
            'name' => $request->name,
            'code' => $request->code ? strtoupper($request->code) : null,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
        ]);

        AuditLog::create([
            'user_id' => $caller->id,
            'document_id' => null,
            'action' => 'department_created',
            'notes' => "Department '{$department->name}' ({$department->code}) created by {$caller->name}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Department created successfully',
            'department' => $department
        ], 201);
    }

    /**
     * Update department details (SuperAdmin only).
     */
    public function update(Request $request, $id): JsonResponse
    {
        $caller = auth('sanctum')->user();

        if (!$caller || !$caller->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only SuperAdmin can edit departments.'
            ], 403);
        }

        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:departments,name,' . $id,
            'code' => 'nullable|string|max:50|unique:departments,code,' . $id,
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $oldStatus = $department->status;

        if ($request->has('name')) $department->name = $request->name;
        if ($request->has('code')) $department->code = $request->code ? strtoupper($request->code) : null;
        if ($request->has('description')) $department->description = $request->description;
        if ($request->has('status')) $department->status = $request->status;

        $department->save();

        if ($oldStatus !== $department->status && $department->status === 'inactive') {
            AuditLog::create([
                'user_id' => $caller->id,
                'document_id' => null,
                'action' => 'department_deactivated',
                'notes' => "Department '{$department->name}' deactivated by {$caller->name}",
            ]);
        } else {
            AuditLog::create([
                'user_id' => $caller->id,
                'document_id' => null,
                'action' => 'department_updated',
                'notes' => "Department '{$department->name}' updated by {$caller->name}",
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Department updated successfully',
            'department' => $department
        ], 200);
    }

    /**
     * Delete department when safe.
     * Prevents deletion if department has linked users or documents to preserve referential integrity.
     */
    public function destroy($id): JsonResponse
    {
        $caller = auth('sanctum')->user();

        if (!$caller || !$caller->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only SuperAdmin can delete departments.'
            ], 403);
        }

        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        $userCount = User::where('department_id', $id)->count();
        $docCount = Document::where('assigned_department_id', $id)->count();

        if ($userCount > 0 || $docCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot permanently delete department '{$department->name}' because it contains {$userCount} linked user(s) and {$docCount} document(s). To preserve historical records and audit integrity, deactivate the department instead.",
                'linked_users' => $userCount,
                'linked_documents' => $docCount,
                'suggested_action' => 'deactivate'
            ], 422);
        }

        AuditLog::create([
            'user_id' => $caller->id,
            'document_id' => null,
            'action' => 'department_deleted',
            'notes' => "Department '{$department->name}' permanently deleted by {$caller->name}",
        ]);

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully'
        ], 200);
    }

    /**
     * Assign a VDG to a department (SuperAdmin only).
     */
    public function assignVdg(Request $request, $id): JsonResponse
    {
        $caller = auth('sanctum')->user();

        if (!$caller || !$caller->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only SuperAdmin can assign VDGs.'
            ], 403);
        }

        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        if (!$department->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot assign VDG to an inactive department.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::find($request->user_id);

        if ($user->role !== 'vdg') {
            return response()->json([
                'success' => false,
                'message' => 'The selected user does not have the VDG role.'
            ], 422);
        }

        $oldDeptId = $user->department_id;
        $user->department_id = $department->id;
        $user->save();

        AuditLog::create([
            'user_id' => $caller->id,
            'document_id' => null,
            'action' => $oldDeptId ? 'user_department_changed' : 'user_department_assigned',
            'notes' => "VDG {$user->name} assigned to department '{$department->name}' by {$caller->name}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'VDG assigned successfully',
            'user' => $user,
            'vdgs' => $department->vdgs
        ], 200);
    }

    /**
     * Remove a VDG from a department (SuperAdmin only).
     */
    public function removeVdg(Request $request, $id, $userId): JsonResponse
    {
        $caller = auth('sanctum')->user();

        if (!$caller || !$caller->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only SuperAdmin can remove VDGs.'
            ], 403);
        }

        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        $user = User::where('id', $userId)->where('department_id', $id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'VDG not found in this department'
            ], 404);
        }

        $user->department_id = null;
        $user->save();

        AuditLog::create([
            'user_id' => $caller->id,
            'document_id' => null,
            'action' => 'user_department_removed',
            'notes' => "VDG {$user->name} unassigned from department '{$department->name}' by {$caller->name}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'VDG removed from department successfully'
        ], 200);
    }

    /**
     * Assign a Staff member to a department (SuperAdmin only).
     */
    public function assignStaff(Request $request, $id): JsonResponse
    {
        $caller = auth('sanctum')->user();

        if (!$caller || !$caller->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only SuperAdmin can assign Staff.'
            ], 403);
        }

        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        if (!$department->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot assign Staff to an inactive department.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::find($request->user_id);

        if (!in_array($user->role, ['staff', 'department'])) {
            return response()->json([
                'success' => false,
                'message' => 'The selected user does not have the Staff role.'
            ], 422);
        }

        $oldDeptId = $user->department_id;
        $user->department_id = $department->id;
        $user->save();

        AuditLog::create([
            'user_id' => $caller->id,
            'document_id' => null,
            'action' => $oldDeptId ? 'user_department_changed' : 'user_department_assigned',
            'notes' => "Staff {$user->name} assigned to department '{$department->name}' by {$caller->name}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Staff assigned to department successfully',
            'user' => $user,
            'staff' => $department->staff
        ], 200);
    }

    /**
     * Remove a Staff member from a department (SuperAdmin only).
     */
    public function removeStaff(Request $request, $id, $userId): JsonResponse
    {
        $caller = auth('sanctum')->user();

        if (!$caller || !$caller->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only SuperAdmin can remove Staff.'
            ], 403);
        }

        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        $user = User::where('id', $userId)->where('department_id', $id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found in this department'
            ], 404);
        }

        $user->department_id = null;
        $user->save();

        AuditLog::create([
            'user_id' => $caller->id,
            'document_id' => null,
            'action' => 'user_department_removed',
            'notes' => "Staff {$user->name} removed from department '{$department->name}' by {$caller->name}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Staff removed from department successfully'
        ], 200);
    }
}
