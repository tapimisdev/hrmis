<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notes')
            ->select('id', 'title', 'content')
            ->orderBy('id')
            ->chunkById(100, function ($notes) {
                foreach ($notes as $note) {
                    DB::table('notes')
                        ->where('id', $note->id)
                        ->update([
                            'title' => Crypt::encryptString((string) $note->title),
                            'content' => Crypt::encryptString((string) $note->content),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('notes')
            ->select('id', 'title', 'content')
            ->orderBy('id')
            ->chunkById(100, function ($notes) {
                foreach ($notes as $note) {
                    DB::table('notes')
                        ->where('id', $note->id)
                        ->update([
                            'title' => Crypt::decryptString((string) $note->title),
                            'content' => Crypt::decryptString((string) $note->content),
                        ]);
                }
            });
    }
};
