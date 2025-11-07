# Troubleshooting - Content Creation Error

## Issue: "Undefined array key 'callerName'"

### Symptoms
When creating a new content/monument in `/content-manager`, you receive the error:
```
Errore nella richiesta: Si è verificato un errore durante l'elaborazione: Undefined array key "callerName"
```

### Root Cause
This error occurs when PHP cannot properly parse POST data from multipart/form-data requests (requests with file uploads). CodeIgniter 4's `$this->request->getPost()` method may not retrieve all form fields when files are included in the request.

### Solution Implemented
The `QrArtController::processQrArtContent()` method has been updated to:

1. **Validate Required Fields**: Check if all required fields are present
2. **Fallback Mechanism**: Use `getVar()` if `getPost()` doesn't return the data
3. **Better Error Messages**: Report which specific fields are missing
4. **Debug Logging**: Log received data for troubleshooting

### Code Changes

**Before:**
```php
public function processQrArtContent()
{
    try {
        $formData = $this->request->getPost();
        $contentData = [
            'caller_name' => $formData['callerName'],  // Could throw error if missing
            ...
        ];
    }
}
```

**After:**
```php
public function processQrArtContent()
{
    try {
        $formData = $this->request->getPost();

        // Validate and fallback
        $requiredFields = ['callerName', 'callerTitle', 'contentName', 'contentType'];
        foreach ($requiredFields as $field) {
            if (!isset($formData[$field])) {
                $formData[$field] = $this->request->getVar($field);
                if ($formData[$field] === null) {
                    throw new Exception("Campo obbligatorio mancante: $field");
                }
            }
        }

        $contentData = [
            'caller_name' => $formData['callerName'],  // Now guaranteed to exist
            ...
        ];
    }
}
```

### How to Debug

If you still encounter this error after the fix:

1. **Check Application Logs**
   ```bash
   tail -f backend/qrartApp/writable/logs/log-*.php
   ```

2. **Look for Debug Messages**
   ```
   DEBUG - Form data received: {"callerName":"...","callerTitle":"..."}
   DEBUG - Files received: ["callerBackground","callerAvatar"]
   ```

3. **Verify Form Submission**
   Open browser DevTools → Network tab → Look at the POST request to `/api/qrart/process`
   - Check Request Payload
   - Verify Content-Type is `multipart/form-data`
   - Ensure all fields are being sent

### Common Issues

#### Issue 1: Empty Form Fields
**Symptom**: Error says "Campi obbligatori mancanti"

**Solution**: Ensure all required fields in the form are filled:
- Nome Chiamante (callerName)
- Sottotitolo Chiamante (callerTitle)
- Nome Contenuto (contentName)
- Tipo di Contenuto (contentType)

#### Issue 2: JavaScript Not Sending Data
**Symptom**: Network tab shows request but data is missing

**Solution**: Check FormController in `app.js`:
```javascript
vm.submitForm = function() {
    var formData = new FormData();

    // Ensure these are appended
    formData.append('callerName', vm.formData.callerName);
    formData.append('callerTitle', vm.formData.callerTitle);
    formData.append('contentName', vm.formData.contentName);
    formData.append('contentType', vm.formData.contentType);

    // ... rest of the form data
};
```

#### Issue 3: PHP Configuration
**Symptom**: Large file uploads fail silently

**Solution**: Check PHP settings:
```ini
post_max_size = 100M
upload_max_filesize = 100M
max_execution_time = 300
```

### Testing the Fix

1. **Navigate to Content Manager**
   ```
   http://localhost:8080/content-manager
   ```

2. **Click "Crea Nuovo Contenuto"** or navigate to `/editor`

3. **Fill in the form**:
   - Nome Chiamante: "Test"
   - Sottotitolo: "Test Subtitle"
   - Nome Contenuto: "Test Content"
   - Tipo: Select any type (audio/video/audio_call/video_call)

4. **Add Language Variant**:
   - Click "Aggiungi Variante Linguistica"
   - Fill in required fields
   - Select language (IT/EN/DE/SV)

5. **Submit**:
   - If successful, you'll get: "Contenuto creato con successo"
   - If error, check logs for specific missing field

### Related Files

- **Controller**: `backend/qrartApp/app/Controllers/QrArtController.php`
- **Frontend Form**: `backend/qrartApp/public/views/contentEditorForm.html`
- **JavaScript**: `backend/qrartApp/public/app.js` (FormController)
- **Route**: `/api/qrart/process` (POST)

### Prevention

To prevent this error in the future:

1. **Always validate form data** in the controller
2. **Use both `getPost()` and `getVar()`** for multipart requests
3. **Log request data** in debug mode for troubleshooting
4. **Test with various content types** (audio, video, with/without files)
5. **Check browser console and network tab** before reporting bugs

### Still Having Issues?

If the error persists:

1. Check if the form is properly bound to AngularJS controller
2. Verify the API endpoint is correct (`/api/qrart/process`)
3. Check for JavaScript errors in browser console
4. Ensure database connection is working
5. Verify file upload permissions (writable directories)

### Additional Resources

- CodeIgniter 4 Request Handling: https://codeigniter.com/user_guide/incoming/incomingrequest.html
- File Uploads: https://codeigniter.com/user_guide/libraries/uploaded_files.html
- Debugging: See `DEPLOYMENT.md` troubleshooting section
