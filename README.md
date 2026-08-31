# 🏛️ CADT Digital Document Workflow & Tracking System (Doc-API)
> **Academic Project — CADT Sarona Final Assignment**  
> *A Secure, Role-Based Governmental Document Routing, Approval, and Digital Signing Platform.*

---

## 📌 1. Project Overview & Problem Statement

### 🎯 Objective
In traditional ministerial and organizational administration, paper documents suffer from:
- ⏳ **Slow physical dispatch and routing bottlenecks**
- 🔍 **Lack of real-time visibility** into who currently holds a pending document
- ⚠️ **High risk of document loss or tampering**
- ❌ **No auditable log** of when decisions and signatures were executed

**Doc-API** solves this by establishing a strict, **7-Phase State Machine** that mirrors official administrative governance workflows (File Entry Desk -> Director General -> Dispatch -> Line Departments -> Vice Director General -> Final Executive Sign-off -> Vault Archiving).

### 🛠️ Core Capabilities
- **Strict Role-Based Access Control (RBAC):** Multi-tiered hierarchy (`file_dept`, `dg`, `vdg`, `department`, `staff`).
- **Dynamic PDF Digital Stamping & Watermarking:** Merges signatures, directive sheets, and action reports via FPDI & DomPDF.
- **Bi-Directional Rejection & Correction Pipeline:** Allows supervisors to reject substandard reports back to staff.
- **Immutable Audit Logging:** Every transition is recorded with actor ID, timestamp, and notes.
- **Omni-Channel Client Ready:** Tailored for Flutter Mobile frontend and web administrative portals.

---

## 🏛️ 2. Organizational Hierarchy & Role Matrix

```mermaid
graph TD
    classDef executive fill:#1e3a8a,stroke:#3b82f6,stroke-width:2px,color:#fff;
    classDef operational fill:#065f46,stroke:#10b981,stroke-width:2px,color:#fff;
    classDef entry fill:#7c2d12,stroke:#f97316,stroke-width:2px,color:#fff;

    DG["👔 Director General (DG)<br/><i>Executive Decisions & Final Sign-off</i>"]:::executive
    VDG["📑 Vice Director General (VDG)<br/><i>Supervision, Verification & Review</i>"]:::executive
    FD["📬 File Department (file_dept)<br/><i>Intake Desk, Dispatch & Permanent Archival</i>"]:::entry
    DEPT["🏢 Department Lead / Staff (department/staff)<br/><i>Action Execution & Report Drafting</i>"]:::operational

    FD -->|1. Uploads Incoming Doc| DG
    DG -->|2. Issues Directive| FD
    FD -->|3. Dispatches Document| DEPT
    DEPT -->|4. Submits Report| VDG
    VDG -->|5. Approves & Signs| DG
    VDG -.->|5b. Rejects for Revision| DEPT
    DG -->|6. Executive Final Sign| FD
    FD -->|7. Seals in Permanent Vault| FD
```

### Role Permissions Matrix

| Role | Upload New Doc | Assign Department | Dispatch | Submit Action Report | VDG Review & Sign | DG Final Sign | Rejection Gate | Permanent Archive |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| `file_dept` | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| `dg` | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| `department` / `staff` | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `vdg` | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ | ❌ |

---

## 🔄 3. End-to-End Workflow & State Machine

```mermaid
stateDiagram-v2
    [*] --> pending_dg_init: 1. File Dept uploads document with Control No (DOC-YYYYMMDD-XXXX)
    
    pending_dg_init --> pending_dispatch: 2. DG assigns Target Department & attaches Directive PDF
    
    pending_dispatch --> dg_directed: 3. File Dept reviews and officially dispatches to Department Inbox
    
    dg_directed --> pending_vdg_approval: 4. Department Staff finishes assignment & uploads Action Report
    
    state VDG_Review_Stage {
        pending_vdg_approval --> dg_directed: ⚠️ Rejection: VDG sends back to Staff for revisions
        pending_vdg_approval --> pending_dg_approval: 5. VDG signs & stamps verification page
    }
    
    pending_dg_approval --> dg_signed: 6. DG applies final executive digital signature
    
    dg_signed --> completed_archive: 7. File Dept locks document & merges all PDFs into permanent archive
    
    completed_archive --> [*]: Lifecycle Closed & Audited
```

---

## ⚡ 4. Detailed Sequence Flow Diagram

```mermaid
sequenceDiagram
    autonumber
    actor FD as 📬 File Dept
    actor DG as 👔 Director General
    actor Staff as 🏢 Dept Staff
    actor VDG as 📑 Vice DG
    participant API as ⚙️ Laravel Backend
    participant Storage as 🗄️ Storage & PDF Engine
    participant DB as 🗃️ Database (MySQL)

    Note over FD,API: Phase 1: Ingestion
    FD->>API: POST /documents (Upload PDF, title, control_no)
    API->>Storage: Store original document PDF
    API->>DB: Insert Document (status: pending_dg_init) + AuditLog
    
    Note over DG,API: Phase 2: Directive Assignment
    DG->>API: POST /documents/{id}/direct (assigned_department_id, dg_note, sig coords)
    API->>Storage: Generate Directive PDF (DomPDF) & burn signature
    API->>DB: Update status to pending_dispatch + AuditLog
    
    Note over FD,API: Phase 3: Official Dispatch
    FD->>API: POST /documents/{id}/dispatch (additional_comment)
    API->>DB: Update status to dg_directed + AuditLog
    
    Note over Staff,API: Phase 4: Action Report Upload
    Staff->>API: POST /documents/{id}/report (Upload report_file)
    API->>Storage: Store report PDF
    API->>DB: Update status to pending_vdg_approval + AuditLog
    
    Note over VDG,API: Phase 5: Supervisory Review
    alt Substandard Report (Reject)
        VDG->>API: POST /documents/{id}/reject (rejection reason)
        API->>DB: Revert status to dg_directed + AuditLog (REJECTED BY VDG)
    else Approved & Signed
        VDG->>API: POST /documents/{id}/vdg-sign
        API->>Storage: Append VDG signature page onto Report PDF
        API->>DB: Update status to pending_dg_approval + AuditLog
    end

    Note over DG,API: Phase 6: Final Executive Sign-off
    DG->>API: POST /documents/{id}/dg-sign
    API->>Storage: Append DG signature onto Report PDF
    API->>DB: Update status to dg_signed + AuditLog
    
    Note over FD,API: Phase 7: Permanent Archiving
    FD->>API: POST /documents/{id}/archive
    API->>DB: Update status to completed_archive + AuditLog
    FD->>API: GET /documents/{id}/download (Merged original + directive + signed report)
```

---

## 🗄️ 5. Database Schema & Entity Relationship Diagram (ERD)

```mermaid
erDiagram
    DEPARTMENTS ||--o{ USERS : "employs"
    DEPARTMENTS ||--o{ DOCUMENTS : "assigned_to"
    USERS ||--o{ DOCUMENTS : "uploaded_by"
    USERS ||--o{ AUDIT_LOGS : "performed_by"
    DOCUMENTS ||--o{ AUDIT_LOGS : "tracks"

    DEPARTMENTS {
        bigint id PK
        string name "Department Name"
        string code "Unique Code (e.g. IT, FIN)"
        timestamp created_at
    }

    USERS {
        bigint id PK
        bigint department_id FK "nullable for DG/FileDept"
        string name
        string email UK
        enum role "file_dept, dg, vdg, department, staff"
        string avatar "Profile photo path"
        string signature "Digital signature PNG path"
        timestamp created_at
    }

    DOCUMENTS {
        bigint id PK
        bigint uploaded_by_user_id FK
        bigint assigned_department_id FK
        string control_no UK "Format: DOC-YYYYMMDD-XXXX"
        string title
        string file_path "Original document PDF"
        string directive_file_path "Generated DG directive PDF"
        string report_path "Staff action report PDF"
        text file_dept_comment
        enum status "pending_dg_init, pending_dispatch, dg_directed, pending_vdg_approval, pending_dg_approval, dg_signed, completed_archive"
        timestamp created_at
        timestamp updated_at
    }

    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK
        bigint document_id FK
        string action "created, assigned, dispatched, report_submitted, vdg_signed, dg_signed, archived"
        text notes "Detailed explanation of action"
        timestamp created_at
    }
```

---

## 🚀 6. API Reference Catalog

### 🔐 Authentication & Profile
| Method | URI | Access | Description |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/login` | Public | Authenticates user & returns Sanctum Bearer token |
| `POST` | `/api/logout` | Authenticated | Revokes current session token |
| `GET` | `/api/users` | Authenticated | List all registered users |
| `POST` | `/api/users/{id}/signature` | Authenticated | Upload digital signature PNG for automated document stamping |
| `POST` | `/api/users/{id}/avatar` | Authenticated | Upload user avatar |

---

### 📄 Document Operations Pipeline
| Phase | Method | URI | Permitted Roles | Description |
| :---: | :--- | :--- | :--- | :--- |
| **Phase 1** | `POST` | `/api/documents` | `file_dept` | Ingest initial document (multipart PDF + title + metadata) |
| **Phase 2** | `POST` | `/api/documents/{id}/direct` | `dg` | Assign department, generate Directive PDF, and burn DG stamp |
| **Phase 3** | `POST` | `/api/documents/{id}/dispatch` | `file_dept` | Validate directive & officially route doc to line department |
| **Phase 4** | `POST` | `/api/documents/{id}/report` | `department`, `staff` | Upload completed task action report PDF |
| **Phase 5** | `POST` | `/api/documents/{id}/vdg-sign` | `vdg` | Review and apply VDG signature to action report |
| **Fail-Safe**| `POST` | `/api/documents/{id}/reject` | `vdg` | Reject report back to department with feedback notes |
| **Phase 6** | `POST` | `/api/documents/{id}/dg-sign` | `dg` | Final executive approval and signature endorsement |
| **Phase 7** | `POST` | `/api/documents/{id}/archive` | `file_dept` | Lock lifecycle into permanent immutable archive |

---

### 📊 Visibility Feeds, Search & File Streaming
| Method | URI | Permitted Roles | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/documents/urgent` | All Authenticated | Documents awaiting urgent action based on current user role |
| `GET` | `/api/departments/inbox` | `dept`, `staff`, `vdg` | Department-specific actionable incoming queue |
| `GET` | `/api/documents/archive` | All Authenticated | Global (DG/FileDept) or departmental search across archived records |
| `GET` | `/api/documents/{id}` | Role Authorized | Complete document profile, metadata, and chronological audit trail |
| `GET` | `/api/documents/{id}/download` | Role Authorized | Dynamic merged multi-part archival PDF stream |
| `GET` | `/api/documents/{id}/report/download` | Role Authorized | Streams staff action report file |
| `GET` | `/api/documents/{id}/directive/download` | Role Authorized | Streams generated DG directive sheet |

---

## 💻 7. Tech Stack & Engineering Highlights

```
┌────────────────────────────────────────────────────────┐
│                   FRONTEND CLIENTS                     │
│  Flutter Mobile App (iOS / Android)  │  React Admin UI │
└───────────────────────────┬────────────────────────────┘
                            │ RESTful JSON / HTTPS
┌───────────────────────────▼────────────────────────────┐
│                    LARAVEL 11 BACKEND                  │
│  • Sanctum Token RBAC Middleware                       │
│  • 7-Phase State Machine Controller Engine            │
│  • Event-Sourced Audit Logging Subsystem               │
└───────────────────────────┬────────────────────────────┘
                            │
              ┌─────────────┴─────────────┐
              ▼                           ▼
┌───────────────────────────┐ ┌───────────────────────────┐
│     DATABASE STORAGE      │ │     PDF ENGINE & MERGER   │
│  MySQL / PostgreSQL       │ │  • FPDI Coordinate Burner │
│  • Foreign Key Integrity  │ │  • DomPDF Template Engine │
│  • Indexed Control Nos    │ │  • Dynamic Multi-Doc Join │
└───────────────────────────┘ └───────────────────────────┘
```

- **Framework:** Laravel 11.x (PHP 8.2+)
- **Authentication:** Laravel Sanctum (Bearer Token RBAC)
- **PDF Generation & Manipulation:**
  - `setasign/fpdi` & `setasign/fpdf`: Coordinate-based digital signature embedding directly on PDF bytes.
  - `barryvdh/laravel-dompdf`: Dynamic ministerial directive sheet and multi-signatory certificate generation.
- **Audit Traceability:** Event-driven audit logs recording timestamp, actor role, action type, and contextual notes on every status transition.

---

## 🎓 8. Quick Presentation Pitch for Your Teacher

When presenting this project to your professor / supervisor, you can emphasize the following key points:

1. **Academic & Real-World Value:**  
   *"This project digitizes bureaucratic document workflows, replacing physical paper transit with a secure, 7-phase state machine that mirrors real governmental hierarchies (File Registry -> Director General -> Department Staff -> Vice DG -> Archive)."*
2. **Key Technical Innovations:**  
   - **Custom State Machine Pattern:** Strict route-level and database-level validation preventing illegal status jumps.
   - **Automated Digital Signature & PDF Assembly:** Real-time PDF modification with coordinate signature burning and automated multi-document merging.
   - **Complete Audit Trail:** Strict accountability with immutable logs for every single action.
   - **Supervisor Rejection Loop:** Realistic workflow incorporating feedback and revisions before executive sign-off.
3. **Cross-Platform Readiness:**  
   *"The backend exposes clean, stateless RESTful endpoints consumed by Flutter mobile and web interfaces."*

---

## 🚀 Setup & Local Execution

### Prerequisites
- PHP 8.2+ with `gd`, `fileinfo`, `pdo_mysql` extensions
- Composer 2+
- MySQL or PostgreSQL database

### Installation Steps
```bash
# 1. Clone the repository and navigate into folder
cd doc-api

# 2. Install PHP dependencies
composer install

# 3. Configure environment file
cp .env.example .env
php artisan key:generate

# 4. Run database migrations & seeders
php artisan migrate:fresh --seed

# 5. Create storage symlink for uploaded files
php artisan storage:link

# 6. Start the development server
php artisan serve --port=8000
```

---
*Developed for CADT Mobile Development & Software Engineering — Sarona Project.*
