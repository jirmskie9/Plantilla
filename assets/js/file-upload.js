// File upload handling
function handleFileUpload(formId, successCallback = null) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fileInput = form.querySelector('input[type="file"]');
        if (!fileInput || !fileInput.files.length) {
            Swal.fire({
                icon: 'error',
                title: 'No File Selected',
                text: 'Please select a file to upload.',
                confirmButtonText: 'OK'
            });
            return;
        }

        const file = fileInput.files[0];
        const formData = new FormData(form);

        // Show loading state
        Swal.fire({
            title: 'Uploading...',
            text: 'Please wait while we process your file',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Send the request
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: result.message || 'File uploaded successfully',
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    // Reset form
                    form.reset();
                    
                    // Call success callback if provided
                    if (typeof successCallback === 'function') {
                        successCallback(result);
                    }
                });
            } else {
                let errorMessage = result.error || 'An error occurred during upload';
                if (result.errors && result.errors.length) {
                    errorMessage = result.errors.join('\n');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Upload Failed',
                    text: errorMessage,
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Upload Failed',
                text: 'An error occurred during upload. Please try again.',
                confirmButtonText: 'OK'
            });
        });
    });
}

// Initialize file upload handlers
document.addEventListener('DOMContentLoaded', function() {
    // Handle main upload form
    handleFileUpload('uploadForm', function(result) {
        // Reload data if on data management page
        if (typeof recordsTable !== 'undefined') {
            recordsTable.ajax.reload();
        }
        if (typeof loadSpreadsheetData === 'function') {
            loadSpreadsheetData();
        }
    });

    // Handle modal upload form
    handleFileUpload('modalUploadForm', function(result) {
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('uploadModal'));
        if (modal) {
            modal.hide();
        }
        
        // Reload data if on data management page
        if (typeof recordsTable !== 'undefined') {
            recordsTable.ajax.reload();
        }
        if (typeof loadSpreadsheetData === 'function') {
            loadSpreadsheetData();
        }
    });
}); 