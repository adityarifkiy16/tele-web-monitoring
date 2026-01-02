<?php

namespace App\Console\Commands;

use App\Models\Website;
use Illuminate\Console\Command;
use App\Services\TelegramService;

class MonitorWebsites extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monitor-websites';

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
        Website::all()->each(function ($website) use ($telegramService) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(30)->get($website->url);
                $status = $response->successful() ? 'up' : 'down';
            } catch (\Exception $e) {
                $status = 'down';
            }

            if ($status !== $website->status) {
                $telegramService->sendMessage(
                    $website->chat_id,
                    $status === 'down'
                        ? "⚠️ WEBSITE DOWN!\n{$website->url}"
                        : "✅ WEBSITE UP KEMBALI!\n{$website->url}"
                );
                $website->update(['status' => $status]);
            }
        });
    }
}
