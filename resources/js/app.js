import './bootstrap';
import Swal from 'sweetalert2';
import Alpine from 'alpinejs';

window.Swal = Swal;
window.Alpine = Alpine;

Alpine.start();

// Global handler for Laravel flash messages & validation errors
document.addEventListener('DOMContentLoaded', () => {
    if (window.flashMessages) {
        if (window.flashMessages.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: window.flashMessages.success,
                confirmButtonColor: '#2563eb',
                timer: 3500,
                timerProgressBar: true
            });
        }
        if (window.flashMessages.error) {
            Swal.fire({
                icon: 'error',
                title: 'Perhatian',
                text: window.flashMessages.error,
                confirmButtonColor: '#ef4444'
            });
        }
        if (window.flashMessages.validationErrors && window.flashMessages.validationErrors.length > 0) {
            let errorHtml = '<div class="text-start p-2" style="font-size: 0.9rem; line-height: 1.6;">';
            window.flashMessages.validationErrors.forEach(err => {
                errorHtml += '<div class="mb-2"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>' + err + '</div>';
            });
            errorHtml += '</div>';

            Swal.fire({
                icon: 'warning',
                title: 'Transisi / Validasi Gagal',
                html: errorHtml,
                confirmButtonColor: '#f59e0b',
                confirmButtonText: 'Mengerti'
            });
        }
    }
});


