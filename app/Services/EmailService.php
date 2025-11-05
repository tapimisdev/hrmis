<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class EmailService
{
    /**
     * Send an email with view-based body and optional attachments.
     *
     * @param  array  $emails
     * @param  string  $title
     * @param  string  $viewPath  Blade view path, e.g. 'emails.job-offer'
     * @param  array  $viewData   Data passed to the view
     * @param  array  $attachments
     * @return bool
     */
    public static function send(array $emails, string $title, string $viewPath, array $viewData = [], array $attachments = []): bool
    {
        try {
            Mail::send($viewPath, $viewData, function (Message $message) use ($emails, $title, $attachments) {
                $message->to($emails)->subject($title);

                foreach ($attachments as $file) {
                    if (file_exists($file)) {
                        $message->attach($file);
                    }
                }
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }
}
