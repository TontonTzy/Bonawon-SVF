// Admin Portal JavaScript Controller

let loadedEvents = [];
let loadedAnnouncements = [];

function checkEnvironment() {
    if (window.location.protocol === 'file:') {
        const header = document.querySelector('.admin-header');
        if (header) {
            const warning = document.createElement('div');
            warning.style.background = '#d9534f';
            warning.style.color = '#ffffff';
            warning.style.padding = '14px 20px';
            warning.style.marginTop = '16px';
            warning.style.borderRadius = '8px';
            warning.style.fontWeight = 'bold';
            warning.style.fontSize = '0.95rem';
            warning.innerHTML = '⚠️ NOTICE: You opened this page directly as a static file (file:///).<br>To run PHP and connect to your Aiven database, start PHP server (e.g. <code>C:\\xampp\\php\\php.exe -S localhost:8000</code>) and open: <a href="http://localhost:8000/admin.html" style="color: #fff; text-decoration: underline;">http://localhost:8000/admin.html</a>';
            header.appendChild(warning);
        }
    }
}

function switchTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

    const activeBtn = Array.from(document.querySelectorAll('.tab-btn')).find(b => b.getAttribute('onclick').includes(tabId));
    if (activeBtn) activeBtn.classList.add('active');

    const targetContent = document.getElementById(tabId);
    if (targetContent) targetContent.classList.add('active');
}

function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.className = `toast-msg ${isError ? 'toast-error' : 'toast-success'}`;
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 4500);
}

async function safeFetchJson(url, options = {}) {
    const normalizedUrl = typeof isGitHubPages === 'function' && isGitHubPages() && url.endsWith('.php')
        ? url.replace(/\.php$/i, '.json')
        : url;
    const requestUrl = typeof resolveApiUrl === 'function' ? resolveApiUrl(normalizedUrl) : normalizedUrl;
    const response = await fetch(requestUrl, options);
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
            throw new Error('The admin API is returning HTML or PHP source instead of JSON. Please open the admin page through http://localhost:8000/admin.html.');
        }
        throw new Error(`Response is not valid JSON: ${text.slice(0, 120)}`);
    }
    return { ok: response.ok, data: data };
}

// ----------------- EVENTS CRUD ----------------- //

async function loadAdminEvents() {
    const tbody = document.getElementById('events-table-body');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #888;">Loading events from database...</td></tr>';

    try {
        const { ok, data: result } = await safeFetchJson('api/events.php');

        if (ok && result.status === 'success' && Array.isArray(result.data)) {
            loadedEvents = result.data;
            renderEventsTable(loadedEvents);
        } else {
            const errorMsg = result.message || result.details || 'Failed to load events from database.';
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: #d9534f; font-weight: bold;">⚠️ ${escapeHtml(errorMsg)}</td></tr>`;
        }
    } catch (err) {
        if (window.location.protocol === 'file:') {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #d9534f; font-weight: bold;">⚠️ Opened via file:// protocol. Please serve via PHP server (e.g. <code>http://localhost:8000/admin.html</code>)</td></tr>';
        } else {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: #d9534f; font-weight: bold;">⚠️ Cannot reach API: ${escapeHtml(err.message)}</td></tr>`;
        }
    }
}

function renderEventsTable(events) {
    const tbody = document.getElementById('events-table-body');
    if (events.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #666;">No events found in database. Use the form above to add your first event!</td></tr>';
        return;
    }

    tbody.innerHTML = events.map(event => `
        <tr>
            <td><strong>${escapeHtml(event.month || '')} ${escapeHtml(String(event.day || ''))}, ${event.year || 2026}</strong> (${escapeHtml(event.dow || '')})</td>
            <td><strong>${escapeHtml(event.title || '')}</strong></td>
            <td><span class="badge-category">${escapeHtml(event.category || 'PARISH EVENT')}</span></td>
            <td>⏰ ${escapeHtml(event.time || 'TBA')}<br>📍 ${escapeHtml(event.location || 'Sanctuary')}</td>
            <td>${escapeHtml(event.description || '')}</td>
            <td>
                <div class="action-btns">
                    <button class="btn-action-edit" onclick="editEvent(${event.id})">Edit</button>
                    <button class="btn-action-delete" onclick="deleteEvent(${event.id})">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function editEvent(id) {
    const event = loadedEvents.find(e => Number(e.id) === Number(id));
    if (!event) return;

    document.getElementById('event-id').value = event.id;
    document.getElementById('event-title').value = event.title;
    document.getElementById('event-category').value = event.category || 'PARISH EVENT';
    document.getElementById('event-month').value = event.month || 'JAN';
    document.getElementById('event-day').value = event.day || 1;
    document.getElementById('event-year').value = event.year || 2026;
    document.getElementById('event-dow').value = event.dow || 'SUN';
    document.getElementById('event-time').value = event.time || '';
    document.getElementById('event-location').value = event.location || '';
    document.getElementById('event-description').value = event.description || '';

    document.getElementById('event-form-title').textContent = `Edit Event #${event.id}`;
    document.getElementById('event-submit-btn').textContent = 'Update Event';
    document.getElementById('event-cancel-btn').style.display = 'inline-block';

    window.scrollTo({ top: document.getElementById('event-form').offsetTop - 100, behavior: 'smooth' });
}

function resetEventForm() {
    document.getElementById('event-id').value = '';
    document.getElementById('event-form').reset();
    document.getElementById('event-form-title').textContent = 'Add New Parish Event';
    document.getElementById('event-submit-btn').textContent = 'Save Event to Database';
    document.getElementById('event-cancel-btn').style.display = 'none';
}

async function handleEventSubmit(e) {
    e.preventDefault();

    if (window.location.protocol === 'file:' || (typeof isGitHubPages === 'function' && isGitHubPages())) {
        showToast('This GitHub Pages deployment is static, so event changes cannot be saved here. Use a PHP-enabled host for admin updates.', true);
        return;
    }

    const id = document.getElementById('event-id').value;
    const isEdit = id !== '';

    const month = document.getElementById('event-month').value;
    const day = document.getElementById('event-day').value;
    const date_str = `${month} ${String(day).padStart(2, '0')}`;

    const payload = {
        id: isEdit ? Number(id) : undefined,
        title: document.getElementById('event-title').value.trim(),
        category: document.getElementById('event-category').value,
        month: month,
        day: Number(day),
        year: Number(document.getElementById('event-year').value),
        dow: document.getElementById('event-dow').value,
        date_str: date_str,
        time: document.getElementById('event-time').value.trim(),
        location: document.getElementById('event-location').value.trim(),
        description: document.getElementById('event-description').value.trim()
    };

    try {
        const { ok, data: result } = await safeFetchJson('api/manage_events.php', {
            method: isEdit ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (ok && result.status === 'success') {
            showToast(isEdit ? 'Event updated successfully!' : 'New event created successfully!');
            resetEventForm();
            loadAdminEvents();
        } else {
            showToast(result.message || 'Error saving event to MySQL database', true);
        }
    } catch (err) {
        showToast(`Failed to reach API server: ${err.message}`, true);
    }
}

async function deleteEvent(id) {
    if (!confirm('Are you sure you want to delete this event from the database?')) return;

    if (window.location.protocol === 'file:') {
        showToast('Please open the page via local web server (e.g. http://localhost:8000/admin.html) to delete items.', true);
        return;
    }

    try {
        const { ok, data: result } = await safeFetchJson('api/manage_events.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });

        if (ok && result.status === 'success') {
            showToast('Event deleted from database!');
            loadAdminEvents();
        } else {
            showToast(result.message || 'Error deleting event', true);
        }
    } catch (err) {
        showToast(`Failed to reach API server: ${err.message}`, true);
    }
}

// ----------------- ANNOUNCEMENTS CRUD ----------------- //

async function loadAdminAnnouncements() {
    const tbody = document.getElementById('ann-table-body');
    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #888;">Loading announcements...</td></tr>';

    try {
        const { ok, data: result } = await safeFetchJson('api/announcements.php');

        if (ok && result.status === 'success' && Array.isArray(result.data)) {
            loadedAnnouncements = result.data;
            renderAnnTable(loadedAnnouncements);
        } else {
            const errorMsg = result.message || 'Failed to load announcements.';
            tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #d9534f; font-weight: bold;">⚠️ ${escapeHtml(errorMsg)}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #d9534f; font-weight: bold;">⚠️ Cannot reach API: ${escapeHtml(err.message)}</td></tr>`;
    }
}

function renderAnnTable(announcements) {
    const tbody = document.getElementById('ann-table-body');
    if (announcements.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #666;">No announcements found in database.</td></tr>';
        return;
    }

    tbody.innerHTML = announcements.map(a => `
        <tr>
            <td>
                <img src="${escapeHtml(a.image || 'images/mamamarylindogon.jpg')}" alt="Thumbnail" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;" />
            </td>
            <td><strong>${escapeHtml(a.title || '')}</strong></td>
            <td><span class="badge-category">${escapeHtml(a.category || 'ANNOUNCEMENT')}</span></td>
            <td>${escapeHtml(a.description || '')}</td>
            <td>
                <div class="action-btns">
                    <button class="btn-action-edit" onclick="editAnn(${a.id})">Edit</button>
                    <button class="btn-action-delete" onclick="deleteAnn(${a.id})">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function editAnn(id) {
    const ann = loadedAnnouncements.find(a => Number(a.id) === Number(id));
    if (!ann) return;

    document.getElementById('ann-id').value = ann.id;
    document.getElementById('ann-title').value = ann.title;
    document.getElementById('ann-category').value = ann.category || 'PARISH ANNOUNCEMENT';
    document.getElementById('ann-existing-image').value = ann.image || 'images/mamamarylindogon.jpg';
    document.getElementById('ann-description').value = ann.description || '';

    // Show preview
    const previewContainer = document.getElementById('ann-image-preview-container');
    const previewImg = document.getElementById('ann-image-preview');
    if (previewContainer && previewImg) {
        previewImg.src = ann.image || 'images/mamamarylindogon.jpg';
        previewContainer.style.display = 'block';
    }

    document.getElementById('ann-form-title').textContent = `Edit Announcement #${ann.id}`;
    document.getElementById('ann-submit-btn').textContent = 'Update Announcement';
    document.getElementById('ann-cancel-btn').style.display = 'inline-block';
}

function resetAnnForm() {
    document.getElementById('ann-id').value = '';
    document.getElementById('ann-form').reset();
    document.getElementById('ann-existing-image').value = 'images/mamamarylindogon.jpg';
    const previewContainer = document.getElementById('ann-image-preview-container');
    if (previewContainer) previewContainer.style.display = 'none';

    document.getElementById('ann-form-title').textContent = 'Add New Parish Announcement';
    document.getElementById('ann-submit-btn').textContent = 'Save Announcement';
    document.getElementById('ann-cancel-btn').style.display = 'none';
}

async function handleAnnSubmit(e) {
    e.preventDefault();

    if (window.location.protocol === 'file:' || (typeof isGitHubPages === 'function' && isGitHubPages())) {
        showToast('This GitHub Pages deployment is static, so announcement changes cannot be saved here. Use a PHP-enabled host for admin updates.', true);
        return;
    }

    const id = document.getElementById('ann-id').value;
    const formData = new FormData();

    formData.append('action', 'save');
    if (id) formData.append('id', id);
    formData.append('title', document.getElementById('ann-title').value.trim());
    formData.append('category', document.getElementById('ann-category').value.trim());
    formData.append('description', document.getElementById('ann-description').value.trim());
    formData.append('existing_image', document.getElementById('ann-existing-image').value);

    const fileInput = document.getElementById('ann-image-file');
    if (fileInput && fileInput.files.length > 0) {
        formData.append('image_file', fileInput.files[0]);
    }

    try {
        const { ok, data: result } = await safeFetchJson('api/manage_announcements.php', {
            method: 'POST',
            body: formData
        });

        if (ok && result.status === 'success') {
            showToast(id ? 'Announcement updated!' : 'Announcement created successfully!');
            resetAnnForm();
            loadAdminAnnouncements();
        } else {
            showToast(result.message || 'Error saving announcement', true);
        }
    } catch (err) {
        showToast(`Failed to reach API server: ${err.message}`, true);
    }
}

async function deleteAnn(id) {
    if (!confirm('Are you sure you want to delete this announcement?')) return;

    if (window.location.protocol === 'file:') {
        showToast('Please open the page via local web server (e.g. http://localhost:8000/admin.html) to delete items.', true);
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    try {
        const { ok, data: result } = await safeFetchJson('api/manage_announcements.php', {
            method: 'POST',
            body: formData
        });

        if (ok && result.status === 'success') {
            showToast('Announcement deleted!');
            loadAdminAnnouncements();
        } else {
            showToast(result.message || 'Error deleting announcement', true);
        }
    } catch (err) {
        showToast(`Failed to reach API server: ${err.message}`, true);
    }
}

// Helpers
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

document.addEventListener('DOMContentLoaded', () => {
    checkEnvironment();
    loadAdminEvents();
    loadAdminAnnouncements();

    const eventForm = document.getElementById('event-form');
    if (eventForm) eventForm.addEventListener('submit', handleEventSubmit);

    const annForm = document.getElementById('ann-form');
    if (annForm) annForm.addEventListener('submit', handleAnnSubmit);

    // Image preview handler
    const fileInput = document.getElementById('ann-image-file');
    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            const previewContainer = document.getElementById('ann-image-preview-container');
            const previewImg = document.getElementById('ann-image-preview');

            if (file && previewContainer && previewImg) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    previewImg.src = evt.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
