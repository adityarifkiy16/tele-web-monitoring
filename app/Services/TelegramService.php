<?php

namespace App\Services;

class TelegramService
{
    protected $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
    }

    public function sendMessage($chatId, $message)
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        $params = [
            'chat_id' => $chatId,
            'text' => $message,
        ];

        // Use Guzzle or any HTTP client to send the request
        $client = new \GuzzleHttp\Client();
        $response = $client->post($url, [
            'form_params' => $params,
        ]);

        return json_decode($response->getBody(), true);
    }
}
