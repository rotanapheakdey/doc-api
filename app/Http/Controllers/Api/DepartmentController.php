<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    /**
     * Get list of departments for dropdowns
     */
    public function index(): JsonResponse
    {
        // Fetch only necessary fields to keep the API payload light
        $departments = Department::select('id', 'name', 'code')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($departments);
    }
}
