# Document Management System - Quick Reference Guide for Flutter

## 🚀 Quick Start

### Base Configuration
```
API_BASE_URL = "http://your-api-domain.com/api"
SECURE_STORAGE_KEY = "auth_token"
AUTH_HEADER = "Authorization: Bearer {token}"
```

---

## 📡 All Endpoints at a Glance

| # | Endpoint | Method | Role | Phase |
|---|----------|--------|------|-------|
| 1 | `/login` | POST | Public | Auth |
| 2 | `/logout` | POST | All | Auth |
| 3 | `/documents` | POST | file_dept | Phase 1 |
| 4 | `/documents/{id}/direct` | POST | dg | Phase 2 |
| 5 | `/documents/{id}/dispatch` | POST | file_dept | Phase 3 |
| 6 | `/documents/{id}/report` | POST | dept/staff | Phase 4 |
| 7 | `/documents/{id}/vdg-sign` | POST | vdg | Phase 5 |
| 8 | `/documents/{id}/dg-sign` | POST | dg | Phase 6 |
| 9 | `/documents/{id}/archive` | POST | file_dept | Phase 7 |
| 10 | `/documents/urgent` | GET | All | Dashboard |
| 11 | `/departments/inbox` | GET | dept/staff/vdg | Dashboard |
| 12 | `/documents/archive?search=X` | GET | All | Archive |
| 13 | `/documents/{id}/download` | GET | All | File |

---

## 👤 User Model
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "role": "file_dept|dg|vdg|department|staff",
  "department_id": 2
}
```

---

## 📄 Document Model
```json
{
  "id": 1,
  "uploaded_by_user_id": 5,
  "assigned_department_id": 2,
  "control_no": "DOC-20260611-ABCD",
  "title": "Budget Report",
  "file_path": "documents/file.pdf",
  "file_dept_comment": "Initial scan complete",
  "status": "pending_dg_init",
  "uploader": { "id": 5, "name": "John Doe" },
  "department": { "id": 2, "name": "Finance Dept" },
  "created_at": "2026-06-11T01:09:14Z",
  "updated_at": "2026-06-11T01:09:14Z"
}
```

---

## 🔄 Document Status Workflow

```
pending_dg_init 
    ↓ (DG assigns) [POST /documents/{id}/direct]
pending_dispatch 
    ↓ (File Dept dispatches) [POST /documents/{id}/dispatch]
dg_directed 
    ↓ (Dept/Staff uploads work) [POST /documents/{id}/report]
pending_vdg_approval 
    ↓ (VDG signs) [POST /documents/{id}/vdg-sign]
pending_dg_approval 
    ↓ (DG final signs) [POST /documents/{id}/dg-sign]
dg_signed 
    ↓ (File Dept archives) [POST /documents/{id}/archive]
completed_archive ✓
```

---

## 🎭 Role Permissions Quick Table

### file_dept (File Department)
- ✅ Upload documents
- ✅ Dispatch documents
- ✅ Archive documents
- ✅ View all urgent docs
- ✅ Search all archives

### dg (Director General)
- ✅ Assign/Direct documents
- ✅ Final signature
- ✅ View all urgent docs
- ✅ Search all archives

### vdg (Vice Director General)
- ✅ Review & sign reports
- ✅ View dept urgent docs
- ✅ Search dept archives only

### department / staff
- ✅ Upload action reports
- ✅ View dept documents
- ✅ Search dept archives only

---

## 💾 Request/Response Quick Samples

### Login
```
POST /api/login
{ "email": "user@example.com", "password": "pass123" }
→ { "access_token": "...", "token_type": "Bearer", "user": {...} }
```

### Upload Document
```
POST /api/documents [multipart/form-data]
{ title, file, comment }
→ { message, document }
```

### Assign Document
```
POST /api/documents/{id}/direct
{ "assigned_department_id": 2, "dg_note": "Urgent" }
→ { message, document }
```

### Get Urgent Feed
```
GET /api/documents/urgent [Bearer token]
→ { role, urgent_count, documents[] }
```

### Search Archive
```
GET /api/documents/archive?search=Budget [Bearer token]
→ { user_role, access_level, result_count, documents[] }
```

### Download File
```
GET /api/documents/{id}/download [Bearer token]
→ Binary file stream
```

---

## 🛡️ Security Checklist

- [ ] Use `flutter_secure_storage` for token
- [ ] Always include `Authorization: Bearer {token}` header
- [ ] Handle 401 → redirect to login
- [ ] Handle 403 → show "Access Denied"
- [ ] Validate file size < 10MB before upload
- [ ] Use HTTPS in production
- [ ] Never log sensitive data
- [ ] Clear storage on logout

---

## ⚠️ Common Status Codes & Meanings

| Code | Meaning | Handle |
|------|---------|--------|
| 200 | Success | Use response data |
| 201 | Created | Resource created, use response data |
| 401 | Unauthorized | Redirect to login screen |
| 403 | Forbidden | Show role-based access denied message |
| 404 | Not Found | Show "Document not found" |
| 422 | Invalid State | Show validation error from response.message |
| 413 | File Too Large | Show "File exceeds 10MB limit" |
| 500 | Server Error | Show "Server error, try again later" |

---

## 🎯 UI/UX Implementation Guide

### Screen 1: Login Screen
```
Inputs:
  - Email (TextFormField)
  - Password (TextFormField, obscured)
  - Login Button

On Success:
  - Save token to secure storage
  - Save user data
  - Navigate to Dashboard
  
On Error:
  - Show error message
  - Clear password field
```

### Screen 2: Dashboard (Role-Based)
```
Show based on role:
  - [file_dept] "Documents to Process" + upload button
  - [dg] "Documents Awaiting Approval"
  - [vdg] "Reports to Review"
  - [dept/staff] "Assigned Documents"

Pull urgent feed from GET /api/documents/urgent

Display:
  - Control No
  - Title
  - Status badge
  - Days in system
  - Tap → Document Detail screen
```

### Screen 3: Document Details
```
Display:
  - Document info (title, control_no, status)
  - Upload info (uploader name, date)
  - Department (if assigned)
  - Current comment
  - Audit trail (if available)
  - File download button

Action Buttons (based on status & role):
  - [PENDING_DG_INIT + dg role] → "Assign Department" button
  - [PENDING_DISPATCH + file_dept] → "Dispatch" button
  - [DG_DIRECTED + dept/staff] → "Upload Report" button
  - [PENDING_VDG_APPROVAL + vdg] → "Sign Report" button
  - [PENDING_DG_APPROVAL + dg] → "Final Sign" button
  - [DG_SIGNED + file_dept] → "Archive" button
  - [All] → "Download File" button
```

### Screen 4: Upload/Action Dialogs
```
For Upload Document (file_dept):
  - Title input field
  - File picker (PDF, Doc, Docx)
  - Optional comment
  - Upload button → POST /documents

For Dispatch (file_dept):
  - Optional additional comment
  - Dispatch button → POST /documents/{id}/dispatch

For Assign (dg):
  - Department dropdown
  - Optional note
  - Assign button → POST /documents/{id}/direct

For Upload Report (dept/staff):
  - File picker (PDF, Doc, Docx)
  - Upload button → POST /documents/{id}/report

For Sign (vdg/dg):
  - Confirmation dialog
  - Sign button → POST /documents/{id}/vdg-sign or dg-sign
```

### Screen 5: Archive Search
```
Search field (title/control_no)
Apply Search → GET /api/documents/archive?search=X

Display:
  - Archive count
  - Document list filtered
  - Access level badge (Global/Restricted)
```

---

## 🔧 Dart Helper Functions

### API Call Wrapper
```dart
Future<Map> apiCall(String method, String endpoint, {
  Map<String, String>? body,
  String? token,
  bool isMultipart = false,
}) async {
  String url = 'http://api.domain.com/api$endpoint';
  
  try {
    http.Response response;
    var headers = {'Content-Type': 'application/json'};
    if (token != null) headers['Authorization'] = 'Bearer $token';
    
    if (method == 'POST') {
      response = await http.post(
        Uri.parse(url),
        headers: headers,
        body: jsonEncode(body),
      );
    } else if (method == 'GET') {
      response = await http.get(Uri.parse(url), headers: headers);
    }
    
    return jsonDecode(response.body);
  } catch (e) {
    return {'error': e.toString()};
  }
}
```

### Login Handler
```dart
Future<bool> login(String email, String password) async {
  final result = await apiCall('POST', '/login', body: {
    'email': email,
    'password': password,
  });
  
  if (result.containsKey('access_token')) {
    await SecureStorage.saveToken(result['access_token']);
    await SharedPreferences.saveUser(result['user']);
    return true;
  }
  return false;
}
```

### Get Urgent Feed
```dart
Future<List> getUrgentFeed(String token) async {
  final result = await apiCall('GET', '/documents/urgent', token: token);
  return result['documents'] ?? [];
}
```

---

## 📱 UI Component Examples

### Document Status Badge
```dart
Widget statusBadge(String status) {
  Map<String, Color> colors = {
    'pending_dg_init': Colors.red,
    'pending_dispatch': Colors.orange,
    'dg_directed': Colors.blue,
    'pending_vdg_approval': Colors.purple,
    'pending_dg_approval': Colors.amber,
    'dg_signed': Colors.green,
    'completed_archive': Colors.grey,
  };
  
  return Container(
    padding: EdgeInsets.symmetric(horizontal: 12, vertical: 6),
    decoration: BoxDecoration(
      color: colors[status] ?? Colors.grey,
      borderRadius: BorderRadius.circular(20),
    ),
    child: Text(status.replaceAll('_', ' '), style: TextStyle(color: Colors.white)),
  );
}
```

### Document Tile
```dart
ListTile(
  title: Text(document['title']),
  subtitle: Text('${document['control_no']} • ${document['uploader']['name']}'),
  trailing: statusBadge(document['status']),
  onTap: () => navigateToDetail(document['id']),
)
```

---

## 🔐 Token Storage (Recommended Setup)

```dart
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class AuthService {
  static const storage = FlutterSecureStorage();
  static const tokenKey = 'auth_token';
  static const userKey = 'user_data';

  static Future<void> saveToken(String token) async {
    await storage.write(key: tokenKey, value: token);
  }

  static Future<String?> getToken() async {
    return await storage.read(key: tokenKey);
  }

  static Future<void> clearAuth() async {
    await storage.delete(key: tokenKey);
    await storage.delete(key: userKey);
  }
}
```

---

## 📊 State Management (Provider Example)

```dart
class AuthProvider extends ChangeNotifier {
  User? currentUser;
  String? token;
  
  Future<bool> login(String email, String password) async {
    try {
      final result = await apiCall('POST', '/login', body: {
        'email': email,
        'password': password,
      });
      
      if (result.containsKey('access_token')) {
        token = result['access_token'];
        currentUser = User.fromJson(result['user']);
        await AuthService.saveToken(token!);
        notifyListeners();
        return true;
      }
    } catch (e) {
      print('Login error: $e');
    }
    return false;
  }
  
  bool hasPermission(String action) {
    if (currentUser == null) return false;
    // Define action permissions per role
    Map<String, List<String>> perms = {
      'upload': ['file_dept'],
      'assign': ['dg'],
      'dispatch': ['file_dept'],
      'sign_vdg': ['vdg'],
      'sign_dg': ['dg'],
    };
    return perms[action]?.contains(currentUser!.role) ?? false;
  }
}
```

---

## 🚨 Error Handling Best Practices

```dart
Future<void> performAction() async {
  try {
    final result = await apiCall(...);
    
    if (result.containsKey('error')) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['error'])),
      );
    } else if (result.containsKey('message')) {
      // Success
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message'])),
      );
    }
  } on SocketException {
    showError('Network error. Check your connection.');
  } on TimeoutException {
    showError('Request timeout. Try again.');
  } catch (e) {
    showError('Unexpected error: $e');
  }
}
```

---

## 📋 File Upload Best Practices

```dart
Future<void> uploadDocument(String title, File file, String comment) async {
  if (file.lengthSync() > 10485760) { // 10MB
    showError('File size exceeds 10MB limit');
    return;
  }
  
  var request = http.MultipartRequest(
    'POST',
    Uri.parse('http://api.domain.com/api/documents'),
  );

  request.headers['Authorization'] = 'Bearer $token';
  request.fields['title'] = title;
  request.fields['comment'] = comment;
  request.files.add(await http.MultipartFile.fromPath('file', file.path));

  try {
    var response = await request.send();
    if (response.statusCode == 201) {
      showSuccess('Document uploaded successfully');
    } else {
      showError('Upload failed');
    }
  } catch (e) {
    showError('Upload error: $e');
  }
}
```

---

## 🎨 Color Scheme Recommendation

```dart
const statusColors = {
  'pending_dg_init': Color(0xFFEF5350), // Red
  'pending_dispatch': Color(0xFFFFA726), // Orange
  'dg_directed': Color(0xFF42A5F5), // Blue
  'pending_vdg_approval': Color(0xFFAB47BC), // Purple
  'pending_dg_approval': Color(0xFFFFCA28), // Amber
  'dg_signed': Color(0xFF66BB6A), // Green
  'completed_archive': Color(0xFFBDBDBD), // Grey
};

const roleColors = {
  'file_dept': Color(0xFF1976D2),
  'dg': Color(0xFFD32F2F),
  'vdg': Color(0xFFF57C00),
  'department': Color(0xFF388E3C),
  'staff': Color(0xFF7B1FA2),
};
```

---

## ✅ Testing Credentials (Example)

```
Email: file_dept_user@example.com
Pass: password123
Role: file_dept

Email: dg_user@example.com
Pass: password123
Role: dg

Email: vdg_user@example.com
Pass: password123
Role: vdg
Department: Finance (ID: 2)
```

---

## 📌 Implementation Checklist

- [ ] Setup API service layer with base URL configuration
- [ ] Implement secure token storage
- [ ] Create authentication flow (login/logout)
- [ ] Build role-based permission system
- [ ] Create dashboard with urgent feed
- [ ] Implement document upload with file picker
- [ ] Build document detail view with actions
- [ ] Implement workflow action buttons
- [ ] Create archive search functionality
- [ ] Add file download capability
- [ ] Implement error handling & retry logic
- [ ] Add network connectivity check
- [ ] Create loading states
- [ ] Add success/error snackbars
- [ ] Implement offline caching (optional)
- [ ] Add app logging for debugging
- [ ] Test all role-based scenarios
- [ ] Security audit before production

