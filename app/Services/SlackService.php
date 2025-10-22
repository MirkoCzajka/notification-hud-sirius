<?php

namespace App\Services;

use GuzzleHttp\Client;

class SlackService
{
    private Client $http;
    private string $token;

    public function __construct(string $endpoint)
    {
        $this->http = new Client(['base_uri' => $endpoint]);
        $this->token = env('SLACK_BOT_TOKEN');
    }

    public function postMessage(string $text): array
    {
        $payload = [
            'channel' => env('SLACK_CHANNEL_ID'),
            'text' => $text
        ];

        $res = $this->http->post('chat.postMessage', [
            'headers' => [
                'Authorization' => "Bearer {$this->token}",
                'Content-Type'  => 'application/json; charset=utf-8',
            ],
            'json' => $payload,
            'http_errors' => false,
        ]);

        return json_decode((string) $res->getBody(), true);
    }
}
