// API Integration Module with Automatic Static Fallback

const API_BASE_URL = 'api';

function isGitHubPages() {
    if (typeof window === 'undefined' || !window.location) return false;
    return /github\.io/i.test(window.location.hostname);
}

function getAnnouncementsApiUrl() {
    return isGitHubPages() ? `${API_BASE_URL}/announcements.json` : `${API_BASE_URL}/announcements.php`;
}

function getEventsApiUrl() {
    return isGitHubPages() ? `${API_BASE_URL}/events.json` : `${API_BASE_URL}/events.php`;
}

function resolveApiUrl(path) {
    if (!path) return path;

    try {
        if (typeof window !== 'undefined' && window.location && window.location.protocol === 'file:') {
            return new URL(path.replace(/^\.?\//, ''), 'http://localhost:8000/').toString();
        }

        return new URL(path, window.location.href).toString();
    } catch (error) {
        return path;
    }
}

async function safeFetch(url, options = {}) {
    const requestUrl = resolveApiUrl(url);
    const headers = options.headers || {};
    if (typeof window !== 'undefined' && window.CSRF_TOKEN && options.method && options.method.toUpperCase() !== 'GET') {
        headers['X-CSRF-Token'] = window.CSRF_TOKEN;
    }
    const response = await fetch(requestUrl, { ...options, headers });
    const text = await response.text();

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${text.slice(0, 120)}`);
    }

    let data;
    try {
        data = JSON.parse(text);
    } catch (e) {
        const trimmed = text.trim();
        if (trimmed.startsWith('<?php') || trimmed.includes('<?php') || trimmed.startsWith('<!DOCTYPE') || trimmed.startsWith('<html')) {
            throw new Error('The API endpoint is returning HTML or PHP source instead of JSON. Please open the site through a PHP-enabled local server such as http://localhost:8000');
        }
        throw e;
    }
    return { ok: response.ok, data: data };
}

async function safeFetchJson(url, options = {}) {
    return safeFetch(url, options);
}

async function fetchAnnouncements() {
    try {
        const { ok, data: result } = await safeFetch(getAnnouncementsApiUrl());
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
        const { ok, data: result } = await safeFetch(getEventsApiUrl());
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
    if (isGitHubPages()) {
        console.warn('GitHub Pages is static hosting; the contact message will be handled by EmailJS only.');
        return true;
    }

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
