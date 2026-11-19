<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

header('Content-Type: application/json; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$name = isset($_POST['name']) ? htmlspecialchars(strip_tags(trim($_POST['name']))) : '';
$email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
$message = isset($_POST['message']) ? htmlspecialchars(strip_tags(trim($_POST['message']))) : '';

if (empty($name) || empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

if (isset($cfg_enable_captcha) && $cfg_enable_captcha) {
    $captcha_input = $_POST['captcha_result'] ?? '';
    if (!NywhashCaptcha::verify($captcha_input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid captcha. Please solve the math question.']);
        exit;
    }
}

$root_dir = dirname(__DIR__);
$data_dir = $root_dir . '/inc/data/messages';
if (!is_dir($data_dir)) @mkdir($data_dir, 0755, true);

$month_file = $data_dir . '/' . date('Y-m') . '.json';
$messages = [];
if (file_exists($month_file)) {
    $json_data = @file_get_contents($month_file);
    if ($json_data) $messages = json_decode($json_data, true) ?: [];
}

$new_msg = [
    'id' => uniqid('msg_'),
    'name' => $name,
    'email' => $email,
    'message' => $message,
    'date' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'read' => false
];

array_unshift($messages, $new_msg);
@file_put_contents($month_file, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

if (empty($cfg_smtp_host) || empty($cfg_contact_email)) {
    echo json_encode(['success' => true, 'message' => 'Your message has been securely sent.']);
    exit;
}

require_once $root_dir . '/inc/smtp.php';
$mailer = new SimpleSMTP($cfg_smtp_host, $cfg_smtp_port, $cfg_smtp_user, $cfg_smtp_pass, $cfg_smtp_secure);

try {
    $body = "<h2>New Contact Message from " . htmlspecialchars($cfg_name) . "</h2>";
    $body .= "<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>";
    $body .= "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
    $body .= "<p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";

    $from_email = !empty($cfg_mail_from_email) ? $cfg_mail_from_email : ((!empty($cfg_smtp_user) && strpos($cfg_smtp_user, '@') !== false) ? $cfg_smtp_user : $cfg_contact_email);
    $from_name = !empty($cfg_mail_from_name) ? $cfg_mail_from_name : $cfg_name;

    $mailer->send(
        $cfg_contact_email,
        "New Contact Form Submission - " . $name,
        $body,
        $from_email,
        $from_name
    );
    echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => true, 'message' => 'Your message has been securely archived.']);
}
exit;
