<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;

class TelegramPoll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:telegram-poll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegramService): void
    {
        $token = config('services.telegram.bot_token');
        $offsetFile = storage_path('app/telegram_offset.txt');

        $offset = file_exists($offsetFile)
            ? (int) file_get_contents($offsetFile)
            : 0;

        $response = Http::timeout(30)->get(
            "https://api.telegram.org/bot{$token}/getUpdates",
            [
                'timeout' => 25,
                'offset'  => $offset + 1,
            ]
        );

        if (!$response->ok()) {
            $this->error('Telegram API error');
            return;
        }

        $updates = $response->json('result', []);

        foreach ($updates as $update) {
            file_put_contents($offsetFile, $update['update_id']);
            $chatId = $update['message']['chat']['id'] ?? null;
            $text = trim($update['message']['text'] ?? '');
            if(!$chatId || !$text) {
                continue;
            }
            $telegramService->handleIncomingMessage($chatId, $text);            
        }
    }
}
