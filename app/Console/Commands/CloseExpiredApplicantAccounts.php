<?php

namespace App\Console\Commands;

use App\Models\ApplicantProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloseExpiredApplicantAccounts extends Command
{
    protected $signature = 'recruitment:close-expired-applicants';
    protected $description = 'Soft-delete applicant portal profiles one month after hiring.';

    public function handle(): int
    {
        ApplicantProfile::whereNotNull('close_account_at')
            ->where('close_account_at', '<=', now())
            ->whereNull('deleted_at')
            ->chunkById(100, function ($profiles) {
                foreach ($profiles as $profile) {
                    DB::transaction(function () use ($profile) {
                        $profile->delete();
                        $profile->user?->tokens()->delete();
                    });
                }
            });

        return self::SUCCESS;
    }
}
