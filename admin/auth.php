<?php
session_start();

function requireAdminLogin(): void
{
    if (empty($_SESSION['is_admin'])) {
        header('Location: login.php');
        exit;
    }
}

function adminPassword(): string
{
    // Set ADMIN_PASSWORD as an environment variable in production.
    // This fallback is only for local development — change it before deploying.
    return getenv('ADMIN_PASSWORD') ?: 'change-me-please';
}
