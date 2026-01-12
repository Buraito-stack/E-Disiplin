const showAlert = (type, message) => {
    const alertId = `alert-${Date.now()}`;
    const bgColor = type === 'error' ? 'bg-red-50' : 'bg-green-50';
    const borderColor = type === 'error' ? 'border-red-200' : 'border-green-200';
    const textColor = type === 'error' ? 'text-red-700' : 'text-green-700';

    const alertHTML = `
        <div id="${alertId}" class="alert-error ${bgColor} border ${borderColor} ${textColor} px-4 py-3 rounded-lg mb-4" role="alert">
            ${message}
        </div>
    `;

    const alertContainer = document.querySelector('[data-alert-container]');
    if (alertContainer) {
        alertContainer.innerHTML = alertHTML;
        
        setTimeout(() => {
            const element = document.getElementById(alertId);
            if (element) {
                element.remove();
            }
        }, 5000);
    }
};

const togglePasswordVisibility = (inputId, buttonId) => {
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);

    if (!input || !button) return;

    button.addEventListener('click', (e) => {
        e.preventDefault();
        const type = input.type === 'password' ? 'text' : 'password';
        input.type = type;
        
        const svg = button.querySelector('svg');
        if (svg) {
            svg.classList.toggle('opacity-50');
        }
    });
};

const handleFormSubmit = (formId, endpoint) => {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Loading...';

        try {
            const formData = new FormData(form);
            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    showAlert('success', data.message);
                }
            } else {
                showAlert('error', data.message);
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        } catch (error) {
            showAlert('error', 'Terjadi kesalahan. Silakan coba lagi.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        } finally {
            if (!form.dataset.redirecting) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    togglePasswordVisibility('password', 'passwordToggle');
    togglePasswordVisibility('password', 'passwordToggle');
});
