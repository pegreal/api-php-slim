<?php
namespace Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService {
    private $mailer;
    private $mailConfig;

    public function __construct( array $mailConfig ) {

        $this->mailConfig = $mailConfig;

        $this->mailer = new PHPMailer(true);

        // Configuración inicial de PHPMailer
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->mailConfig['host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $this->mailConfig['username'];
        $this->mailer->Password = $this->mailConfig['password'];
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mailer->Port = $this->mailConfig['port']; 

        // Configuración por defecto
        $this->mailer->setFrom($this->mailConfig['mail'], $this->mailConfig['name']);
        $this->mailer->isHTML(true);
    }

    public function sendEmail($to, $subject, $body, $attachmentName = null, $attachmentContent = null,) {
        try {
            // Destinatario
            $this->mailer->addAddress($to);

            // Asunto y cuerpo del correo
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $body;

            // Si hay un archivo adjunto, agregarlo
            if ($attachmentContent && $attachmentName) {
                // Añadir el archivo adjunto desde una cadena de texto (el contenido del PDF)
                $this->mailer->addStringAttachment($attachmentContent, $attachmentName, 'base64', 'application/pdf');
            }

            // Enviar el correo
            $this->mailer->send();

            return [
                'status' => 'success',
                'message' => 'Correo enviado correctamente'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => "No se pudo enviar el correo. Error: {$this->mailer->ErrorInfo}"
            ];
        }
    }
}