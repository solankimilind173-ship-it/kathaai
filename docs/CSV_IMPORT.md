# CSV Import Implementation

Production-ready user import for admin. Validates file and rows before writing; uses DB transactions and sanitization.

---

## Endpoint

- **Method:** `POST`
- **URL:** `/admin/users/import`
- **Auth:** Admin or super_admin only (middleware: auth, suspended, role)
- **Content-Type:** `multipart/form-data`
- **Field:** `file` (required) – CSV or TXT, max 10 MB

---

## CSV format

- **Encoding:** UTF-8 recommended.
- **Header row:** Required. Must include both columns (case-insensitive):
  - `name` – user display name (optional; defaults to "User" if empty)
  - `email` – unique, valid email (required per row)
- **Max rows:** 1000 data rows (after header). Extra rows are ignored.
- **Delimiter:** Comma (standard CSV).

### Example file

```csv
name,email
Jane Doe,jane@example.com
John Smith,john@example.com
,newuser@example.com
```

Row 3 is valid: name becomes "User". Rows with invalid email or duplicate email are skipped (counted in "Skipped" in the success message).

---

## Validation (before any import)

1. **File:** required, file type csv/txt, max size 10240 KB.
2. **Structure (rule `CsvUserImportStructure`):**
   - Header must contain both `name` and `email`.
   - Each row (up to 1000): email required and valid format; name length ≤ 255.
   - If any row fails, the whole request fails with validation errors (no users created).

---

## Backend flow

1. `ImportUsersRequest` validates file + runs `CsvUserImportStructure`.
2. `UsersController::importCsv()`:
   - Parses CSV; finds name/email column indices from header.
   - Builds array of `['name' => trim(strip_tags(...)), 'email' => trim(strip_tags(...))]`.
   - Calls `UserService::importFromCsvRows($rows)` inside a single **DB::transaction**.
3. `UserService::importFromCsvRows()`:
   - For each row: sanitizes name/email; skips invalid or duplicate email; creates user with random password and role `user`.
   - Returns `['created' => int, 'skipped' => int]`.
4. Redirect to `admin.users.index` with flash: `"Imported {created} users. Skipped {skipped}."`

---

## Security

- Only admins can hit the route.
- File type and size limited.
- Rows validated before any insert.
- Inputs sanitized: `trim(strip_tags(...))` on name and email.
- Passwords: random 32-char, bcrypt; never sent or logged.
- Duplicate emails in CSV or existing in DB are skipped (no overwrite).

---

## Frontend (Admin Users index)

- Form: file input (accept `.csv,.txt`), submit to `route('admin.users.import')` with `POST`, `enctype="multipart/form-data"`, `forceFormData: true` if using Inertia.
- Success: flash message shows "Imported X users. Skipped Y."
- Validation errors: display `flash.error` or Inertia validation `errors.file`.

---

## Files

| File | Purpose |
|------|---------|
| `app/Modules/Admin/Controllers/UsersController.php` | `importCsv()` – parse CSV, call service, redirect |
| `app/Modules/Admin/Requests/ImportUsersRequest.php` | File validation + `CsvUserImportStructure` rule |
| `app/Rules/CsvUserImportStructure.php` | Validates header and each row (structure + email + name length) |
| `app/Modules/Admin/Services/UserService.php` | `importFromCsvRows()` – transactional create, sanitization, skip invalid/duplicate |

---

*This is the single CSV import implementation in the app; use it as the reference for production behavior.*
