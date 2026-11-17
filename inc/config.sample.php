<?php
// PurifyCMS Configuration
defined('DIR_ACCESS') or exit;

// System URL and Path Routing
$cfg_mainurl = 'http://localhost';
$cfg_admin_path = 'admin';
$cfg_admin_ips = '';
$cfg_trust_proxies = false;
$cfg_robots = true;
$cfg_domain = 'localhost';
$cfg_readonly_mode = false;
$cfg_maintenance_mode = false;
$cfg_maintenance_msg = '';

// Branding & Identity
$cfg_name = 'PurifyCMS';
$cfg_legalname = 'PurifyCMS';
$cfg_logolink = 'https://static.nywhash.com/partners/nywhash/nywhash_text_color.png';
$cfg_favicon = 'https://static.nywhash.com/partners/nywhash/nywhashsoftware_icon.png';
$cfg_support = 'support@example.com';
$cfg_googleanalytics = '';

// Social Links
$cfg_social_linkedin = '';
$cfg_social_instagram = '';
$cfg_social_x = '';
$cfg_social_facebook = '';

// Communication (SMTP)
$cfg_smtp_host = 'smtp.example.com';
$cfg_smtp_port = '587';
$cfg_smtp_user = 'user@example.com';
$cfg_smtp_pass = '';
$cfg_smtp_secure = 'tls';
$cfg_mail_from_name = 'PurifyCMS';
$cfg_mail_from_email = 'noreply@example.com';
$cfg_contact_email = 'contact@example.com';

// Theme & Appearance
$cfg_active_theme = 'purifydefault';
$cfg_admin_sidebar_color = '#4f46e5';
$cfg_admin_lang = 'en';

// Admin Authentication (Default password: admin)
// Generated using password_hash('admin', PASSWORD_DEFAULT)
$cfg_admin_user = 'admin';
$cfg_admin_passhash = '$2y$10$IpSaDatDAN5ogGeepQwurOQEmjFGjVXiPXmdGm2pFVbkn7/UZ0qRi';

// System Features
$cfg_enable_filemanager = true;
$cfg_enable_analytics = true;
$cfg_enable_captcha = false;

// eCommerce Settings
$cfg_enable_ecommerce = true;
$cfg_ecommerce_currency = 'USD';
$cfg_paytr_m_id = '';
$cfg_paytr_m_key = '';
$cfg_paytr_m_salt = '';
$cfg_paytr_test = 1;
$cfg_bank_details = '';
