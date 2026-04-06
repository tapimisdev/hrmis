<?php

namespace Tests\Unit;

use App\Models\DirectMessage;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class DirectMessageEncryptionTest extends TestCase
{
    public function test_body_is_encrypted_in_storage_but_decrypted_on_access(): void
    {
        $message = new DirectMessage();
        $message->body = 'secret message';

        $this->assertSame('secret message', $message->body);
        $this->assertNotSame('secret message', $message->getAttributes()['body']);
        $this->assertSame(
            'secret message',
            Crypt::decryptString($message->getAttributes()['body'])
        );
    }
}
