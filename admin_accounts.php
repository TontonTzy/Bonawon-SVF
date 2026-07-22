<?php
require_once __DIR__ . '/api/auth_utils.php';
require_super_admin();
$admin = get_current_admin();
$displayName = htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Accounts | St. Vincent Ferrer Parish</title>
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
        .admin-main { display: grid; gap: 24px; }
        .admin-card { background: white; border-radius: 16px; padding: 22px; box-shadow: 0 12px 28px rgba(0,0,0,0.08); }
        .admin-card h2 { margin-top: 0; color: #2d5a3d; }
        .actions-row { display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; }
        .control-group { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .control-group input, .control-group select { padding: 12px 14px; border: 1px solid #ccc; border-radius: 8px; width: 100%; max-width: 260px; }
        .btn-primary { background: #2d5a3d; color: white; padding: 12px 18px; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; }
        .btn-secondary { background: #f4f1e7; color: #2d5a3d; padding: 12px 18px; border: none; border-radius: 10px; cursor: pointer; }
        .admin-table th, .admin-table td { padding: 14px; border-bottom: 1px solid #eee; }
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
        @media (max-width: 860px) { .admin-table { font-size: 0.9rem; } }
    </style>
</head>
<body>
    <header class="admin-page-header">
        <h1>Admin Accounts</h1>
        <div class="admin-user-bar">
            <div class="admin-user-info">Logged in as <strong><?php echo $displayName; ?></strong></div>
            <button class="btn-logout" id="logout-button">Logout</button>
        </div>
    </header>

    <div class="admin-container admin-page-grid">
        <aside class="admin-sidebar">
            <a href="admin.php">Dashboard</a>
            <a href="admin_accounts.php" class="active">Admin Accounts</a>
        </aside>

        <main class="admin-main">
            <div class="admin-card">
                <div class="actions-row">
                    <div>
                        <h2>Manage Administrator Accounts</h2>
                        <p>Search, filter, add, and manage Super Admin and Admin accounts securely.</p>
                    </div>
                    <button class="btn-primary" id="add-admin-button">Add Admin</button>
                </div>
            </div>

            <div class="admin-card">
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
        </main>
    </div>

    <div id="admin-modal" style="display:none;"></div>
    <script>window.CSRF_TOKEN = '<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>';</script>
    <script src="js/auth.js"></script>
    <script src="js/admin_accounts.js"></script>
</body>
</html>
