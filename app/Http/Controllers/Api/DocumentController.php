<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    // ==========================================
    // PHASE 1: THE FRONT ENTRY DESK (FILE DEPT)
    // ==========================================

    /**
     * List all documents accessible by the authenticated user.
     */
    public function index()
    {
        $user = Auth::user();

        // DG and File Dept have global visibility
        if (in_array($user->role, ['dg', 'file_dept'])) {
            $documents = Document::with(['uploader:id,name', 'department:id,name'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // Department staff and VDG only see their department's documents
            $documents = Document::with(['uploader:id,name', 'department:id,name'])
                ->where('assigned_department_id', $user->department_id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return response()->json([
            'documents' => $documents
        ], 200);
    }

    /**
     * 1. UPLOAD A NEW DOCUMENT (Restricted to File Dept)
     * Target department is pre-selected by File Dept upfront.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'file_dept') {
            return response()->json(['message' => 'Unauthorized. Only File Department can upload.'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'assigned_department_id' => 'required|exists:departments,id',
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB max
            'comment' => 'nullable|string'
        ]);

        $path = $request->file('file')->store('documents', 'public');
        $controlNo = 'DOC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $document = Document::create([
            'uploaded_by_user_id' => $user->id,
            'assigned_department_id' => $request->assigned_department_id,
            'control_no' => $controlNo,
            'title' => $request->title,
            'file_path' => $path,
            'file_dept_comment' => $request->comment,
            'status' => 'pending_dg_init',
            'is_urgent' => false,
            'urgent_reason' => null,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'action' => 'uploaded',
            'notes' => 'Document scanned and uploaded by File Dept with suggested Department #' . $request->assigned_department_id . '.'
        ]);

        $document->load(['uploader:id,name', 'department:id,name']);

        return response()->json([
            'message' => 'Document uploaded successfully and queued for DG review!',
            'document' => $document
        ], 201);
    }

    // ==========================================
    // PHASE 2: THE EXECUTIVE OFFICE (DG DIRECT)
    // ==========================================

    /**
     * 2. DG ASSIGN & AUTO-DISPATCH
     * Confirms department, generates directive, burns signature, and auto-dispatches straight to dg_directed.
     */
    public function direct(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'dg') {
            return response()->json(['message' => 'Unauthorized. Only DG can endorse documents.'], 403);
        }

        $request->validate([
            'assigned_department_id' => 'nullable|exists:departments,id',
            'dg_note' => 'nullable|string|max:500',
            'x' => 'nullable|numeric',
            'y' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'page' => 'nullable|integer',
        ]);

        $document = Document::findOrFail($id);

        if ($document->status !== 'pending_dg_init') {
            return response()->json(['message' => 'Document is not in initiation phase.'], 422);
        }

        $targetDeptId = $request->assigned_department_id ?? $document->assigned_department_id;
        if (!$targetDeptId) {
            return response()->json(['message' => 'Target department must be specified.'], 422);
        }

        if ($request->has(['x', 'y', 'page', 'width', 'height'])) {
            if (!$user->signature) {
                return response()->json(['message' => 'Please register your signature in your profile first.'], 422);
            }
            $burned = $this->burnSignatureIntoPdf(
                $document->file_path,
                $user->signature,
                $request->x,
                $request->y,
                $request->width,
                $request->height,
                $request->page
            );
            if (!$burned) {
                return response()->json(['message' => 'Failed to apply signature to PDF.'], 500);
            }
        }

        // Fetch the assigned department details and build verification PDF
        $dept = Department::findOrFail($targetDeptId);
        $signaturePath = $user->signature ? storage_path('app/public/' . $user->signature) : null;

        $pdfData = [
            'date' => now()->format('F j, Y, g:i a'),
            'department' => $dept->name,
            'signature_path' => ($signaturePath && file_exists($signaturePath)) ? $signaturePath : null,
        ];

        $fileName = null;
        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.verification', $pdfData);
            $fileName = 'directives/directive_' . $document->id . '_' . time() . '.pdf';
            if (!Storage::disk('public')->exists('directives')) {
                Storage::disk('public')->makeDirectory('directives');
            }
            Storage::disk('public')->put($fileName, $pdf->output());
        } catch (\Exception $e) {
            Log::error('DomPDF directive generation failed: ' . $e->getMessage());
        }

        // AUTO-DISPATCH: Directly transitions to dg_directed (bypasses manual file_dept dispatch)
        $document->update([
            'assigned_department_id' => $targetDeptId,
            'directive_file_path' => $fileName,
            'dg_note' => $request->dg_note,
            'status' => 'dg_directed',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'action' => 'assigned',
            'notes' => 'DG endorsed & auto-dispatched file directly to Department: ' . $dept->name . '. Executive Note: ' . ($request->dg_note ?? 'None')
        ]);

        $document->load(['uploader:id,name', 'department:id,name']);

        return response()->json([
            'message' => 'Document endorsed and auto-dispatched to department inbox successfully!',
            'document' => $document
        ], 200);
    }

    // ==========================================
    // PHASE 3: COMPATIBILITY DISPATCH (OPTIONAL)
    // ==========================================

    /**
     * 3. DISPATCH DOCUMENT (Preserved for backward compatibility)
     */
    public function dispatch(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'file_dept') {
            return response()->json(['message' => 'Unauthorized. Only File Department can dispatch.'], 403);
        }

        $request->validate([
            'additional_comment' => 'nullable|string|max:500',
            'x' => 'nullable|numeric',
            'y' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'page' => 'nullable|integer',
        ]);

        $document = Document::findOrFail($id);

        if ($document->status !== 'pending_dispatch') {
            return response()->json(['message' => 'This document is not awaiting dispatch.'], 422);
        }

        if ($request->has(['x', 'y', 'page', 'width', 'height'])) {
            if (!$user->signature) {
                return response()->json(['message' => 'Please register your signature in your profile first.'], 422);
            }
            $burned = $this->burnSignatureIntoPdf(
                $document->file_path,
                $user->signature,
                $request->x,
                $request->y,
                $request->width,
                $request->height,
                $request->page
            );
            if (!$burned) {
                return response()->json(['message' => 'Failed to apply signature to PDF.'], 500);
            }
        }

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

    // ====================================================
    // PHASE 4: TASK EXECUTION & REPORT (STAFF / DEPT)
    // ====================================================

    /**
     * 4. UPLOAD ACTION REPORT
     * Supports:
     * - Option A (Standard): Routes to VDG (pending_vdg_approval)
     * - Option B (Urgent): Bypasses VDG straight to DG (pending_dg_approval)
     */
    public function uploadReport(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'department' && $user->role !== 'staff') {
            return response()->json(['message' => 'Unauthorized. Only Department Staff can upload the action report.'], 403);
        }

        $request->validate([
            'report_file' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'is_urgent' => 'nullable|boolean',
            'urgent_reason' => 'required_if:is_urgent,true,1|nullable|string|max:500',
        ]);

        $document = Document::findOrFail($id);

        if ($document->assigned_department_id !== $user->department_id) {
            return response()->json(['message' => 'Access Denied. This belongs to another department.'], 403);
        }

        if ($document->status !== 'dg_directed') {
            return response()->json(['message' => 'Document is not in a processable state.'], 422);
        }

        $reportPath = $request->file('report_file')->store('reports', 'public');
        $isUrgent = filter_var($request->input('is_urgent'), FILTER_VALIDATE_BOOLEAN);
        $urgentReason = $isUrgent ? $request->input('urgent_reason') : null;

        // Dynamic Branching:
        // Option B (Urgent) -> pending_dg_approval
        // Option A (Standard) -> pending_vdg_approval
        $targetStatus = $isUrgent ? 'pending_dg_approval' : 'pending_vdg_approval';

        $document->update([
            'status' => $targetStatus,
            'report_path' => $reportPath,
            'is_urgent' => $isUrgent,
            'urgent_reason' => $urgentReason,
            'return_reason' => null,
            'returned_by_role' => null,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'action' => 'report_submitted',
            'notes' => $isUrgent
                ? 'URGENT BYPASS: Fast-tracked directly to Director General (bypassed VDG review). Reason: ' . $urgentReason
                : 'Department staff completed execution and uploaded action report to VDG.'
        ]);

        $document->load(['uploader:id,name', 'department:id,name']);

        return response()->json([
            'message' => $isUrgent
                ? 'Urgent action report uploaded! Fast-tracked directly to the Director General.'
                : 'Action report uploaded successfully. Sent to VDG for verification.',
            'document' => $document
        ], 200);
    }

    // ==========================================
    // PHASE 5: SUPERVISORY REVIEW (VDG)
    // ==========================================

    /**
     * 5. VDG SIGN OFF
     * Signs the report and forwards it upward to the Director General (pending_dg_approval).
     */
    public function vdgSign(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'vdg') {
            return response()->json(['message' => 'Unauthorized. Only VDG can sign.'], 403);
        }

        $document = Document::findOrFail($id);

        if ($document->status !== 'pending_vdg_approval') {
            return response()->json(['message' => 'No report found awaiting VDG signature.'], 422);
        }

        if (!$document->report_path) {
            return response()->json(['message' => 'No report file attached to sign.'], 422);
        }

        if (!$user->signature) {
            return response()->json(['message' => 'Please register your signature in your profile first.'], 422);
        }

        $document->update([
            'status' => 'pending_dg_approval',
            'return_reason' => null,
            'returned_by_role' => null,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'action' => 'vdg_signed',
            'notes' => 'Vice Director General signed off on the report. Routed upwards to the DG.'
        ]);

        $document->load(['uploader:id,name', 'department:id,name']);

        return response()->json([
            'message' => 'Document signed by VDG. Routed to the Director General.',
            'document' => $document
        ], 200);
    }

    /**
     * 5b. RETURN FOR REVISION PIPELINE (VDG & DG)
     * - VDG: Returns report back 1 step to Department Staff (status reverts to dg_directed).
     * - DG:
     *    - If urgent bypass (VDG bypassed): returns report back 1 step to Staff (status reverts to dg_directed).
     *    - If standard flow (VDG participated): returns document back 1 step to VDG (status reverts to pending_vdg_approval).
     */
    public function reject(Request $request, $id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['vdg', 'dg'])) {
            return response()->json(['message' => 'Unauthorized. Only VDG or DG can return documents for revision.'], 403);
        }

        $request->validate(['notes' => 'required|string|max:500']);
        $document = Document::findOrFail($id);

        if ($user->role === 'vdg') {
            if ($document->status !== 'pending_vdg_approval') {
                return response()->json(['message' => 'Document is not in VDG review stage.'], 422);
            }

            $document->update([
                'status' => 'dg_directed',
                'return_reason' => $request->notes,
                'returned_by_role' => 'vdg',
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'document_id' => $document->id,
                'action' => 'returned',
                'notes' => 'RETURNED FOR REVISION BY VDG: ' . $request->notes
            ]);

            $document->load(['uploader:id,name', 'department:id,name']);

            return response()->json([
                'message' => 'Document returned to department staff for revision.',
                'document' => $document
            ], 200);
        }

        if ($user->role === 'dg') {
            if ($document->status !== 'pending_dg_approval') {
                return response()->json(['message' => 'Document is not in DG final approval stage.'], 422);
            }

            // Check if VDG was bypassed (urgent fast-track)
            $vdgLog = AuditLog::where('document_id', $document->id)
                ->where('action', 'vdg_signed')
                ->first();
            $bypassedVdg = ($document->is_urgent && !$vdgLog);

            if ($bypassedVdg) {
                // Return 1 step back to Staff
                $targetStatus = 'dg_directed';
                $targetMessage = 'Document returned to department staff for revision (VDG review was bypassed).';
            } else {
                // Return 1 step back to VDG
                $targetStatus = 'pending_vdg_approval';
                $targetMessage = 'Document returned to Vice Director General for supervisory revision.';
            }

            $document->update([
                'status' => $targetStatus,
                'return_reason' => $request->notes,
                'returned_by_role' => 'dg',
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'document_id' => $document->id,
                'action' => 'returned',
                'notes' => 'RETURNED FOR REVISION BY DG: ' . $request->notes
            ]);

            $document->load(['uploader:id,name', 'department:id,name']);

            return response()->json([
                'message' => $targetMessage,
                'document' => $document
            ], 200);
        }
    }

    // ====================================================
    // PHASE 6 & 7: FINAL EXECUTIVE SIGN & ARCHIVING
    // ====================================================

    /**
     * 6. DG FINAL SIGN
     * Executive validation for both VDG-endorsed files and Urgent bypass files.
     */
    public function dgFinalSign(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'dg') {
            return response()->json(['message' => 'Unauthorized. Only DG can give final approval.'], 403);
        }

        $document = Document::findOrFail($id);

        if ($document->status !== 'pending_dg_approval') {
            return response()->json(['message' => 'Document is not awaiting final executive sign-off.'], 422);
        }

        if (!$document->report_path) {
            return response()->json(['message' => 'No report file attached to sign.'], 422);
        }

        if (!$user->signature) {
            return response()->json(['message' => 'Please register your signature in your profile first.'], 422);
        }

        $vdgLog = AuditLog::where('document_id', $document->id)
            ->where('action', 'vdg_signed')
            ->first();
        $bypassedVdg = ($document->is_urgent && !$vdgLog);

        $document->update([
            'status' => 'dg_signed'
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'document_id' => $document->id,
            'action' => 'dg_signed',
            'notes' => 'Director General gave final executive signature validation.' . ($bypassedVdg ? ' (Fast-tracked Urgent)' : '')
        ]);

        $document->load(['uploader:id,name', 'department:id,name']);

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

        $document->load(['uploader:id,name', 'department:id,name']);

        return response()->json([
            'message' => 'Document successfully locked and archived permanently!',
            'document' => $document
        ], 200);
    }

    /**
     * Detailed profile view with full history and audit trails.
     */
    public function show($id)
    {
        $user = Auth::user();
        $document = Document::with(['uploader:id,name', 'department:id,name', 'auditLogs.user:id,name'])->findOrFail($id);

        if (in_array($user->role, ['staff', 'department'])) {
            if ($document->assigned_department_id !== $user->department_id) {
                return response()->json(['message' => 'Access Denied.'], 403);
            }
        }

        // VDG has full access to all documents belonging to their department
        if ($user->role === 'vdg') {
            if ($document->assigned_department_id !== $user->department_id) {
                return response()->json(['message' => 'Access Denied.'], 403);
            }
        }

        return response()->json($document, 200);
    }

    // ==========================================
    // SEARCH & ARCHIVE VISIBILITY
    // ==========================================

    public function searchArchive(Request $request)
    {
        $user = Auth::user();
        $query = Document::where('status', 'completed_archive');

        if (in_array($user->role, ['vdg', 'staff', 'department'])) {
            $query->where('assigned_department_id', $user->department_id);
        }

        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('control_no', 'LIKE', '%' . $searchTerm . '%');
            });
        }

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

    // ==========================================
    // CORE VISIBILITY SYSTEMS (FEEDS & INBOXES)
    // ==========================================

    /**
     * THE SMART URGENT FEED
     * Dynamically prioritizes items requiring immediate action by role.
     */
    public function urgentFeed()
    {
        $user = Auth::user();
        $query = Document::query();

        switch ($user->role) {
            case 'dg':
                // DG handles:
                // 1. Initial endorsements (pending_dg_init)
                // 2. Final approvals (pending_dg_approval) - Urgent bypass files sorted to top
                $query->whereIn('status', ['pending_dg_init', 'pending_dg_approval'])
                      ->orderBy('is_urgent', 'desc')
                      ->orderBy('created_at', 'asc');
                break;

            case 'file_dept':
                // File dept handles finalized files awaiting archival
                $query->whereIn('status', ['dg_signed'])
                      ->orderBy('updated_at', 'desc');
                break;

            case 'department':
            case 'staff':
                // Staff needs to work on directed files
                $query->where('assigned_department_id', $user->department_id)
                      ->where('status', 'dg_directed')
                      ->orderBy('created_at', 'asc');
                break;

            case 'vdg':
                // VDG handles pending reviews
                // AND has situational awareness of urgent bypass files in their department
                $query->where('assigned_department_id', $user->department_id)
                      ->where(function($q) {
                          $q->where('status', 'pending_vdg_approval')
                            ->orWhere(function($sub) {
                                $sub->where('status', 'pending_dg_approval')
                                    ->where('is_urgent', true);
                            });
                      })
                      ->orderBy('is_urgent', 'desc')
                      ->orderBy('created_at', 'asc');
                break;
        }

        $documents = $query->with(['uploader:id,name', 'department:id,name'])->get();

        return response()->json([
            'role' => $user->role,
            'urgent_count' => $documents->count(),
            'documents' => $documents
        ]);
    }

    /**
     * DEPARTMENT INBOX
     * Active files currently undergoing processing within the department.
     */
    public function departmentInbox(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['vdg', 'department', 'staff']) || !$user->department_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $documents = Document::where('assigned_department_id', $user->department_id)
            ->whereIn('status', ['dg_directed', 'pending_vdg_approval', 'pending_dg_approval'])
            ->with(['uploader:id,name', 'department:id,name'])
            ->orderBy('is_urgent', 'desc')
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

    // ==========================================
    // PDF ENGINE & STREAMING HELPERS
    // ==========================================

    private function resolveAbsolutePath($filePath)
    {
        if (!$filePath) return null;

        clearstatcache();

        $path = storage_path('app/public/' . ltrim($filePath, '/\\'));
        if (file_exists($path)) return $path;

        try {
            $diskPath = Storage::disk('public')->path($filePath);
            if (file_exists($diskPath)) return $diskPath;
        } catch (\Exception $e) {}

        $cleaned = preg_replace('#^(public/|storage/|app/public/)#', '', ltrim($filePath, '/\\'));
        $cleanedPath = storage_path('app/public/' . $cleaned);
        if (file_exists($cleanedPath)) return $cleanedPath;

        $appPath = storage_path('app/' . ltrim($filePath, '/\\'));
        if (file_exists($appPath)) return $appPath;

        return null;
    }

    /**
     * Streams document file (or merged archive if status is completed_archive).
     */
    public function downloadFile($id)
    {
        $document = Document::findOrFail($id);

        if ($document->status === 'completed_archive') {
            return $this->downloadMergedArchivePdf($document);
        }

        $absolutePath = $this->resolveAbsolutePath($document->file_path);

        if (!$absolutePath || !file_exists($absolutePath)) {
            return response()->json(['message' => 'Original file not found on server storage.'], 404);
        }

        $mimeType = mime_content_type($absolutePath) ?: 'application/pdf';

        return response(file_get_contents($absolutePath), 200, [
            'Content-Type'      => $mimeType,
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'Pragma'            => 'no-cache',
            'Expires'           => '0',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function downloadMergedArchivePdf($document)
    {
        $pdfFiles = [];

        // 1. Verification Slip (Directive)
        if ($document->directive_file_path) {
            $path = $this->resolveAbsolutePath($document->directive_file_path);
            if ($path && file_exists($path)) {
                $pdfFiles[] = $path;
            }
        }

        // 2. Original Document
        if ($document->file_path) {
            $path = $this->resolveAbsolutePath($document->file_path);
            if ($path && file_exists($path)) {
                $pdfFiles[] = $path;
            }
        }

        // 3. Action Report (Includes appended signature pages)
        if ($document->report_path) {
            $path = $this->resolveAbsolutePath($document->report_path);
            if ($path && file_exists($path)) {
                $pdfFiles[] = $path;
            }
        }

        if (empty($pdfFiles)) {
            return response()->json(['message' => 'No files found to merge.'], 404);
        }

        try {
            $newPdf = new \setasign\Fpdi\Fpdi();

            foreach ($pdfFiles as $filePath) {
                if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'pdf') {
                    continue;
                }

                $pageCount = $newPdf->setSourceFile($filePath);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $newPdf->setSourceFile($filePath);
                    $size = $newPdf->getTemplateSize($newPdf->importPage($pageNo));
                    $newPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $templateId = $newPdf->importPage($pageNo);
                    $newPdf->useTemplate($templateId);
                }
            }

            $tempMergedPath = storage_path('app/temp_merged_' . $document->id . '_' . time() . '.pdf');
            $newPdf->Output($tempMergedPath, 'F');

            $mimeType = 'application/pdf';
            $content = file_get_contents($tempMergedPath);
            if (file_exists($tempMergedPath)) {
                unlink($tempMergedPath);
            }

            return response($content, 200, [
                'Content-Type'      => $mimeType,
                'Cache-Control'     => 'no-cache, no-store, must-revalidate',
                'Pragma'            => 'no-cache',
                'Expires'           => '0',
                'X-Accel-Buffering' => 'no',
                'Content-Disposition' => 'attachment; filename="archived_document_' . $document->control_no . '.pdf"',
            ]);

        } catch (\Exception $e) {
            Log::error('Archived PDF merge download failed: ' . $e->getMessage());
            if (isset($tempMergedPath) && file_exists($tempMergedPath)) {
                unlink($tempMergedPath);
            }
            return response()->json(['message' => 'Failed to generate merged archive PDF: ' . $e->getMessage()], 500);
        }
    }

    public function downloadReportFile($id)
    {
        $document = Document::findOrFail($id);

        if (!$document->report_path) {
            return response()->json(['message' => 'No action report attached to this document yet.'], 404);
        }

        $absolutePath = $this->resolveAbsolutePath($document->report_path);

        if (!$absolutePath || !file_exists($absolutePath)) {
            return response()->json(['message' => 'Report file not found on server storage.'], 404);
        }

        $mimeType = mime_content_type($absolutePath) ?: 'application/pdf';

        return response(file_get_contents($absolutePath), 200, [
            'Content-Type'      => $mimeType,
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'Pragma'            => 'no-cache',
            'Expires'           => '0',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function downloadDirectiveFile($id)
    {
        $document = Document::findOrFail($id);

        if (!$document->directive_file_path) {
            return response()->json(['message' => 'No directive file generated for this document.'], 404);
        }

        $absolutePath = $this->resolveAbsolutePath($document->directive_file_path);

        if (!$absolutePath || !file_exists($absolutePath)) {
            return response()->json(['message' => 'Directive file not found on server storage.'], 404);
        }

        $mimeType = mime_content_type($absolutePath) ?: 'application/pdf';

        return response(file_get_contents($absolutePath), 200, [
            'Content-Type'      => $mimeType,
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'Pragma'            => 'no-cache',
            'Expires'           => '0',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Download / View VDG Clearance Signature Slip (Standalone 1-Page PDF)
     */
    public function downloadVdgSignFile($id)
    {
        $document = Document::with('department')->findOrFail($id);

        if ($document->is_urgent) {
            return response()->json(['message' => 'VDG review was bypassed for this urgent document.'], 404);
        }

        $vdgLog = AuditLog::where('document_id', $document->id)
            ->where('action', 'vdg_signed')
            ->first();

        if (!$vdgLog && !in_array($document->status, ['pending_dg_approval', 'dg_signed', 'completed_archive'])) {
            return response()->json(['message' => 'Document has not received VDG sign-off yet.'], 404);
        }

        $vdgUser = $vdgLog ? User::find($vdgLog->user_id) : null;
        $vdgSignedAt = $vdgLog ? $vdgLog->created_at : null;

        $pdfData = [
            'document' => $document,
            'bypassed_vdg' => false,
            'urgent_reason' => null,
            'vdg_name' => $vdgUser ? $vdgUser->name : 'Vice Director General',
            'vdg_signature_path' => ($vdgUser && $vdgUser->signature && file_exists(storage_path('app/public/' . $vdgUser->signature))) 
                ? storage_path('app/public/' . $vdgUser->signature) 
                : null,
            'vdg_signed_at' => $vdgSignedAt ? $vdgSignedAt->format('F j, Y, g:i a') : now()->format('F j, Y, g:i a'),
            'dg_name' => null,
            'dg_signature_path' => null,
            'dg_signed_at' => null,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report_signature', $pdfData);
        $output = $pdf->output();

        return response($output, 200, [
            'Content-Type'      => 'application/pdf',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'Pragma'            => 'no-cache',
            'Expires'           => '0',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Download / View DG Final Sign-off Certificate (Standalone 1-Page PDF)
     */
    public function downloadFinalSignFile($id)
    {
        $document = Document::with('department')->findOrFail($id);

        if (!in_array($document->status, ['dg_signed', 'completed_archive'])) {
            return response()->json(['message' => 'Document has not received final DG approval yet.'], 404);
        }

        $vdgLog = AuditLog::where('document_id', $document->id)
            ->where('action', 'vdg_signed')
            ->first();
        $vdgUser = $vdgLog ? User::find($vdgLog->user_id) : null;
        $vdgSignedAt = $vdgLog ? $vdgLog->created_at : null;
        $bypassedVdg = ($document->is_urgent && !$vdgLog);

        $dgLog = AuditLog::where('document_id', $document->id)
            ->where('action', 'dg_signed')
            ->first();
        $dgUser = $dgLog ? User::find($dgLog->user_id) : Auth::user();
        $dgSignedAt = $dgLog ? $dgLog->created_at : now();

        $pdfData = [
            'document' => $document,
            'bypassed_vdg' => $bypassedVdg,
            'urgent_reason' => $document->urgent_reason,
            'vdg_name' => $vdgUser ? $vdgUser->name : null,
            'vdg_signature_path' => ($vdgUser && $vdgUser->signature && file_exists(storage_path('app/public/' . $vdgUser->signature))) 
                ? storage_path('app/public/' . $vdgUser->signature) 
                : null,
            'vdg_signed_at' => $vdgSignedAt ? $vdgSignedAt->format('F j, Y, g:i a') : null,
            'dg_name' => $dgUser ? $dgUser->name : 'Director General',
            'dg_signature_path' => ($dgUser && $dgUser->signature && file_exists(storage_path('app/public/' . $dgUser->signature))) 
                ? storage_path('app/public/' . $dgUser->signature) 
                : null,
            'dg_signed_at' => $dgSignedAt ? $dgSignedAt->format('F j, Y, g:i a') : now()->format('F j, Y, g:i a'),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report_signature', $pdfData);
        $output = $pdf->output();

        return response($output, 200, [
            'Content-Type'      => 'application/pdf',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'Pragma'            => 'no-cache',
            'Expires'           => '0',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function burnSignatureIntoPdf($filePath, $signaturePath, $x, $y, $width, $height, $page)
    {
        $absoluteFilePath = $this->resolveAbsolutePath($filePath);
        $absoluteSigPath = $this->resolveAbsolutePath($signaturePath);

        if (!$absoluteFilePath || !$absoluteSigPath || !file_exists($absoluteFilePath) || !file_exists($absoluteSigPath)) {
            return false;
        }

        if (strtolower(pathinfo($absoluteFilePath, PATHINFO_EXTENSION)) !== 'pdf') {
            return false;
        }

        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            $pageCount = $pdf->setSourceFile($absoluteFilePath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $size = $pdf->getTemplateSize($pdf->importPage($pageNo));
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                
                $templateId = $pdf->importPage($pageNo);
                $pdf->useTemplate($templateId);

                if ($pageNo == $page) {
                    $pdf->Image($absoluteSigPath, $x, $y, $width, $height);
                }
            }

            $pdf->Output($absoluteFilePath, 'F');
            return true;
        } catch (\Exception $e) {
            Log::error('PDF signature burn failed: ' . $e->getMessage());
            return false;
        }
    }

    private function appendSignaturePage($document, $vdgUser, $vdgSignedAt, $dgUser, $dgSignedAt, $bypassedVdg = false)
    {
        $absoluteFilePath = $this->resolveAbsolutePath($document->report_path);
        if (!$absoluteFilePath || !file_exists($absoluteFilePath)) {
            return false;
        }

        $pdfData = [
            'document' => $document,
            'bypassed_vdg' => $bypassedVdg,
            'urgent_reason' => $document->urgent_reason,
            'vdg_name' => $vdgUser ? $vdgUser->name : null,
            'vdg_signature_path' => ($vdgUser && $vdgUser->signature && file_exists(storage_path('app/public/' . $vdgUser->signature))) 
                ? storage_path('app/public/' . $vdgUser->signature) 
                : null,
            'vdg_signed_at' => $vdgSignedAt ? $vdgSignedAt->format('F j, Y, g:i a') : null,
            'dg_name' => $dgUser ? $dgUser->name : null,
            'dg_signature_path' => ($dgUser && $dgUser->signature && file_exists(storage_path('app/public/' . $dgUser->signature))) 
                ? storage_path('app/public/' . $dgUser->signature) 
                : null,
            'dg_signed_at' => $dgSignedAt ? $dgSignedAt->format('F j, Y, g:i a') : null,
        ];

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report_signature', $pdfData);
            $signaturePageOutput = $pdf->output();
            
            $tempPath = storage_path('app/temp_sig_' . $document->id . '_' . time() . '.pdf');
            file_put_contents($tempPath, $signaturePageOutput);

            $fpdi = new \setasign\Fpdi\Fpdi();
            $pageCount = $fpdi->setSourceFile($absoluteFilePath);

            // If replacing previous single VDG signature page, copy all pages except last
            $pagesToCopy = $pageCount;
            if ($document->status === 'pending_dg_approval' && !$bypassedVdg) {
                $pagesToCopy = max(1, $pageCount - 1);
            }

            $newPdf = new \setasign\Fpdi\Fpdi();

            for ($pageNo = 1; $pageNo <= $pagesToCopy; $pageNo++) {
                $newPdf->setSourceFile($absoluteFilePath);
                $size = $newPdf->getTemplateSize($newPdf->importPage($pageNo));
                $newPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $templateId = $newPdf->importPage($pageNo);
                $newPdf->useTemplate($templateId);
            }

            $newPdf->setSourceFile($tempPath);
            $size = $newPdf->getTemplateSize($newPdf->importPage(1));
            $newPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $templateId = $newPdf->importPage(1);
            $newPdf->useTemplate($templateId);

            $newPdf->Output($absoluteFilePath, 'F');

            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to append signature page: ' . $e->getMessage());
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
            return false;
        }
    }
}
