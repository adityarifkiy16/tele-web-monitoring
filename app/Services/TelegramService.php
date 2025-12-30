<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

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
        Http::post($url, $params);
    }

    public function handleIncomingMessage($chatId, $text)
    {
        $args = preg_split('/\s+/', $text);
        $command = $args[0] ?? '';
        $url = $args[1] ?? '';

        match ($command) {
            '/start' => $this->sendMessage($chatId, "Welcome to the Website Monitor Bot! Use /add <url> to monitor a website."),
            '/add' => $this->addWebsite($chatId, $url),
            '/remove' => $this->removeWebsite($chatId, $url),
            '/list' => $this->listWebsites($chatId),
            default => $this->sendMessage($chatId, "Unknown command. Available commands: /add, /remove, /list"),
        };
    }

    private function addWebsite($chatId, $url)
    {
        if (empty($url)) {
            $this->sendMessage($chatId, "Please provide a URL to add.");
            return;
        }

        \App\Models\Website::firstOrCreate(
            ['url' => $url, 'chat_id' => $chatId],
            ['status' => 'up']
        );

        $this->sendMessage($chatId, "Website $url added for monitoring.");
    }

    private function removeWebsite($chatId, $url)
    {
        if (empty($url)) {
            $this->sendMessage($chatId, "Please provide a URL to remove.");
            return;
        }

        \App\Models\Website::where('url', $url)->where('chat_id', $chatId)->delete();

        $this->sendMessage($chatId, "Website $url removed from monitoring.");
    }

    private function listWebsites($chatId)
    {
        $websites = \App\Models\Website::where('chat_id', $chatId)->get();

        if ($websites->isEmpty()) {
            $this->sendMessage($chatId, "No websites are being monitored.");
            return;
        }
        $message = "Monitored Websites:\n";
        foreach ($websites as $website) {
            $status = $website->status === 'up' ? '✅' : '⚠️';
            $message .= "- {$website->url} (Status: {$status})\n";
        }

        $this->sendMessage($chatId, $message);
    }
}
