<?php

namespace Tests\Unit;

use App\Models\Note;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class NoteEncryptionTest extends TestCase
{
    public function test_note_title_and_content_are_encrypted_in_storage(): void
    {
        $note = new Note();
        $note->title = 'Private title';
        $note->content = 'Private content';
        $note->hasPin = false;

        $this->assertSame('Private title', $note->title);
        $this->assertSame('Private content', $note->content);
        $this->assertNotSame('Private title', $note->getAttributes()['title']);
        $this->assertNotSame('Private content', $note->getAttributes()['content']);
        $this->assertSame('Private title', Crypt::decryptString($note->getAttributes()['title']));
        $this->assertSame('Private content', Crypt::decryptString($note->getAttributes()['content']));
    }
}
