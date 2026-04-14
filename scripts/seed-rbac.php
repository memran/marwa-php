<?php

$pdo = new PDO('sqlite:' . dirname(__DIR__) . '/database/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Creating roles table...\n";
$pdo->exec('CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    level INTEGER DEFAULT 1,
    description TEXT,
    is_system INTEGER DEFAULT 0,
    created_at VARCHAR(50),
    updated_at VARCHAR(50)
)');

echo "Creating permissions table...\n";
$pdo->exec('CREATE TABLE IF NOT EXISTS permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    "group" VARCHAR(50),
    created_at VARCHAR(50),
    updated_at VARCHAR(50)
)');

echo "Creating role_permission table...\n";
$pdo->exec('CREATE TABLE IF NOT EXISTS role_permission (
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
)');

echo "Seeding roles...\n";
$roles = [
    ['name' => 'Super Admin', 'slug' => 'super_admin', 'level' => 5, 'description' => 'Full system access', 'is_system' => 1],
    ['name' => 'Admin', 'slug' => 'admin', 'level' => 5, 'description' => 'Administrative access', 'is_system' => 1],
    ['name' => 'Manager', 'slug' => 'manager', 'level' => 4, 'description' => 'Team management', 'is_system' => 1],
    ['name' => 'Staff', 'slug' => 'staff', 'level' => 2, 'description' => 'Operational access', 'is_system' => 1],
    ['name' => 'Viewer', 'slug' => 'viewer', 'level' => 1, 'description' => 'Read-only access', 'is_system' => 1],
];

$roleStmt = $pdo->prepare('INSERT INTO roles (name, slug, level, description, is_system, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
$roleIds = [];
foreach ($roles as $role) {
    $now = date('Y-m-d H:i:s');
    $roleStmt->execute([$role['name'], $role['slug'], $role['level'], $role['description'], $role['is_system'], $now, $now]);
    $roleIds[$role['slug']] = $pdo->lastInsertId();
}

echo "Seeding permissions...\n";
$permissions = [
    ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'group' => 'dashboard'],
    ['name' => 'View Users', 'slug' => 'users.view', 'group' => 'users'],
    ['name' => 'Create Users', 'slug' => 'users.create', 'group' => 'users'],
    ['name' => 'Edit Users', 'slug' => 'users.edit', 'group' => 'users'],
    ['name' => 'Delete Users', 'slug' => 'users.delete', 'group' => 'users'],
    ['name' => 'Restore Users', 'slug' => 'users.restore', 'group' => 'users'],
    ['name' => 'View Activity', 'slug' => 'activity.view', 'group' => 'activity'],
    ['name' => 'View Notifications', 'slug' => 'notifications.view', 'group' => 'notifications'],
    ['name' => 'Manage Notifications', 'slug' => 'notifications.manage', 'group' => 'notifications'],
    ['name' => 'View Settings', 'slug' => 'settings.view', 'group' => 'settings'],
    ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'settings'],
    ['name' => 'View Database', 'slug' => 'database.view', 'group' => 'database'],
    ['name' => 'Query Database', 'slug' => 'database.query', 'group' => 'database'],
    ['name' => 'View Roles', 'slug' => 'roles.view', 'group' => 'roles'],
    ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'group' => 'roles'],
    ['name' => 'View Permissions', 'slug' => 'permissions.view', 'group' => 'permissions'],
    ['name' => 'Manage Permissions', 'slug' => 'permissions.manage', 'group' => 'permissions'],
];

$permStmt = $pdo->prepare('INSERT INTO permissions (name, slug, "group", created_at, updated_at) VALUES (?, ?, ?, ?, ?)');
$permIds = [];
foreach ($permissions as $perm) {
    $now = date('Y-m-d H:i:s');
    $permStmt->execute([$perm['name'], $perm['slug'], $perm['group'], $now, $now]);
    $permIds[$perm['slug']] = $pdo->lastInsertId();
}

echo "Assigning permissions to roles...\n";
$rolePermAssignments = [
    'super_admin' => array_values($permIds),
    'admin' => array_values($permIds),
    'manager' => [
        $permIds['dashboard.view'],
        $permIds['users.view'],
        $permIds['activity.view'],
        $permIds['notifications.view'],
        $permIds['settings.view'],
    ],
    'staff' => [
        $permIds['dashboard.view'],
        $permIds['notifications.view'],
    ],
    'viewer' => [
        $permIds['dashboard.view'],
    ],
];

$rpStmt = $pdo->prepare('INSERT INTO role_permission (role_id, permission_id) VALUES (?, ?)');
foreach ($rolePermAssignments as $roleSlug => $permIdList) {
    foreach ($permIdList as $permId) {
        $rpStmt->execute([$roleIds[$roleSlug], $permId]);
    }
}

echo "Done! Roles and permissions seeded.\n";