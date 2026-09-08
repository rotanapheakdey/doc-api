<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    /**
     * List audit logs with relations and filtering.
     * Accessible to SuperAdmin, DG, and File Dept.
     */
    public function index(Request $request): JsonResponse
    {
        $caller = auth('sanctum')->user();

        if (!$caller || !in_array($caller->role, ['super_admin', 'dg', 'file_dept'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view audit logs.'
            ], 403);
        }

        $query = AuditLog::with([
            'user:id,name,email,role',
        ]);

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('document_id')) {
            $query->where('document_id', $request->document_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $perPage = $request->integer('per_page', 50);
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'logs' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ]
        ], 200);
    }
}
