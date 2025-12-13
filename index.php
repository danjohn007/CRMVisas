<?php
/**
 * Root Index - CRM Visas
 * Redirects to the main application
 */

// Load URL configuration to get BASE_URL
require_once __DIR__ . '/config/url.php';

// Redirect to login page
header('Location: ' . BASE_URL . '/public/index.php?page=login');
exit;
