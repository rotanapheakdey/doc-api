<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    // PHASE 1: THE FRONT ENTRY DESK

    /**
     * 1. UPLOAD A NEW DOCUMENT (Restricted to File Dept)
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'file_dept') {
            return response()->json(['message' => 'Unauthorized. Only File Department can upload.'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB max
            'comment' => 'nullable|string'
        ]);

        $path = $request->file('file')->store('documents', 'public');
        $controlNo = 'DOC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $document = Document::create([
            'uploaded_by_user_id' => $user->id,
            'control_no' => $controlNo,
            'title' => $request->title,
            'file_path' => $path,
            'file_dept_comment' => $request->comment,
            'status' => 'pending_dg_init',
        ]);

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

    // PHASE 2: THE EXECUTIVE OFFICE

    /**
     * 2. DG ASSIGN (Routes document back to File Dept for check/dispatch)
     */
    public function direct(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'dg') {
            return response()->json(['message' => 'Unauthorized. Only DG can assign departments.'], 403);
        }

        $request->validate([
            'assigned_department_id' => 'required|exists:departments,id',
            'dg_note' => 'nullable|string|max:500'
        ]);

        $document = Document::findOrFail($id);

        if ($document->status !== 'pending_dg_init') {
            return response()->json(['message' => 'Document is not in initiation phase.'], 422);
        }

        // 💡 Loop back: Moves to pending_dispatch so File Dept gets it back
        $document->update([
            'assigned_department_id' => $request->assigned_department_id,
            'status' => 'pending_dispatch',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'action' => 'assigned',
            'notes' => 'DG assigned file to Department #' . $request->assigned_department_id . '. Executive Note: ' . ($request->dg_note ?? 'None')
        ]);

        $document->load(['uploader:id,name', 'department:id,name']);

        return response()->json([
            'message' => 'Document assigned. Sent back to File Department for final dispatch.',
            'document' => $document
        ], 200);
    }

    // PHASE 3: RETURN TO FRONT DESK FOR DISPATCH

    /**
     * 3. DISPATCH DOCUMENT (File Dept approves DG assignment and sends to VDG)
     */
    public function dispatch(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'file_dept') {
            return response()->json(['message' => 'Unauthorized. Only File Department can dispatch.'], 403);
        }

        $request->validate([
            'additional_comment' => 'nullable|string|max:500'
        ]);

        $document = Document::findOrFail($id);

        if ($document->status !== 'pending_dispatch') {
            return response()->json(['message' => 'This document is not awaiting dispatch.'], 422);
        }

        // Dispatch: Update comment if provided, transition status so VDG can see it
        $document->update([
            'status' => 'dg_directed',
            'file_dept_comment' => $request->additional_comment
                ? $document->file_dept_comment . ' | Dispatch Note: ' . $request->additional_comment
                : $document->file_dept_comment
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'action' => 'dispatched',
            'notes' => 'File Department reviewed and officially dispatched the file to the assigned department.'
        ]);

        $document->load(['uploader:id,name', 'department:id,name']);

        return response()->json([
            'message' => 'Document officially dispatched to the target department successfully!',
            'document' => $document
        ], 200);
    }

    // PHASE 4 & 5: DEPARTMENT PROCESSING & SIGNING

    /**
     * 4. UPLOAD ACTION REPORT (Department VDG uploads the finished work/report)
     */
    public function uploadReport(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'department' && $user->role !== 'staff') {
            return response()->json(['message' => 'Unauthorized. Only Department Staff can upload the action report.'], 403);
        }

        $request->validate([
            'report_file' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $document = Document::findOrFail($id);

        if ($document->assigned_department_id !== $user->department_id) {
            return response()->json(['message' => 'Access Denied. This belongs to another department.'], 403);
        }

        if ($document->status !== 'dg_directed') {
            return response()->json(['message' => 'Document is not in a processable state.'], 422);
        }

        $reportPath = $request->file('report_file')->store('reports', 'public');

        $document->update([
            'status' => 'pending_vdg_approval',
            'file_path' => $reportPath
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'action' => 'report_submitted',
            'notes' => 'Department staff finished execution and uploaded the target action report.'
        ]);

        return response()->json([
            'message' => 'Action report uploaded successfully. Sent to VDG for verification.',
            'document' => $document
        ], 200);
    }

    /**
     * 5. VDG SIGN OFF (VDG signs the report, sending it up to the top office)
     */
    public function vdgSign($id)
    {
        $user = Auth::user();

        if ($user->role !== 'vdg') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $document = Document::findOrFail($id);

        if ($document->assigned_department_id !== $user->department_id) {
            return response()->json(['message' => 'Access Denied.'], 403);
        }

        if ($document->status !== 'pending_vdg_approval') {
            return response()->json(['message' => 'No report found awaiting signature.'], 422);
        }

        $document->update([
            'status' => 'pending_dg_approval'
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'action' => 'vdg_signed',
            'notes' => 'Vice Director General signed off on the report. Routed upwards to the DG.'
        ]);

        return response()->json([
            'message' => 'Document signed by VDG. Routed to the Director General.',
            'document' => $document
        ], 200);
    }

    // PHASE 6 & 7: FINAL EXECUTION & PERMANENT ARCHIVING

    /**
     * 6. DG FINAL SIGN (Director General gives executive sign-off)
     */
    public function dgFinalSign($id)
    {
        $user = Auth::user();

        if ($user->role !== 'dg') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $document = Document::findOrFail($id);

        if ($document->status !== 'pending_dg_approval') {
            return response()->json(['message' => 'Document is not awaiting final executive sign-off.'], 422);
        }

        $document->update([
            'status' => 'dg_signed'
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'action' => 'dg_signed',
            'notes' => 'Director General gave final executive signature validation.'
        ]);

        return response()->json([
            'message' => 'Document officially signed by the DG! Sent to Entry desk for archiving.',
            'document' => $document
        ], 200);
    }

    /**
     * 7. PERMANENT ARCHIVE (File Dept locks down the finalized file)
     */
    public function archive($id)
    {
        $user = Auth::user();

        if ($user->role !== 'file_dept') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $document = Document::findOrFail($id);

        if ($document->status !== 'dg_signed') {
            return response()->json(['message' => 'This document has not received all required signatures yet.'], 422);
        }

        $document->update([
            'status' => 'completed_archive'
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'action' => 'archived',
            'notes' => 'Document file safely vaulted in permanent records registry. Lifecycle closed.'
        ]);

        return response()->json([
            'message' => 'Document successfully locked and archived permanently!',
            'document' => $document
        ], 200);
    }

    // SEARCH & ARCHIVE VISIBILITY
    public function searchArchive(Request $request)
    {
        $user = Auth::user();

        // Base Query: Only look at files that are permanently locked
        $query = Document::where('status', 'completed_archive');

        // 🛡️ THE SECURITY GATE:
        if (in_array($user->role, ['vdg', 'staff', 'department'])) {
            // Rule 1: Department accounts can ONLY see files assigned to their specific ID
            $query->where('assigned_department_id', $user->department_id);
        }

        // Rule 2: If the user is 'dg' or 'file_dept', we don't add the filter above.
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('control_no', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // Fetch the results
        $documents = $query->with(['uploader:id,name', 'department:id,name'])
                           ->orderBy('updated_at', 'desc')
                           ->get();

        return response()->json([
            'user_role' => $user->role,
            'access_level' => in_array($user->role, ['dg', 'file_dept']) ? 'Global Access' : 'Department Restricted',
            'result_count' => $documents->count(),
            'documents' => $documents
        ], 200);
    }

    // CORE VISIBILITY SYSTEMS (URGENT FEEDS & INBOXES)

    /**
     * THE SMART URGENT FEED (Changes dynamically based on role tracking)
     */
    public function urgentFeed()
    {
        $user = Auth::user();
        $query = Document::query();

        switch ($user->role) {
            case 'dg':
                $query->whereIn('status', ['pending_dg_init', 'pending_dg_approval']);
                break;

            case 'file_dept':
                $query->whereIn('status', ['pending_dispatch', 'dg_signed']);
                break;

            case 'department':
            case 'staff':
                $query->where('assigned_department_id', $user->department_id)
                      ->where('status', 'dg_directed');
                break;

            case 'vdg':
                $query->where('assigned_department_id', $user->department_id)
                      ->where('status', 'pending_vdg_approval');
                break;
        }

        $documents = $query->oldest()->get();

        return response()->json([
            'role' => $user->role,
            'urgent_count' => $documents->count(),
            'documents' => $documents
        ]);
    }

    /**
     * DEPARTMENT INBOX (For VDG to monitor files actively being worked on)
     */
    public function departmentInbox(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['vdg', 'department', 'staff']) || !$user->department_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $documents = Document::where('assigned_department_id', $user->department_id)
            ->whereIn('status', ['dg_directed', 'pending_vdg_approval'])
            ->with(['uploader:id,name'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'user_name' => $user->name,
            'role' => $user->role,
            'department_id' => $user->department_id,
            'document_count' => $documents->count(),
            'documents' => $documents
        ], 200);
    }
}
