<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TelegramService;

class TelegramController extends Controller
{
    public function webhook(Request $request, TelegramService $telegramService)
    {
        // Handle incoming Telegram webhook updates here
        $message = $request->input('message');
        if (!$message) return response()->json();

        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $args = explode(' ', $text);

        $command = $args[0] ?? '';
        $url = $args[1] ?? '';

        match ($command) {
            '/start' => $telegramService->sendMessage($chatId, "Welcome to the Website Monitor Bot! Use /add <url> to monitor a website."),
            '/add' => $this->addWebsite($chatId, $url, $telegramService),
            '/remove' => $this->removeWebsite($chatId, $url, $telegramService),
            '/list' => $this->listWebsites($chatId, $telegramService),
            default => $telegramService->sendMessage($chatId, "Unknown command. Available commands: /add, /remove, /list"),
        };
    }

    public function addWebsite($chatId, $url, TelegramService $telegramService)
    {
        if (empty($url)) {
            $telegramService->sendMessage($chatId, "Please provide a URL to add.");
            return;
        }

        \App\Models\Website::firstOrCreate(
            ['url' => $url, 'chat_id' => $chatId],
            ['status' => 'up']
        );

        $telegramService->sendMessage($chatId, "Website $url added for monitoring.");
    }

    public function removeWebsite($chatId, $url, TelegramService $telegramService)
    {
        if (empty($url)) {
            $telegramService->sendMessage($chatId, "Please provide a URL to remove.");
            return;
        }

        \App\Models\Website::where('url', $url)->where('chat_id', $chatId)->delete();

        $telegramService->sendMessage($chatId, "Website $url removed from monitoring.");
    }

    public function listWebsites($chatId, TelegramService $telegramService)
    {
        $websites = \App\Models\Website::where('chat_id', $chatId)->get();

        if ($websites->isEmpty()) {
            $telegramService->sendMessage($chatId, "No websites are being monitored.");
            return;
        }

        $message = "Monitored Websites:\n";
        foreach ($websites as $website) {
            $message .= "- {$website->url} (Status: {$website->status})\n";
        }

        $telegramService->sendMessage($chatId, $message);
    }
}
