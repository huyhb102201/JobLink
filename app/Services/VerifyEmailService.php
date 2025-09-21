<?php

namespace App\Services;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use SendGrid\Mail\Mail as SGMail;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class VerifyEmailService
{
    public function send(MustVerifyEmail $user): void
    {
        // URL xác minh (hết hạn 60 phút)
        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
        );

        $fromAddress = Config::get('mail.from.address', 'no-reply@example.com');
        $fromName    = Config::get('mail.from.name', config('app.name'));
        $toAddress   = $user->getEmailForVerification();
        $toName      = $user->name ?? 'Người dùng';

        $subject = 'Xác minh địa chỉ email của bạn';
        $html = <<<HTML
<p>Xin chào {$toName},</p>
<p>Vui lòng nhấn nút bên dưới để xác minh email của bạn:</p>
<p><a href="{$verifyUrl}" style="display:inline-block;background:#4f46e5;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;">Xác minh email</a></p>
<p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email.</p>
<p>Trân trọng,<br>{$fromName}</p>
HTML;

        $text = "Xin chào {$toName},\n\n"
              . "Hãy mở link sau để xác minh email: {$verifyUrl}\n\n"
              . "Nếu bạn không yêu cầu, hãy bỏ qua.\n"
              . "— {$fromName}";

        $mail = new SGMail();
        $mail->setFrom($fromAddress, $fromName);
        $mail->setSubject($subject);
        $mail->addTo($toAddress, $toName);
        $mail->addContent('text/plain', $text);
        $mail->addContent('text/html', $html);

        $sg = new \SendGrid(env('SENDGRID_API_KEY'));
        $sg->send($mail); // Status 202 = gửi thành công vào hàng đợi SendGrid
    }
}
