# Document Management System - Flutter Mobile Frontend API Documentation

## 📱 Project Overview
This is a **Document Workflow Management System** API designed for a governmental/organizational document approval process. The system implements a 7-phase workflow where documents move through different departments and approval stages.

**Base URL:** `http://your-api-domain.com/api`
**Authentication:** Bearer Token (Sanctum)
**API Type:** RESTful JSON

---

## 🔐 Authentication

### 1. **Login Endpoint**
```
POST /api/login
```

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Success Response (200):**
```json
{
  "access_token": "1|Xk7pL8mN9qRsT2uVwXyZ3aBcDeFgHiJkLmNoPqRs",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "file_dept",
    "department_id": null
  }
}
```

**Error Response (401):**
```json
{
  "message": "invalid login credentials"
}
```

**Validation Rules:**
- `email` - required, valid email format
- `password` - required, minimum 1 character

---

### 2. **Logout Endpoint**
```
POST /api/logout
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "message": "successfully logged out"
}
```

---

## 👥 User Roles & Permissions

| Role | Permissions | Department Required |
|------|-------------|-------------------|
| **file_dept** | Upload documents, Dispatch, Archive | No |
| **dg** (Director General) | Initialize documents, Final signature | No |
| **department** | View assigned docs, Upload reports | Yes |
| **staff** | View assigned docs, Upload reports | Yes |
| **vdg** (Vice Director General) | Review & sign reports | Yes |

---

## 📊 Data Models & Database Structure

### **Users Table**
```
- id: Integer (Primary Key)
- department_id: Integer (Foreign Key, nullable)
- name: String (max 255)
- email: String (unique, max 255)
- email_verified_at: Timestamp (nullable)
- role: Enum [file_dept, staff, vdg, dg] (default: staff)
- password: String (hashed)
- remember_token: String (nullable)
- created_at: Timestamp
- updated_at: Timestamp
```

### **Departments Table**
```
- id: Integer (Primary Key)
- name: String (max 255)
- code: String (unique, max 255)
- created_at: Timestamp
- updated_at: Timestamp
```

### **Documents Table**
```
- id: Integer (Primary Key)
- uploaded_by_user_id: Integer (Foreign Key) -> Users
- assigned_department_id: Integer (Foreign Key, nullable) -> Departments
- control_no: String (unique) [Format: DOC-YYYYMMDD-XXXX]
- title: String (max 255)
- file_path: String (path to stored file)
- file_dept_comment: Text (nullable)
- status: Enum [
    pending_dg_init,
    pending_dispatch,
    dg_directed,
    pending_vdg_approval,
    pending_dg_approval,
    dg_signed,
    completed_archive
  ] (default: pending_dg_init)
- created_at: Timestamp
- updated_at: Timestamp
```

### **Audit Logs Table**
```
- id: Integer (Primary Key)
- user_id: Integer (Foreign Key) -> Users
- document_id: Integer (Foreign Key) -> Documents
- action: Enum [uploaded, assigned, dispatched, report_submitted, vdg_signed, dg_signed, archived]
- notes: String (nullable)
- created_at: Timestamp
- updated_at: Timestamp
```

---

## 🔄 Document Workflow (7-Phase State Machine)

```
PHASE 1: Upload (File Dept)
    ↓
PHASE 2: Assign/Direct (DG)
    ↓
PHASE 3: Dispatch (File Dept)
    ↓
PHASE 4: Upload Work (Department/Staff)
    ↓
PHASE 5: VDG Sign (VDG)
    ↓
PHASE 6: DG Final Sign (DG)
    ↓
PHASE 7: Archive (File Dept)
```

### **Status Transitions:**

| Current Status | Next Status | Actor | Endpoint |
|---|---|---|---|
| pending_dg_init | pending_dispatch | DG | POST /documents/{id}/direct |
| pending_dispatch | dg_directed | File Dept | POST /documents/{id}/dispatch |
| dg_directed | pending_vdg_approval | Department/Staff | POST /documents/{id}/report |
| pending_vdg_approval | pending_dg_approval | VDG | POST /documents/{id}/vdg-sign |
| pending_dg_approval | dg_signed | DG | POST /documents/{id}/dg-sign |
| dg_signed | completed_archive | File Dept | POST /documents/{id}/archive |

---

## 📡 API Endpoints

### **1. Upload Document (Phase 1)**
```
POST /api/documents
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Restricted to:** file_dept role only

**Request Body:**
```
- title: String (required, max 255)
- file: File (required, mimes: pdf, doc, docx, max: 10MB)
- comment: String (optional)
```

**Success Response (201):**
```json
{
  "message": "Document uploaded successfully!",
  "document": {
    "id": 1,
    "uploaded_by_user_id": 5,
    "assigned_department_id": null,
    "control_no": "DOC-20260611-ABCD",
    "title": "Budget Report 2026",
    "file_path": "documents/document_file.pdf",
    "file_dept_comment": "Initial scan complete",
    "status": "pending_dg_init",
    "created_at": "2026-06-11T01:09:14Z",
    "updated_at": "2026-06-11T01:09:14Z"
  }
}
```

**Error Response (403):**
```json
{
  "message": "Unauthorized. Only File Department can upload."
}
```

---

### **2. Assign/Direct Document (Phase 2)**
```
POST /api/documents/{id}/direct
Authorization: Bearer {token}
Content-Type: application/json
```

**Restricted to:** dg role only

**Request Body:**
```json
{
  "assigned_department_id": 2,
  "dg_note": "Please prioritize this document"
}
```

**Validation Rules:**
- `assigned_department_id` - required, must exist in departments table
- `dg_note` - optional, max 500 characters

**Success Response (200):**
```json
{
  "message": "Document assigned. Sent back to File Department for final dispatch.",
  "document": {
    "id": 1,
    "uploaded_by_user_id": 5,
    "assigned_department_id": 2,
    "control_no": "DOC-20260611-ABCD",
    "title": "Budget Report 2026",
    "file_path": "documents/document_file.pdf",
    "file_dept_comment": "Initial scan complete",
    "status": "pending_dispatch",
    "uploader": {
      "id": 5,
      "name": "John Doe"
    },
    "department": {
      "id": 2,
      "name": "Finance Department"
    },
    "created_at": "2026-06-11T01:09:14Z",
    "updated_at": "2026-06-11T01:10:00Z"
  }
}
```

**Error Response (422):**
```json
{
  "message": "Document is not in initiation phase."
}
```

---

### **3. Dispatch Document (Phase 3)**
```
POST /api/documents/{id}/dispatch
Authorization: Bearer {token}
Content-Type: application/json
```

**Restricted to:** file_dept role only

**Request Body:**
```json
{
  "additional_comment": "Document verified and ready for department"
}
```

**Validation Rules:**
- `additional_comment` - optional, max 500 characters

**Success Response (200):**
```json
{
  "message": "Document officially dispatched to the target department successfully!",
  "document": {
    "id": 1,
    "status": "dg_directed",
    "file_dept_comment": "Initial scan complete | Dispatch Note: Document verified and ready for department",
    "uploader": {
      "id": 5,
      "name": "John Doe"
    },
    "department": {
      "id": 2,
      "name": "Finance Department"
    },
    "created_at": "2026-06-11T01:09:14Z",
    "updated_at": "2026-06-11T01:11:00Z"
  }
}
```

---

### **4. Upload Report/Action Work (Phase 4)**
```
POST /api/documents/{id}/report
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Restricted to:** department, staff roles only

**Request Body:**
```
- report_file: File (required, mimes: pdf, doc, docx, max: 10MB)
```

**Security Check:**
- User must belong to the same department as the assigned_department_id

**Success Response (200):**
```json
{
  "message": "Action report uploaded successfully. Sent to VDG for verification.",
  "document": {
    "id": 1,
    "status": "pending_vdg_approval",
    "file_path": "reports/report_file.pdf",
    "created_at": "2026-06-11T01:09:14Z",
    "updated_at": "2026-06-11T01:12:00Z"
  }
}
```

**Error Response (403):**
```json
{
  "message": "Access Denied. This belongs to another department."
}
```

---

### **5. VDG Sign Off (Phase 5)**
```
POST /api/documents/{id}/vdg-sign
Authorization: Bearer {token}
```

**Restricted to:** vdg role only

**Request Body:** (empty)

**Security Check:**
- User must belong to the same department as assigned_department_id
- Document status must be pending_vdg_approval

**Success Response (200):**
```json
{
  "message": "Document signed by VDG. Routed to the Director General.",
  "document": {
    "id": 1,
    "status": "pending_dg_approval",
    "created_at": "2026-06-11T01:09:14Z",
    "updated_at": "2026-06-11T01:13:00Z"
  }
}
```

---

### **6. DG Final Sign (Phase 6)**
```
POST /api/documents/{id}/dg-sign
Authorization: Bearer {token}
```

**Restricted to:** dg role only

**Request Body:** (empty)

**Success Response (200):**
```json
{
  "message": "Document officially signed by the DG! Sent to Entry desk for archiving.",
  "document": {
    "id": 1,
    "status": "dg_signed",
    "created_at": "2026-06-11T01:09:14Z",
    "updated_at": "2026-06-11T01:14:00Z"
  }
}
```

---

### **7. Archive Document (Phase 7)**
```
POST /api/documents/{id}/archive
Authorization: Bearer {token}
```

**Restricted to:** file_dept role only

**Request Body:** (empty)

**Success Response (200):**
```json
{
  "message": "Document successfully locked and archived permanently!",
  "document": {
    "id": 1,
    "status": "completed_archive",
    "created_at": "2026-06-11T01:09:14Z",
    "updated_at": "2026-06-11T01:15:00Z"
  }
}
```

---

### **8. Get Urgent Feed (Role-Based)**
```
GET /api/documents/urgent
Authorization: Bearer {token}
```

**Behavior by Role:**

| Role | Documents Returned |
|------|-------------------|
| dg | status in [pending_dg_init, pending_dg_approval] |
| file_dept | status in [pending_dispatch, dg_signed] |
| department, staff | assigned_department_id = user.department_id AND status = dg_directed |
| vdg | assigned_department_id = user.department_id AND status = pending_vdg_approval |

**Success Response (200):**
```json
{
  "role": "dg",
  "urgent_count": 3,
  "documents": [
    {
      "id": 1,
      "control_no": "DOC-20260611-ABCD",
      "title": "Budget Report 2026",
      "status": "pending_dg_init",
      "created_at": "2026-06-11T01:09:14Z"
    }
  ]
}
```

---

### **9. Get Department Inbox**
```
GET /api/departments/inbox
Authorization: Bearer {token}
```

**Restricted to:** vdg, department, staff roles with valid department_id

**Query Parameters:** None

**Success Response (200):**
```json
{
  "user_name": "Jane Smith",
  "role": "vdg",
  "department_id": 2,
  "document_count": 5,
  "documents": [
    {
      "id": 1,
      "control_no": "DOC-20260611-ABCD",
      "title": "Budget Report 2026",
      "status": "dg_directed",
      "uploader": {
        "id": 5,
        "name": "John Doe"
      },
      "created_at": "2026-06-11T01:09:14Z",
      "updated_at": "2026-06-11T01:10:00Z"
    }
  ]
}
```

---

### **10. Search Archive**
```
GET /api/documents/archive?search=Budget
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` - optional, searches in title and control_no fields

**Security Rules:**
- Only returns status = completed_archive
- If user role is vdg, department, or staff: only returns documents where assigned_department_id = user.department_id
- If user role is dg or file_dept: returns all archived documents (global access)

**Success Response (200):**
```json
{
  "user_role": "vdg",
  "access_level": "Department Restricted",
  "result_count": 2,
  "documents": [
    {
      "id": 1,
      "control_no": "DOC-20260611-ABCD",
      "title": "Budget Report 2026",
      "status": "completed_archive",
      "uploader": {
        "id": 5,
        "name": "John Doe"
      },
      "department": {
        "id": 2,
        "name": "Finance Department"
      },
      "created_at": "2026-06-11T01:09:14Z",
      "updated_at": "2026-06-11T01:15:00Z"
    }
  ]
}
```

---

### **11. Download File**
```
GET /api/documents/{id}/download
Authorization: Bearer {token}
```

**Response:**
- Returns the actual file for download
- Content-Type: application/pdf (or appropriate type)
- Includes proper Content-Length header

**Error Response (404):**
```json
{
  "message": "No file attached."
}
```

---

## 🔒 Authentication Header Format

All protected endpoints require this header:
```
Authorization: Bearer {access_token}
```

Example:
```
Authorization: Bearer 1|Xk7pL8mN9qRsT2uVwXyZ3aBcDeFgHiJkLmNoPqRs
```

---

## 📝 Common Error Responses

### **401 Unauthorized**
```json
{
  "message": "Unauthenticated."
}
```

### **403 Forbidden**
```json
{
  "message": "Unauthorized. Only [role] can [action]."
}
```

### **422 Unprocessable Entity**
```json
{
  "message": "This document is not in the correct status to perform this action."
}
```

### **404 Not Found**
```json
{
  "message": "Document not found."
}
```

### **413 Payload Too Large**
```json
{
  "message": "File size exceeds 10MB limit."
}
```

---

## 🧪 Example Flutter API Call (Dart)

### **Login Example:**
```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

Future<void> login(String email, String password) async {
  final response = await http.post(
    Uri.parse('http://your-api-domain.com/api/login'),
    headers: {'Content-Type': 'application/json'},
    body: json.encode({
      'email': email,
      'password': password,
    }),
  );

  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    String token = data['access_token'];
    String userRole = data['user']['role'];
    // Store token in secure storage
    print('Login successful: $token');
  } else {
    print('Login failed: ${response.body}');
  }
}
```

### **Get Urgent Feed Example:**
```dart
Future<void> getUrgentFeed(String token) async {
  final response = await http.get(
    Uri.parse('http://your-api-domain.com/api/documents/urgent'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    List documents = data['documents'];
    print('Found ${data['urgent_count']} urgent documents');
  }
}
```

### **Upload Document Example:**
```dart
Future<void> uploadDocument(String token, String title, String filePath, String comment) async {
  var request = http.MultipartRequest(
    'POST',
    Uri.parse('http://your-api-domain.com/api/documents'),
  );

  request.headers['Authorization'] = 'Bearer $token';
  request.fields['title'] = title;
  request.fields['comment'] = comment;
  request.files.add(await http.MultipartFile.fromPath('file', filePath));

  var response = await request.send();
  if (response.statusCode == 201) {
    print('Document uploaded successfully');
  }
}
```

---

## 📋 Recommended Flutter Package Dependencies

```yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^1.1.0
  dio: ^5.3.0
  provider: ^6.0.0
  flutter_secure_storage: ^9.0.0
  shared_preferences: ^2.1.0
  intl: ^0.18.0
  image_picker: ^1.0.0
  permission_handler: ^11.4.0
  connectivity_plus: ^5.0.0
  file_picker: ^6.0.0
```

---

## 🔑 Key Points for Flutter Frontend Development

1. **Store Access Token Securely:** Use `flutter_secure_storage` instead of SharedPreferences
2. **Token Expiration:** Implement token refresh logic (currently not provided by API, handle accordingly)
3. **File Upload:** Use multipart/form-data for file uploads
4. **Error Handling:** Always check HTTP status codes and handle validation errors
5. **Offline Support:** Implement local caching with Hive or similar
6. **Role-Based UI:** Show different UI elements based on user.role from login response
7. **State Management:** Use Provider or Riverpod for managing auth state and document workflow
8. **Network Security:** Use HTTPS in production
9. **File Download:** Implement proper file handling for downloaded documents

---

## 🎯 Frontend Features to Implement

- **Authentication Screen:** Login with email/password
- **Dashboard:** Role-specific urgent feed
- **Document Upload:** With title, file picker, and optional comment
- **Document List:** Show inbox/urgent documents
- **Document Details:** View document info and audit history (if available)
- **Workflow Actions:** Role-specific buttons for each phase
- **File Viewer/Download:** View or download documents
- **Archive Search:** Search completed documents with filtering
- **Audit Trail:** Show action history for each document
- **Logout:** Clear token and session

---

## 📞 Support & Notes

- **API Base URL:** Configure dynamically for different environments
- **File Storage:** Files are stored in `storage/app/public/`
- **Audit Logs:** All actions are automatically logged for compliance
- **Timestamps:** All dates are in ISO 8601 format (UTC)
- **No Pagination:** Currently not implemented, consider adding if documents exceed 100+
