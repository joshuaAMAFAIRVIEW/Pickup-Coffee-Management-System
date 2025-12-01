<?php
/**
 * Simple SMTP Email Sender (No dependencies required)
 * Uses PHP's built-in socket functions to send emails
 */
class SimpleMailer {
    private $host;
    private $port;
    private $username;
    private $password;
    private $from_email;
    private $from_name;
    
    public function __construct($host, $port, $username, $password, $from_email, $from_name) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->from_email = $from_email;
        $this->from_name = $from_name;
    }
    
    public function send($to_email, $subject, $html_body) {
        try {
            // Create message
            $boundary = md5(time());
            
            $headers = "From: {$this->from_name} <{$this->from_email}>\r\n";
            $headers .= "Reply-To: {$this->from_email}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            
            $message = "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $html_body . "\r\n";
            $message .= "--{$boundary}--";
            
            // Connect to SMTP server
            $smtp = fsockopen($this->host, $this->port, $errno, $errstr, 30);
            if (!$smtp) {
                throw new Exception("Failed to connect: $errstr ($errno)");
            }
            
            // Read greeting
            $this->getResponse($smtp);
            
            // Send EHLO
            fputs($smtp, "EHLO {$this->host}\r\n");
            $this->getResponse($smtp);
            
            // Start TLS
            fputs($smtp, "STARTTLS\r\n");
            $this->getResponse($smtp);
            
            stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // Send EHLO again after TLS
            fputs($smtp, "EHLO {$this->host}\r\n");
            $this->getResponse($smtp);
            
            // Authenticate
            fputs($smtp, "AUTH LOGIN\r\n");
            $this->getResponse($smtp);
            
            fputs($smtp, base64_encode($this->username) . "\r\n");
            $this->getResponse($smtp);
            
            fputs($smtp, base64_encode($this->password) . "\r\n");
            $this->getResponse($smtp);
            
            // Send email
            fputs($smtp, "MAIL FROM: <{$this->from_email}>\r\n");
            $this->getResponse($smtp);
            
            fputs($smtp, "RCPT TO: <{$to_email}>\r\n");
            $this->getResponse($smtp);
            
            fputs($smtp, "DATA\r\n");
            $this->getResponse($smtp);
            
            fputs($smtp, "Subject: {$subject}\r\n");
            fputs($smtp, $headers);
            fputs($smtp, "\r\n");
            fputs($smtp, $message);
            fputs($smtp, "\r\n.\r\n");
            $this->getResponse($smtp);
            
            // Quit
            fputs($smtp, "QUIT\r\n");
            fclose($smtp);
            
            return true;
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getResponse($smtp) {
        $response = '';
        while ($line = fgets($smtp, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return $response;
    }
}
?>
