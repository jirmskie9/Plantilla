
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...';
            });

            const currentDate = new Date();
            const targetDate = new Date('2025-05-10');
            
            if (currentDate.toDateString() === targetDate.toDateString()) {
                Swal.fire({
                    title: 'Test Ended',
                    text: 'Your test is end',
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false
                });
            }
        });
