<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');
session_destroy();
require_once __DIR__ . '/../inc/config.php';
header('Location: ' . rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path);
exit;
