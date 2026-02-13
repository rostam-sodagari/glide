<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function ($notifiable, string $token): string {
            $baseUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
            $email = urlencode($notifiable->getEmailForPasswordReset());

            return $baseUrl.'/reset-password?token='.$token.'&email='.$email;
        });

        VerifyEmail::createUrlUsing(function ($notifiable): string {
            $baseUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
            $id = $notifiable->getKey();
            $hash = sha1($notifiable->getEmailForVerification());

            return $baseUrl.'/verify-email/'.$id.'/'.$hash;
        });
    }
}
