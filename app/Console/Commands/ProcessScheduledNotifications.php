<?php

namespace App\Console\Commands;

use App\Jobs\SendScheduledNotification;
use App\Models\Notification;
use Illuminate\Console\Command;

class ProcessScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled notifications that are ready to be sent';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pendingNotifications = Notification::readyToSend()->get();

        $this->info("Found {$pendingNotifications->count()} notifications ready to send");

        foreach ($pendingNotifications as $notification) {
            SendScheduledNotification::dispatch($notification);
            $this->line("Dispatched notification: {$notification->title}");
        }

        $this->info('All scheduled notifications have been queued for processing');
    }
}
