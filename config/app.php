<?php
// Simple app-wide settings. Move to a DB-backed settings table later if you want
// the mess name/logo to be editable from the admin panel too.

define('ORG_NAME', 'Services Officers Mess Jhelum');
define('ORG_SHORT', 'SOM');

/**
 * Builds the base URL of the folder the current script is running in
 * (e.g. https://yourdomain.com/ServiceCardGenerator/public), so QR codes
 * work correctly regardless of subfolder deployment.
 * Set APP_BASE_URL as an env var to override (e.g. for CLI/cron generation).
 */
function appDirUrl(): string
{
    $override = getenv('APP_BASE_URL');
    if ($override) {
        return rtrim($override, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return $scheme . '://' . $host . $dir;
}