<?php
/**
 * PurifyCMS Localization Parity & Integrity Test
 */

$base_dir = dirname(__DIR__);
$locales_dir = $base_dir . '/inc/data/locales';

if (!is_dir($locales_dir)) {
    echo "[FAIL] Locales directory not found: {$locales_dir}\n";
    exit(1);
}

$ref_file = $locales_dir . '/admin_en.json';
if (!file_exists($ref_file)) {
    echo "[FAIL] Primary reference locale file (admin_en.json) missing.\n";
    exit(1);
}

$ref_data = json_decode(file_get_contents($ref_file), true);
if (!is_array($ref_data)) {
    echo "[FAIL] admin_en.json is not valid JSON.\n";
    exit(1);
}

$locale_files = glob($locales_dir . '/admin_*.json');
$has_errors = false;

echo "==> Testing Localization Parity against admin_en.json (" . count($ref_data) . " keys)...\n";

foreach ($locale_files as $file) {
    $basename = basename($file);
    if ($basename === 'admin_en.json') {
        continue;
    }

    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) {
        echo "[FAIL] {$basename} is corrupted or not valid JSON.\n";
        $has_errors = true;
        continue;
    }

    // Check missing keys compared to EN
    $missing_keys = array_diff_key($ref_data, $data);
    $extra_keys = array_diff_key($data, $ref_data);

    // Check empty values
    $empty_keys = [];
    foreach ($data as $k => $v) {
        if ($v === '' || $v === null) {
            $empty_keys[] = $k;
        }
    }

    if (!empty($missing_keys)) {
        echo "[ERROR] {$basename} is missing " . count($missing_keys) . " keys from admin_en.json:\n";
        foreach (array_slice(array_keys($missing_keys), 0, 10) as $mk) {
            echo "   - {$mk}\n";
        }
        if (count($missing_keys) > 10) {
            echo "   ... and " . (count($missing_keys) - 10) . " more.\n";
        }
        $has_errors = true;
    }

    if (!empty($empty_keys)) {
        echo "[ERROR] {$basename} has " . count($empty_keys) . " empty translation strings.\n";
        $has_errors = true;
    }

    if (empty($missing_keys) && empty($empty_keys)) {
        echo "  [OK] {$basename} is 100% in parity with reference.\n";
    }
}

if ($has_errors) {
    echo "\n[FAIL] Localization tests failed. Please synchronize locale files.\n";
    exit(1);
}

echo "==> All Localization Tests Passed Successfully!\n";
exit(0);
