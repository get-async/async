<?php

declare(strict_types=1);

use App\Enums\EmailType;
use App\Jobs\SendEmail;
use App\Mail\ApiKeyDestroyed;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

it('sends email the traditional way', function (): void {
    Config::set('async.use_resend', false);
    Config::set('mail.from.address', 'noreply@example.com');
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'michael.scott@dundermifflin.com',
    ]);

    $job = new SendEmail(
        emailType: EmailType::API_DESTROYED,
        user: $user,
        parameters: ['label' => '123'],
    );

    $job->handle();

    Mail::assertQueued(ApiKeyDestroyed::class, function (ApiKeyDestroyed $mail) use ($user): bool {
        return $mail->hasTo($user->email) &&
            $mail->label === '123';
    });

    $emailSent = EmailSent::latest()->first();
    expect($emailSent->email_type)->toBe(EmailType::API_DESTROYED->value);
    expect($emailSent->email_address)->toBe('michael.scott@dundermifflin.com');
    expect($emailSent->subject)->toBe('API key removed');
});
