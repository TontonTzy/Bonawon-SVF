<?php
require_once __DIR__ . '/api/auth_utils.php';
require_login();
$admin = get_current_admin();
$displayName = htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name'], ENT_QUOTES, 'UTF-8');
$roleLabel = $admin['role'] === 'super_admin' ? 'Super Admin' : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Portal | St. Vincent Ferrer Parish</title>
    <link rel="stylesheet" href="css/styles.css" />
    <style>
        .admin-page-header { background: linear-gradient(rgba(45, 90, 61, 0.95), rgba(45, 90, 61, 0.95)); color: white; padding: 28px 24px; text-align: center; }
        .admin-page-header h1 { margin: 0 0 8px 0; font-size: 2rem; }
        .admin-user-bar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; margin-top: 16px; }
        .admin-user-info { color: #f1f1f1; font-size: 0.95rem; }
        .btn-logout { background: #d9534f; color: white; padding: 10px 18px; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; }
        .admin-container { max-width: 1200px; margin: 24px auto; padding: 0 24px; }
        .admin-sidebar { display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; }
        .admin-sidebar a { display: block; padding: 14px 18px; background: #f4f1e7; color: #2d5a3d; border-radius: 10px; text-decoration: none; font-weight: 700; }
        .admin-sidebar a.active { background: #2d5a3d; color: white; }
        .admin-main { display: grid; grid-template-columns: 1fr; gap: 24px; }
        .admin-welcome { display: grid; gap: 16px; }
        .admin-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
        .welcome-card { background: white; border-radius: 16px; padding: 22px; box-shadow: 0 12px 28px rgba(0,0,0,0.08); }
        .welcome-card h2 { margin: 0 0 10px; color: #2d5a3d; }
        .welcome-card p { margin: 0; color: #555; }
        .admin-note { background: #d4a041; color: #1a1a1a; padding: 18px; border-radius: 12px; font-weight: 600; }
        .actions-row { display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .control-group { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; width: 100%; }
        .control-group input, .control-group select { padding: 12px 14px; border: 1px solid #ccc; border-radius: 8px; width: 100%; max-width: 260px; }
        .data-table-wrapper { overflow-x: auto; }
        table.admin-table { width: 100%; border-collapse: collapse; margin-top: 10px; text-align: left; }
        table.admin-table th { background: #f4f1e7; color: #2d5a3d; padding: 12px 14px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e0dacb; }
        table.admin-table td { padding: 14px; border-bottom: 1px solid #eee; font-size: 0.9rem; vertical-align: top; }
        table.admin-table tr:hover { background: #fafafa; }
        .profile-pill { display: inline-flex; align-items: center; gap: 10px; }
        .profile-avatar { width: 38px; height: 38px; border-radius: 50%; background: #d4a041; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .badge { display: inline-flex; padding: 6px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; }
        .badge-super { background: #2d5a3d; color: white; }
        .badge-admin { background: #888; color: white; }
        .badge-active { background: #2d5a3d; color: white; }
        .badge-inactive { background: #d9534f; color: white; }
        .action-button { padding: 8px 12px; border-radius: 8px; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 700; }
        .action-button.view { background: #f4f1e7; color: #2d5a3d; }
        .action-button.edit { background: #d4a041; color: #1a1a1a; }
        .action-button.delete { background: #d9534f; color: white; }
        @media (min-width: 900px) { .admin-page-grid { display: grid; grid-template-columns: 240px 1fr; gap: 24px; } }
        @media (max-width: 680px) { .admin-user-bar { flex-direction: column; align-items: flex-start; } }
    </style>
</head>
<body>
    <header class="admin-page-header">
        <h1>Parish Content & Events Management</h1>
        <div class="admin-user-bar">
            <div class="admin-user-info">Logged in as <strong><?php echo $displayName; ?></strong> &middot; <em><?php echo $roleLabel; ?></em></div>
            <button class="btn-logout" id="logout-button">Logout</button>
        </div>
    </header>

    <div class="admin-container admin-page-grid">
        <aside class="admin-sidebar">
            <a href="admin.php" class="active">Dashboard</a>
            <a href="admin.php#events-tab">Manage Events</a>
            <a href="admin.php#announcements-tab">Manage Announcements</a>
            <?php if ($admin['role'] === 'super_admin'): ?><a href="admin_accounts.php">Admin Accounts</a><?php endif; ?>
        </aside>

        <main class="admin-main">
            <div class="welcome-card admin-welcome">
                <div>
                    <h2>Welcome, <?php echo $admin['first_name']; ?></h2>
                    <p>Use the sections below to manage parish announcements, events, and administrator accounts.</p>
                </div>
                <div class="admin-summary">
                    <div class="welcome-card">
                        <h3>Role</h3>
                        <p><?php echo $roleLabel; ?></p>
                    </div>
                    <div class="welcome-card">
                        <h3>Last login</h3>
                        <p><?php echo htmlspecialchars($admin['last_login_at'] ?? 'Never', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="welcome-card">
                        <h3>Status</h3>
                        <p><?php echo htmlspecialchars($admin['status'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>

            <div class="welcome-card admin-note">
                <p>Use the admin sections below for database-driven updates. All administrative actions require a secure login.</p>
            </div>

            <div class="welcome-card" id="admin-dashboard-content">
                <h2>Admin Dashboard</h2>
                <p>This page preserves your existing admin dashboard layout while enforcing secure access.</p>
                <p>Use the sidebar to navigate to Events, Announcements, and Admin Accounts.</p>
            </div>

            <?php if ($admin['role'] === 'super_admin'): ?>
                <div class="welcome-card" id="admin-accounts-section">
                    <div class="actions-row">
                        <div>
                            <h2>Admin Account Management</h2>
                            <p>Search, add, edit, and manage administrator accounts from one secure dashboard.</p>
                        </div>
                        <button class="btn-primary" id="add-admin-button">Add Admin</button>
                    </div>
                    <div class="actions-row">
                        <div class="control-group">
                            <input type="text" id="search-input" placeholder="Search by name, username, or email" />
                            <select id="role-filter">
                                <option value="all">All Roles</option>
                                <option value="super_admin">Super Admin</option>
                                <option value="admin">Admin</option>
                            </select>
                            <select id="status-filter">
                                <option value="all">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="data-table-wrapper">
                        <table class="admin-table" id="admin-table">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="admin-table-body">
                                <tr><td colspan="8" style="text-align:center;color:#888;">Loading accounts...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="admin-modal" style="display:none;"></div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        window.CSRF_TOKEN = '<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>';
        document.getElementById('logout-button').addEventListener('click', async () => {
            try {
                const response = await fetch('/api/logout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: window.CSRF_TOKEN })
                });
                const result = await response.json();
                if (result.status === 'success') {
                    window.location.href = result.redirect || '/login.html';
                    return;
                }
            } catch (err) {
                console.error(err);
            }
            window.location.href = '/login.html';
        });
    </script>
    <?php if ($admin['role'] === 'super_admin'): ?>
        <script src="js/admin_accounts.js"></script>
    <?php endif; ?>
</body>
</html>
