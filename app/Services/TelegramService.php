<?php

namespace App\Services;

use GuzzleHttp\Client;

class TelegramService
{
    private Client $http;
    private string $token;

    public function __construct(string $endpoint)
    {
        $this->token = env('TELEGRAM_BOT_TOKEN', 'TEST_TOKEN');
        $this->http = new Client([
            'base_uri' => $endpoint . "bot{$this->token}/",
            'http_errors' => false,
            'timeout' => 10,
        ]);
    }

    public function postMessage(string $text): array {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $payload = [
            'chat_id' => env('TELEGRAM_CHANNEL_ID'),
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        $response = $this->http->post('sendMessage', ['json' => $payload]);
        return json_decode((string) $response->getBody(), true);
    }
}
