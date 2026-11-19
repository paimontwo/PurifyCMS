<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'img' => NywhashCaptcha::getImageBase64()]);
exit;
