// EmailJS Configuration
// Replace with your actual EmailJS credentials
const EMAILJS_SERVICE_ID = "service_38916nj";
const EMAILJS_TEMPLATE_ID = "template_0zk8u6q";
const EMAILJS_PUBLIC_KEY = "qgSwiBrJ6hxF88T_p";

// Initialize EmailJS
emailjs.init(EMAILJS_PUBLIC_KEY);

// Notification function
function showNotification(message, type = 'success') {
    const notificationEl = document.getElementById('notification');
    
    notificationEl.textContent = message;
    notificationEl.className = 'notification ' + type;
    notificationEl.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        notificationEl.style.display = 'none';
    }, 5000);
}

// Handle contact form submission
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contact-form') || document.querySelector('.contact-form');
    const submitBtn = document.querySelector('.form-submit');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Disable button during submission
            submitBtn.disabled = true;
            submitBtn.textContent = 'SENDING...';
            const localTime = new Date().toLocaleTimeString();
            // Get form values
            const formData = {
                from_name: document.getElementById('name').value,
                from_email: document.getElementById('email').value,
                time: localTime,
                phone: document.getElementById('phone').value,
                subject: document.getElementById('subject').value,
                message: document.getElementById('message').value
            };
            
            if (typeof emailjs === 'undefined') {
                showNotification('✗ Email service could not be loaded. Please refresh the page and try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'SEND MESSAGE';
                return;
            }

            // Send email using EmailJS
            emailjs.send(EMAILJS_SERVICE_ID, EMAILJS_TEMPLATE_ID, formData)
                .then(function(response) {
                    console.log('SUCCESS!', response.status, response.text);
                    showNotification('✓ Message sent successfully! We will reply soon.', 'success');
                    contactForm.reset();
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'SEND MESSAGE';
                }, function(error) {
                    console.log('FAILED...', error);
                    showNotification('✗ Failed to send message. Please try again or contact us directly.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'SEND MESSAGE';
                });
        });
    }
});
