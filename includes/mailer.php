<?php
/**
 * Tulima Solar - SMTP Mailer
 *
 * Centralized SMTP email sending based on settings stored in site_settings.
 * All application emails (contact form, job applications, blog engagement, etc.)
 * should go through these helpers so they respect the admin-configured SMTP.
 */

if (!defined('APP_STARTED')) {
    define('APP_STARTED', true);
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/path.php';

/**
 * Get SMTP settings from site_settings.
 *
 * @param PDO $pdo
 * @return array
 */
function getSmtpSettings(PDO $pdo): array
{
    $keys = [
        'smtp_enabled',
        'smtp_host',
        'smtp_port',
        'smtp_security',
        'smtp_username',
        'smtp_password',
        'smtp_from_email',
        'smtp_from_name',
        'smtp_test_recipient',
        'smtp_contact_recipients',
        'smtp_job_recipients',
    ];

    $settings = [];
    foreach ($keys as $key) {
        $settings[$key] = getSetting($pdo, $key, '');
    }

    $enabled = $settings['smtp_enabled'] === '1' || $settings['smtp_enabled'] === 1;

    return [
        'enabled' => $enabled,
        'host' => trim((string)$settings['smtp_host']),
        'port' => (int)($settings['smtp_port'] ?: 587),
        'security' => in_array($settings['smtp_security'], ['none', 'tls', 'ssl'], true)
            ? $settings['smtp_security']
            : 'tls',
        'username' => (string)$settings['smtp_username'],
        'password' => (string)$settings['smtp_password'],
        'from_email' => (string)$settings['smtp_from_email'],
        'from_name' => (string)$settings['smtp_from_name'] ?: 'Tulima Solar',
        'test_recipient' => (string)$settings['smtp_test_recipient'],
        'contact_recipients' => (string)$settings['smtp_contact_recipients'],
        'job_recipients' => (string)$settings['smtp_job_recipients'],
    ];
}

/**
 * Lightweight SMTP client for sending emails.
 * Supports plain, SSL, and STARTTLS connections with AUTH LOGIN.
 */
class SmtpClient
{
    private string $host;
    private int $port;
    private string $security; // none|tls|ssl
    private ?string $username;
    private ?string $password;
    private int $timeout;
    private $socket = null;
    private string $lastError = '';

    public function __construct(
        string $host,
        int $port = 587,
        string $security = 'tls',
        ?string $username = null,
        ?string $password = null,
        int $timeout = 10
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->security = $security;
        $this->username = $username ?: null;
        $this->password = $password ?: null;
        $this->timeout = $timeout;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Open connection and perform optional TLS + AUTH.
     */
    public function connect(): bool
    {
        $transport = ($this->security === 'ssl') ? 'ssl://' . $this->host : 'tcp://' . $this->host;

        $errno = 0;
        $errstr = '';
        $this->socket = @stream_socket_client(
            $transport . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT
        );

        if (!$this->socket) {
            $this->lastError = "Could not connect to SMTP server: $errstr ($errno)";
            return false;
        }

        stream_set_timeout($this->socket, $this->timeout);

        if (!$this->readExpected(220)) {
            $this->lastError = 'Invalid SMTP greeting';
            return false;
        }

        $clientName = 'localhost';
        if (!empty($_SERVER['SERVER_NAME'])) {
            $clientName = $_SERVER['SERVER_NAME'];
        }

        if (!$this->sendCommand("EHLO " . $clientName, 250)) {
            // Fallback to HELO
            if (!$this->sendCommand("HELO " . $clientName, 250)) {
                $this->lastError = 'EHLO/HELO failed';
                return false;
            }
        }

        if ($this->security === 'tls') {
            if (!$this->sendCommand("STARTTLS", 220)) {
                $this->lastError = 'STARTTLS failed';
                return false;
            }
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $this->lastError = 'Failed to enable TLS encryption';
                return false;
            }
            // Re-issue EHLO after TLS negotiation
            if (!$this->sendCommand("EHLO " . $clientName, 250)) {
                $this->lastError = 'EHLO after STARTTLS failed';
                return false;
            }
        }

        if ($this->username !== null && $this->password !== null) {
            if (!$this->sendCommand("AUTH LOGIN", 334)) {
                $this->lastError = 'AUTH LOGIN not accepted';
                return false;
            }
            if (!$this->sendCommand(base64_encode($this->username), 334)) {
                $this->lastError = 'SMTP username rejected';
                return false;
            }
            if (!$this->sendCommand(base64_encode($this->password), 235)) {
                $this->lastError = 'SMTP password rejected';
                return false;
            }
        }

        return true;
    }

    /**
     * Send email.
     *
     * @param string $fromEmail
     * @param string $fromName
     * @param string $toEmail
     * @param string $subject
     * @param string $htmlBody
     * @param string|null $textBody
     * @return bool
     */
    public function sendMail(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $subject,
        string $htmlBody,
        ?string $textBody = null
    ): bool {
        if (!$this->socket && !$this->connect()) {
            return false;
        }

        $fromEmail = trim($fromEmail);
        $toEmail = trim($toEmail);

        if ($fromEmail === '' || $toEmail === '') {
            $this->lastError = 'From and To addresses are required';
            return false;
        }

        if (!$this->sendCommand("MAIL FROM:<{$fromEmail}>", 250)) {
            $this->lastError = 'MAIL FROM rejected';
            return false;
        }

        if (!$this->sendCommand("RCPT TO:<{$toEmail}>", [250, 251])) {
            $this->lastError = 'RCPT TO rejected';
            return false;
        }

        if (!$this->sendCommand("DATA", 354)) {
            $this->lastError = 'DATA command rejected';
            return false;
        }

        $boundary = 'b_' . bin2hex(random_bytes(16));

        $headers = [];
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $fromHeaderName = $fromName !== '' ? '=?UTF-8?B?' . base64_encode($fromName) . '?=' : $fromEmail;

        $headers[] = "From: {$fromHeaderName} <{$fromEmail}>";
        $headers[] = "To: <{$toEmail}>";
        $headers[] = "Subject: {$encodedSubject}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

        $textBody = $textBody ?? strip_tags($htmlBody);

        $bodyLines = [];
        $bodyLines[] = "--{$boundary}";
        $bodyLines[] = "Content-Type: text/plain; charset=UTF-8";
        $bodyLines[] = "Content-Transfer-Encoding: 8bit";
        $bodyLines[] = "";
        $bodyLines[] = $textBody;
        $bodyLines[] = "";
        $bodyLines[] = "--{$boundary}";
        $bodyLines[] = "Content-Type: text/html; charset=UTF-8";
        $bodyLines[] = "Content-Transfer-Encoding: 8bit";
        $bodyLines[] = "";
        $bodyLines[] = $htmlBody;
        $bodyLines[] = "";
        $bodyLines[] = "--{$boundary}--";
        $bodyLines[] = "";

        $data = implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $bodyLines) . "\r\n.";

        $this->write($data . "\r\n");

        $line = $this->readLine();
        if (!$line || (int)substr($line, 0, 3) !== 250) {
            $this->lastError = 'Message not accepted: ' . trim($line);
            return false;
        }

        return true;
    }

    /**
     * Close connection.
     */
    public function close(): void
    {
        if ($this->socket) {
            $this->sendCommand("QUIT", 221, false);
            fclose($this->socket);
            $this->socket = null;
        }
    }

    private function sendCommand(string $cmd, $expectedCodes, bool $throwOnError = true): bool
    {
        $this->write($cmd . "\r\n");
        $line = $this->readLine();

        if ($line === false || $line === '') {
            if ($throwOnError) {
                $this->lastError = 'No response for command: ' . $cmd;
            }
            return false;
        }

        $code = (int)substr($line, 0, 3);
        $expected = (array)$expectedCodes;
        if (!in_array($code, $expected, true)) {
            if ($throwOnError) {
                $this->lastError = "Unexpected response for '{$cmd}': {$line}";
            }
            return false;
        }

        return true;
    }

    private function readExpected(int $code): bool
    {
        $line = $this->readLine();
        return $line !== false && (int)substr($line, 0, 3) === $code;
    }

    private function write(string $data): void
    {
        if ($this->socket) {
            fwrite($this->socket, $data);
        }
    }

    private function readLine()
    {
        if (!$this->socket) {
            return false;
        }
        $line = fgets($this->socket, 515);
        return $line;
    }
}

/**
 * High-level helper to send an email using configured SMTP settings.
 *
 * @param PDO $pdo
 * @param string $toEmail
 * @param string $subject
 * @param string $htmlBody
 * @param string|null $textBody
 * @return array ['success' => bool, 'error' => string]
 */
function sendSmtpEmail(PDO $pdo, string $toEmail, string $subject, string $htmlBody, ?string $textBody = null): array
{
    $cfg = getSmtpSettings($pdo);

    if (!$cfg['enabled']) {
        // Respect admin choice: SMTP disabled => do not send, but log.
        error_log('SMTP is disabled; email to ' . $toEmail . ' not sent.');
        return ['success' => false, 'error' => 'SMTP is disabled in settings.'];
    }

    if (empty($cfg['host']) || empty($cfg['from_email'])) {
        return ['success' => false, 'error' => 'SMTP host or From email not configured.'];
    }

    $client = new SmtpClient(
        $cfg['host'],
        $cfg['port'],
        $cfg['security'],
        $cfg['username'] ?: null,
        $cfg['password'] ?: null
    );

    if (!$client->connect()) {
        $err = $client->getLastError();
        error_log('SMTP connect error: ' . $err);
        return ['success' => false, 'error' => $err ?: 'Failed to connect to SMTP server'];
    }

    $ok = $client->sendMail(
        $cfg['from_email'],
        $cfg['from_name'],
        $toEmail,
        $subject,
        $htmlBody,
        $textBody
    );

    $err = $client->getLastError();
    $client->close();

    if (!$ok) {
        error_log('SMTP send error: ' . $err);
        return ['success' => false, 'error' => $err ?: 'Failed to send email'];
    }

    return ['success' => true, 'error' => ''];
}

/**
 * Send a test email using current (or provided) SMTP settings.
 *
 * @param PDO $pdo
 * @param string|null $toOverride Optional override recipient
 * @return array
 */
function sendSmtpTestEmail(PDO $pdo, ?string $toOverride = null): array
{
    $cfg = getSmtpSettings($pdo);
    if (!$cfg['enabled']) {
        return ['success' => false, 'error' => 'SMTP is disabled. Enable it first.'];
    }
    $to = $toOverride ?: $cfg['test_recipient'] ?: $cfg['from_email'];
    if (empty($to)) {
        return ['success' => false, 'error' => 'No test recipient configured.'];
    }

    $subject = 'SMTP Test Email - Tulima Solar';
    $siteUrl = rtrim(BASE_PATH, '/') . '/';

    $html = '<p>This is a test email from the Tulima Solar admin panel.</p>'
          . '<p>If you are reading this, your SMTP configuration is working correctly.</p>'
          . '<p>Sent at: <strong>' . date('Y-m-d H:i:s') . '</strong></p>'
          . '<p>Site: <a href="' . htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') . '</a></p>';

    $text = "This is a test email from the Tulima Solar admin panel.\n\n"
          . "If you are reading this, your SMTP configuration is working correctly.\n\n"
          . 'Sent at: ' . date('Y-m-d H:i:s') . "\n"
          . 'Site: ' . $siteUrl . "\n";

    return sendSmtpEmail($pdo, $to, $subject, $html, $text);
}

