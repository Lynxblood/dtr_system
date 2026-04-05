/**
 * Main Application JavaScript
 */

// Helper function to show alerts from AJAX responses
function showAlert(containerId, type, message) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const colorClass = type === 'success' 
        ? 'text-green-800 bg-green-50 dark:bg-gray-800 dark:text-green-400 border border-green-200 dark:border-green-700'
        : 'text-red-800 bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-700';

    const title = type === 'success' ? 'Success!' : 'Error!';

    container.classList.remove('hidden');
    container.innerHTML = `
        <div class="p-4 text-sm rounded-lg ${colorClass}" role="alert">
            <span class="font-medium">${title}</span> ${message}
        </div>
    `;
}

// Initialize file upload handlers
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadForm');
    if (!uploadForm) return;

    uploadForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const fileInput = document.getElementById('file');
        const alertContainer = document.getElementById('alertContainer');

        if (!fileInput || fileInput.files.length === 0) {
            showAlert('alertContainer', 'error', 'Please select a file first.');
            return;
        }

        const formData = new FormData();
        formData.append('file', fileInput.files[0]);

        const baseUrl = (window.APP_URL || '').replace(/\/$/, '');
        const importApiUrl = baseUrl ? baseUrl + '/public/import-api.php' : 'import-api.php';

        try {
            const response = await fetch(importApiUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                showAlert('alertContainer', 'success', result.message);
                setTimeout(() => location.reload(), 2000);
            } else {
                showAlert('alertContainer', 'error', result.message);
            }
        } catch (error) {
            showAlert('alertContainer', 'error', 'An unexpected error occurred during the upload.');
        }
    });
});
