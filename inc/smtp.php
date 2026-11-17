<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

class SimpleSMTP {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $secure; // 'ssl', 'tls', or ''
    private $connection;
    private $timeout = 10;
    private $debug = false;
    private $log = [];

    public function __construct($host, $port, $user, $pass, $secure = '') {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->secure = strtolower($secure);
    }

    public function setDebug($debug) {
        $this->debug = $debug;
    }

    public function getLog() {
        return implode("\n", $this->log);
    }

    private function log($msg) {
        if ($this->debug) {
            $this->log[] = $msg;
        }
    }

    private function sendCommand($cmd, $expectCode) {
        $this->log("> " . trim($cmd));
        fwrite($this->connection, $cmd . "\r\n");
        $response = '';
        while ($line = fgets($this->connection, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        $this->log("< " . trim($response));
        if (substr($response, 0, 3) != $expectCode) {
            throw new Exception("Expected code $expectCode but got: $response");
        }
    }

    public function send($to, $subject, $message, $fromEmail, $fromName) {
        $protocol = ($this->secure == 'ssl') ? 'ssl://' : '';
        $this->connection = @fsockopen($protocol . $this->host, $this->port, $errno, $errstr, $this->timeout);
        
        if (!$this->connection) {
            throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
        }

        $res = fgets($this->connection, 515);
        $this->log("< " . trim($res));

        try {
            $domain = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
            $this->sendCommand("EHLO $domain", "250");
            
            if ($this->secure == 'tls') {
                $this->sendCommand("STARTTLS", "220");
                stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->sendCommand("EHLO $domain", "250");
            }

            if (!empty($this->user)) {
                $this->sendCommand("AUTH LOGIN", "334");
                $this->sendCommand(base64_encode($this->user), "334");
                $this->sendCommand(base64_encode($this->pass), "235");
            }

            $this->sendCommand("MAIL FROM:<$fromEmail>", "250");
            $this->sendCommand("RCPT TO:<$to>", "250");
            $this->sendCommand("DATA", "354");

            // Format body to handle lines starting with dot
            $message = preg_replace('/^\./m', '..', $message);

            $headers = [];
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=utf-8";
            $headers[] = "Date: " . date("r");
            $headers[] = "From: =?utf-8?B?" . base64_encode($fromName) . "?= <$fromEmail>";
            $headers[] = "To: <$to>";
            $headers[] = "Subject: =?utf-8?B?" . base64_encode($subject) . "?=";
            
            $body = implode("\r\n", $headers) . "\r\n\r\n" . $message . "\r\n.";
            
            $this->sendCommand($body, "250");
            $this->sendCommand("QUIT", "221");
            
        } catch (Exception $e) {
            fclose($this->connection);
            throw $e;
        }

        fclose($this->connection);
        return true;
    }
}
