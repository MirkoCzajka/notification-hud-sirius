<?php

namespace App\Services;

use GuzzleHttp\Client;

class DiscordService
{
    private Client $http;
    private string $token;

    public function __construct(string $endpoint)
    {
        $this->token = env('DISCORD_BOT_TOKEN');
        $this->http  = new Client([
            'base_uri'    => $endpoint,
            'http_errors' => false,
            'timeout'     => 10,
        ]);
    }

    public function postMessage(string $text): array
    {
        $channelId = env('DISCORD_CHANNEL_ID');

        $response = $this->http->post("channels/{$channelId}/messages", [
            'headers' => [
                'Authorization' => "Bot {$this->token}",
                'Content-Type'  => 'application/json',
            ],
            'json' => ['content' => $text],
        ]);

        $status = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);
        $ok = ($status === 200 && isset($body['id']));

        $out = [
            'ok'        => $ok,
            'status'    => $status,
            'provider'  => $body,
        ];

        return $out;
    }
}
