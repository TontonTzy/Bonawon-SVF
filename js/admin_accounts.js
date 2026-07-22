const adminTableBody = document.getElementById('admin-table-body');
const searchInput = document.getElementById('search-input');
const roleFilter = document.getElementById('role-filter');
const statusFilter = document.getElementById('status-filter');
const addAdminButton = document.getElementById('add-admin-button');
const adminModal = document.getElementById('admin-modal');
let adminAccounts = [];

async function loadAdminAccounts() {
    adminTableBody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#888;">Loading accounts...</td></tr>';
    try {
        const result = await fetchAdminApi('/api/admin_accounts.php?action=list', { method: 'GET' });
        adminAccounts = Array.isArray(result.data) ? result.data : [];
        renderAdminAccounts();
    } catch (error) {
        adminTableBody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:#d9534f;">${escapeHtml(error.message)}</td></tr>`;
    }
}

function renderAdminAccounts() {
    const query = searchInput.value.trim().toLowerCase();
    const role = roleFilter.value;
    const status = statusFilter.value;

    const filtered = adminAccounts.filter((account) => {
        const matchSearch = query === '' || [account.first_name, account.last_name, account.username, account.email].some(value => value.toLowerCase().includes(query));
        const matchRole = role === 'all' || account.role === role;
        const matchStatus = status === 'all' || account.status === status;
        return matchSearch && matchRole && matchStatus;
    });

    if (filtered.length === 0) {
        adminTableBody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#666;">No administrator accounts found.</td></tr>';
        return;
    }

    adminTableBody.innerHTML = filtered.map(account => {
        const initials = getInitials(account.first_name, account.last_name);
        const roleBadge = account.role === 'super_admin' ? '<span class="badge badge-super">Super Admin</span>' : '<span class="badge badge-admin">Admin</span>';
        const statusBadge = account.status === 'active' ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Inactive</span>';
        return `
            <tr>
                <td>
                    <div class="profile-pill"><span class="profile-avatar">${initials}</span><span>${escapeHtml(account.first_name)} ${escapeHtml(account.last_name)}</span></div>
                </td>
                <td>${escapeHtml(account.username)}</td>
                <td>${escapeHtml(account.email)}</td>
                <td>${roleBadge}</td>
                <td>${statusBadge}</td>
                <td>${escapeHtml(account.last_login_at || 'Never')}</td>
                <td>${escapeHtml(account.created_at || '')}</td>
                <td>
                    <button class="action-button view" onclick="openAdminModal('view', ${account.id})">View</button>
                    <button class="action-button edit" onclick="openAdminModal('edit', ${account.id})">Edit</button>
                    <button class="action-button delete" onclick="openAdminModal('delete', ${account.id})">Delete</button>
                </td>
            </tr>
        `;
    }).join('');
}

function getInitials(firstName, lastName) {
    const first = firstName ? firstName.charAt(0).toUpperCase() : '';
    const last = lastName ? lastName.charAt(0).toUpperCase() : '';
    return `${first}${last}`;
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function openAdminModal(mode, accountId = null) {
    const account = adminAccounts.find(item => item.id === accountId) || null;
    const title = mode === 'view' ? 'View Admin Account' : mode === 'edit' ? 'Edit Admin Account' : mode === 'delete' ? 'Delete Admin Account' : 'Add Admin Account';
    const isDelete = mode === 'delete';
    const actionText = mode === 'delete' ? 'Delete' : mode === 'edit' ? 'Save Changes' : 'Create Account';

    const content = isDelete ? `
        <div class="admin-card">
            <h2>${title}</h2>
            <p>Are you sure you want to delete <strong>${escapeHtml(account.first_name)} ${escapeHtml(account.last_name)}</strong>?</p>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button class="btn-primary" onclick="confirmDeleteAdmin(${accountId})">Yes, delete</button>
                <button class="btn-secondary" onclick="closeAdminModal()">Cancel</button>
            </div>
        </div>
    ` : `
        <div class="admin-card">
            <h2>${title}</h2>
            <form id="admin-account-form">
                <div style="display:grid;gap:14px;">
                    <div><label>First Name</label><input type="text" id="admin-first-name" value="${escapeHtml(account?.first_name || '')}" required /></div>
                    <div><label>Last Name</label><input type="text" id="admin-last-name" value="${escapeHtml(account?.last_name || '')}" required /></div>
                    <div><label>Username</label><input type="text" id="admin-username" value="${escapeHtml(account?.username || '')}" required /></div>
                    <div><label>Email</label><input type="email" id="admin-email" value="${escapeHtml(account?.email || '')}" required /></div>
                    <div><label>Role</label><select id="admin-role"><option value="super_admin" ${account?.role === 'super_admin' ? 'selected' : ''}>Super Admin</option><option value="admin" ${account?.role === 'admin' ? 'selected' : ''}>Admin</option></select></div>
                    <div><label>Status</label><select id="admin-status"><option value="active" ${account?.status === 'active' ? 'selected' : ''}>Active</option><option value="inactive" ${account?.status === 'inactive' ? 'selected' : ''}>Inactive</option></select></div>
                    <div><label>Password ${mode === 'edit' ? '(leave blank to keep current)' : ''}</label><input type="password" id="admin-password" /></div>
                    <div><label>Confirm Password</label><input type="password" id="admin-password-confirm" /></div>
                </div>
                <div id="admin-form-message" style="margin-top:12px;color:#d9534f;"></div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;">
                    <button type="submit" class="btn-primary">${actionText}</button>
                    <button type="button" class="btn-secondary" onclick="closeAdminModal()">Cancel</button>
                </div>
            </form>
        </div>
    `;

    adminModal.innerHTML = `<div style="position:fixed;inset:0;background:rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;padding:16px;z-index:9999;">${content}</div>`;
    adminModal.style.display = 'block';

    if (!isDelete) {
        document.getElementById('admin-account-form').addEventListener('submit', (event) => {
            event.preventDefault();
            saveAdminAccount(mode, accountId);
        });
    }
}

function closeAdminModal() {
    adminModal.style.display = 'none';
    adminModal.innerHTML = '';
}

async function saveAdminAccount(mode, accountId) {
    const firstName = document.getElementById('admin-first-name').value.trim();
    const lastName = document.getElementById('admin-last-name').value.trim();
    const username = document.getElementById('admin-username').value.trim();
    const email = document.getElementById('admin-email').value.trim();
    const role = document.getElementById('admin-role').value;
    const status = document.getElementById('admin-status').value;
    const password = document.getElementById('admin-password').value;
    const passwordConfirm = document.getElementById('admin-password-confirm').value;
    const formMessage = document.getElementById('admin-form-message');

    formMessage.textContent = '';
    if (!firstName || !lastName || !username || !email) {
        formMessage.textContent = 'Please fill in all required fields.';
        return;
    }
    if (password && password !== passwordConfirm) {
        formMessage.textContent = 'Passwords do not match.';
        return;
    }
    if (password && password.length < 10) {
        formMessage.textContent = 'Password must be at least 10 characters.';
        return;
    }

    const action = mode === 'edit' ? 'update' : 'create';
    const payload = { action, id: accountId, first_name: firstName, last_name: lastName, username, email, role, status, password };
    if (!password) delete payload.password;

    try {
        const result = await fetchAdminApi('/api/admin_accounts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        if (result.status === 'success') {
            closeAdminModal();
            await loadAdminAccounts();
            return;
        }
        formMessage.textContent = result.message || 'Unable to save account.';
    } catch (err) {
        formMessage.textContent = err.message;
    }
}

async function confirmDeleteAdmin(accountId) {
    try {
        const result = await fetchAdminApi('/api/admin_accounts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: accountId })
        });
        if (result.status === 'success') {
            closeAdminModal();
            await loadAdminAccounts();
            return;
        }
        alert(result.message || 'Unable to delete account.');
    } catch (err) {
        alert(err.message);
    }
}

searchInput.addEventListener('input', renderAdminAccounts);
roleFilter.addEventListener('change', renderAdminAccounts);
statusFilter.addEventListener('change', renderAdminAccounts);
addAdminButton.addEventListener('click', () => openAdminModal('create'));

loadAdminAccounts();
