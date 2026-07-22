// API Integration Module with Automatic Static Fallback

const API_BASE_URL = 'api';

async function fetchAnnouncements() {
    try {
        const response = await fetch(`${API_BASE_URL}/announcements.php`);
        if (response.ok) {
            const result = await response.json();
            if (result.status === 'success' && Array.isArray(result.data)) {
                console.log('Loaded announcements from XAMPP MySQL database');
                return result.data;
            }
        }
    } catch (error) {
        console.warn('XAMPP API connection unavailable, falling back to local parishData:', error.message);
    }
    
    // Fallback to static data.js if API unavailable
    return typeof parishData !== 'undefined' ? parishData.announcements : [];
}

async function fetchEvents() {
    try {
        const response = await fetch(`${API_BASE_URL}/events.php`);
        if (response.ok) {
            const result = await response.json();
            if (result.status === 'success' && Array.isArray(result.data)) {
                console.log('Loaded events from XAMPP MySQL database');
                return result.data;
            }
        }
    } catch (error) {
        console.warn('XAMPP API connection unavailable, falling back to local parishData:', error.message);
    }

    // Fallback to static data.js if API unavailable
    return typeof parishData !== 'undefined' ? parishData.events : [];
}

async function sendContactMessage(formData) {
    let apiSuccess = false;
    
    try {
        const response = await fetch(`${API_BASE_URL}/contact.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        if (response.ok) {
            const result = await response.json();
            console.log('Contact message saved to XAMPP database:', result);
            apiSuccess = true;
        }
    } catch (error) {
        console.warn('XAMPP API unavailable for storing contact message:', error.message);
    }

    return apiSuccess;
}
