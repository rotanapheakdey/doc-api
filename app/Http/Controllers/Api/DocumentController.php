<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    // --------------------------------------------------------
    // 1. UPLOAD A NEW DOCUMENT (Restricted to File Dept)
    // --------------------------------------------------------
    public function store(Request $request)
    {
        $user = Auth::user();

        // Security Gate: Only the File Department can initiate a document
        if ($user->role !== 'file_dept') {
            return response()->json(['message' => 'Unauthorized. Only File Department can upload.'], 403);
        }

        // Validate the incoming document payload
        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB max
            'comment' => 'nullable|string'
        ]);

        // Save the physical file in the 'storage/app/public/documents' folder
        $path = $request->file('file')->store('documents', 'public');

        // Generate a unique Ministry Control Number (e.g., DOC-20260604-A1B2)
        $controlNo = 'DOC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        // Create the database record
        $document = Document::create([
            'uploaded_by_user_id' => $user->id,
            'control_no' => $controlNo,
            'title' => $request->title,
            'file_path' => $path,
            'file_dept_comment' => $request->comment,
            'status' => 'pending_dg_init', // First step in our state machine
        ]);

        // Log the action for the audit trail
        AuditLog::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'action' => 'uploaded',
            'notes' => 'Document scanned and uploaded into the system.'
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully!',
            'document' => $document
        ], 201);
    }

    // --------------------------------------------------------
    // 2. THE SMART URGENT FEED (Changes based on Role)
    // --------------------------------------------------------
    public function urgentFeed()
    {
        $user = Auth::user();
        $query = Document::query();

        // What you see depends entirely on the badge you are wearing
        switch ($user->role) {
            case 'dg':
                // DG sees brand new docs (needs direction) OR docs approved by VDG (needs final signature)
                $query->whereIn('status', ['pending_dg_init', 'vdg_approved']);
                break;

            case 'vdg':
                // VDG sees docs assigned to their specific department that are awaiting approval
                $query->where('assigned_department_id', $user->department_id)
                    ->where('status', 'pending_vdg');
                break;

            case 'staff':
                // Staff sees docs currently processing in their specific department
                $query->where('assigned_department_id', $user->department_id)
                    ->where('status', 'processing_dept');
                break;

            case 'file_dept':
                // File Dept sees docs that are fully signed by DG and need physical archiving
                $query->where('status', 'dg_signed');
                break;
        }

        // Fetch them, oldest first (First In, First Out)
        $documents = $query->oldest()->get();

        return response()->json([
            'role' => $user->role,
            'urgent_count' => $documents->count(),
            'documents' => $documents
        ]);
    }
}
