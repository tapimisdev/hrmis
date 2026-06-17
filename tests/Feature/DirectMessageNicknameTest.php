<?php

namespace Tests\Feature;

use App\Events\DirectConversationInfoUpdated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DirectMessageNicknameTest extends TestCase
{
    use RefreshDatabase;

    public function test_clearing_a_direct_message_nickname_deletes_the_saved_setting(): void
    {
        $authUser = User::factory()->create();
        $partner = User::factory()->create();

        DB::table('direct_conversation_settings')->insert([
            'user_id' => $authUser->id,
            'partner_id' => $partner->id,
            'nickname' => 'Project Lead',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Event::fake([DirectConversationInfoUpdated::class]);

        $response = $this->actingAs($authUser, 'sanctum')->postJson(
            "/api/direct-messages/{$partner->id}/info",
            ['nickname' => '   '],
        );

        $response
            ->assertOk()
            ->assertJsonPath('conversation.nickname', null)
            ->assertJsonPath('conversation.name', $partner->name)
            ->assertJsonPath('conversation.actual_name', $partner->name);

        $this->assertDatabaseMissing('direct_conversation_settings', [
            'user_id' => $authUser->id,
            'partner_id' => $partner->id,
        ]);
        Event::assertDispatched(DirectConversationInfoUpdated::class);
    }
}
