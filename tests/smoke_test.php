<?php
/**
 * PurifyCMS Core & Smoke Test Suite
 */

echo "==> Running PurifyCMS Smoke Tests..." . PHP_EOL;

// 1. Check directory constants
define('DIR_ACCESS', true);

// 2. Prepare mock config if missing
$config_file = __DIR__ . '/../inc/config.php';
$sample_file = __DIR__ . '/../inc/config.sample.php';
$created_temp_config = false;

if (!file_exists($config_file) && file_exists($sample_file)) {
    copy($sample_file, $config_file);
    $created_temp_config = true;
}

// 3. Test Kernel Boot & PurifyVolt Cache
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/kernel.php';
require_once __DIR__ . '/../inc/security.php';
require_once __DIR__ . '/../inc/version.php';

echo "Testing Kernel Boot...";
PurifyKernel::boot();
if (!defined('LIC_STATUS') || LIC_STATUS !== 'valid') {
    echo " FAIL: Kernel boot invalid" . PHP_EOL;
    exit(1);
}
echo " OK" . PHP_EOL;

// 4. Test Caching
echo "Testing PurifyVolt Cache Engine...";
PurifyVolt::set('ci_test_key', 'ci_test_val', 60);
$cached_val = PurifyVolt::get('ci_test_key');
if ($cached_val !== 'ci_test_val') {
    echo " FAIL: Cache mismatch" . PHP_EOL;
    exit(1);
}
echo " OK" . PHP_EOL;

// 5. Test Localization
echo "Testing Localization Engine...";
$test_str = p_lang('overview', 'Overview');
if (empty($test_str)) {
    echo " FAIL: Localization empty" . PHP_EOL;
    exit(1);
}
echo " OK" . PHP_EOL;

// 6. Test Security Helpers
echo "Testing Security Helpers...";
$token = generate_csrf_token();
if (!verify_csrf_token($token)) {
    echo " FAIL: CSRF token verification" . PHP_EOL;
    exit(1);
}
echo " OK" . PHP_EOL;

// 7. Test Commerce Trade Engine
echo "Testing PurifyTrade Engine...";
require_once __DIR__ . '/../inc/PurifyTrade.php';
$calc = PurifyTrade::calculate(100, 20);
if (!isset($calc['gross']) || $calc['gross'] != 120) {
    echo " FAIL: Price calculation mismatch" . PHP_EOL;
    exit(1);
}
echo " OK" . PHP_EOL;

// Cleanup if temp config was created for CI
if ($created_temp_config && file_exists($config_file)) {
    unlink($config_file);
}

echo "==> All Tests Passed Successfully!" . PHP_EOL;
exit(0);
