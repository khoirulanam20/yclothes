<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotEmbedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_widget_version_proxy_returns_upstream_version(): void
    {
        Http::fake([
            'https://chatbot.firstudio.id/chatbot-widget.ver' => Http::response("abc123\n", 200),
        ]);

        $this->get(route('embed.chatbot.version'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSeeText('abc123');
    }

    public function test_sanitized_body_scripts_remove_chatbot_loader(): void
    {
        Setting::updateOrCreate(['key' => 'custom_body_scripts'], [
            'value' => '<script src="https://chatbot.firstudio.id/chatbot.js" data-bot-id="bot-1" defer></script><!-- other -->',
        ]);

        $sanitized = sanitized_custom_body_scripts();

        $this->assertSame('<!-- other -->', $sanitized);
        $this->assertSame('bot-1', chatbot_bot_id());
    }

    public function test_chatbot_bot_id_prefers_setting_over_legacy_script(): void
    {
        Setting::updateOrCreate(['key' => 'chatbot_bot_id'], ['value' => 'bot-99']);
        Setting::updateOrCreate(['key' => 'custom_body_scripts'], [
            'value' => '<script src="https://chatbot.firstudio.id/chatbot.js" data-bot-id="bot-legacy" defer></script>',
        ]);

        $this->assertSame('bot-99', chatbot_bot_id());
    }
}
