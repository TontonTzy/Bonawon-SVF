// API Integration Module with Automatic Static Fallback

const API_BASE_URL = 'api';

async function safeFetch(url, options = {}) {
    const response = await fetch(url, options);
    const text = await response.text();
    let data;
    try {
        data = JSON.parse(text);
    } catch (e) {
        if (text.trim().startsWith('<?php') || text.includes('<?php')) {
            throw new Error('Web server is serving raw PHP code without executing it. Please access the site via http://localhost:8000');
        }
        throw e;
    }
    return { ok: response.ok, data: data };
}

async function fetchAnnouncements() {
    try {
        const { ok, data: result } = await safeFetch(`${API_BASE_URL}/announcements.php`);
        if (ok && result.status === 'success' && Array.isArray(result.data)) {
            console.log('Loaded announcements from Aiven MySQL database');
            return result.data;
        }
    } catch (error) {
        console.warn('API connection unavailable, falling back to local parishData:', error.message);
    }
    
    // Fallback to static data.js if API unavailable
    return typeof parishData !== 'undefined' ? parishData.announcements : [];
}

async function fetchEvents() {
    try {
        const { ok, data: result } = await safeFetch(`${API_BASE_URL}/events.php`);
        if (ok && result.status === 'success' && Array.isArray(result.data)) {
            console.log('Loaded events from Aiven MySQL database');
            return result.data;
        }
    } catch (error) {
        console.warn('API connection unavailable, falling back to local parishData:', error.message);
    }

    // Fallback to static data.js if API unavailable
    return typeof parishData !== 'undefined' ? parishData.events : [];
}

async function sendContactMessage(formData) {
    let apiSuccess = false;
    
    try {
        const { ok, data: result } = await safeFetch(`${API_BASE_URL}/contact.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        if (ok && result.status === 'success') {
            console.log('Contact message saved to Aiven database:', result);
            apiSuccess = true;
        }
    } catch (error) {
        console.warn('API unavailable for storing contact message:', error.message);
    }

    return apiSuccess;
}
