<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $config;

    public function __construct($config) {
        $this->config = $config;
    }

    public function sendReport($date, $total, $htmlContent) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['user'];
            $mail->Password   = $this->config['pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($this->config['user'], "Nouvelles Entreprises");
            $mail->addAddress($this->config['dest']);

            $mail->isHTML(true);
            $mail->Subject = "Rapport INSEE : $total nouvelles entreprises du $date(Aude)";
            $mail->Body    = $htmlContent;

            return $mail->send();
        } catch (Exception $e) {
            error_log("Erreur Mail : " . $mail->ErrorInfo);
            return false;
        }
    }
}