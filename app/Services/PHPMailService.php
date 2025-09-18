<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PHPMailService
{
    public function sendResetLink($to, $token)
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = config('mail.mailers.smtp.host');
            $mail->SMTPAuth   = true;
            $mail->Username   = config('mail.mailers.smtp.username');
            $mail->Password   = config('mail.mailers.smtp.password');
            $mail->SMTPSecure = config('mail.mailers.smtp.encryption', 'tls');
            $mail->Port       = config('mail.mailers.smtp.port');

            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Password';

            $resetUrl = env('FRONTEND_URL') . '/reset-password?token=' . $token . '&email=' . $to;

            $mail->Body = "
                <h3>Reset Password</h3>
                <p>Click the link below to reset your password:</p>
                <a href='{$resetUrl}'>Reset Password</a>
                <p>This link will expire in 10 minutes.</p>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            return $mail->ErrorInfo;
        }
    }
}
