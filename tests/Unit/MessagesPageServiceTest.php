<?php

namespace Tests\Unit;

use App\Services\MessagesPageService;
use Tests\TestCase;

class MessagesPageServiceTest extends TestCase
{
    public function test_it_generates_and_resolves_direct_conversation_tokens(): void
    {
        $service = app(MessagesPageService::class);

        $token = $service->conversationToken('direct', 42);
        $tokenFromKey = $service->conversationTokenFromKey('direct:42');

        $this->assertNotSame('direct:42', $token);
        $this->assertSame('direct:42', $service->conversationKeyFromToken($token));
        $this->assertSame('direct:42', $service->conversationKeyFromToken($tokenFromKey));
    }

    public function test_it_generates_and_resolves_group_conversation_tokens(): void
    {
        $service = app(MessagesPageService::class);

        $token = $service->conversationToken('group', 77);
        $tokenFromKey = $service->conversationTokenFromKey('group:77');

        $this->assertNotSame('group:77', $token);
        $this->assertSame('group:77', $service->conversationKeyFromToken($token));
        $this->assertSame('group:77', $service->conversationKeyFromToken($tokenFromKey));
    }

    public function test_it_returns_null_for_invalid_conversation_tokens(): void
    {
        $service = app(MessagesPageService::class);

        $this->assertNull($service->conversationKeyFromToken('invalid-token'));
        $this->assertNull($service->conversationTokenFromKey('nope'));
    }
}
