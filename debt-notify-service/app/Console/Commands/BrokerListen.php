<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class BrokerListen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broker:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to events from Redis queue and create notifications';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $queueName = env('NOTIFICATIONS_QUEUE', 'queue:notifications');
        $this->info("Broker listener started. Listening on queue: {$queueName}");

        while (true) {
            try {
                // BLPOP blocks for up to 5 seconds waiting for a new event in the queue
                $result = Redis::command('BLPOP', [$queueName, 5]);

                if ($result && is_array($result) && isset($result[1])) {
                    $rawPayload = $result[1];
                    $this->info("Received event: " . $rawPayload);
                    
                    $payload = json_decode($rawPayload, true);

                    if (!$payload || !isset($payload['event'])) {
                        $this->error("Invalid payload structure.");
                        continue;
                    }

                    $this->processEvent($payload);
                }
            } catch (\Throwable $e) {
                $this->error("Error in listener loop: " . $e->getMessage());
                // Avoid tight loop / high CPU usage in case of persistent Redis disconnects
                sleep(2);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Process a popped event.
     *
     * @param array $payload
     * @return void
     */
    private function processEvent(array $payload)
    {
        try {
            $event = $payload['event'] ?? '';
            $userId = $payload['user_id'] ?? null;

            if (!$userId) {
                $this->error("Missing user_id in event payload.");
                return;
            }

            $title = '';
            $message = '';
            $type = 'info';

            switch ($event) {
                case 'transaction.created':
                    $trxType = $payload['type'] ?? 'transaksi';
                    $amount = $payload['amount'] ?? 0;
                    // Producer (transaction-service) mengirim type 'income' / 'expense'
                    $typeStr = ($trxType === 'income') ? 'Pemasukan' : 'Pengeluaran';
                    $formattedAmount = number_format($amount, 0, ',', '.');

                    $title = 'Transaksi Baru';
                    $message = "{$typeStr} Rp {$formattedAmount} tercatat.";
                    $type = ($trxType === 'income') ? 'success' : 'info';
                    break;

                case 'transfer.completed':
                    $amount = $payload['amount'] ?? 0;
                    $formattedAmount = number_format($amount, 0, ',', '.');
                    
                    $title = 'Transfer Berhasil';
                    $message = "Transfer Rp {$formattedAmount} berhasil.";
                    $type = 'success';
                    break;

                case 'debt.payment_made':
                    $amount = $payload['amount'] ?? 0;
                    $sisa = $payload['sisa'] ?? 0;
                    $formattedAmount = number_format($amount, 0, ',', '.');
                    $formattedSisa = number_format($sisa, 0, ',', '.');
                    
                    $title = 'Pembayaran Utang';
                    $message = "Pembayaran utang Rp {$formattedAmount}, sisa Rp {$formattedSisa}.";
                    $type = 'info';
                    break;

                default:
                    $this->warn("Unknown event type: " . $event);
                    return;
            }

            Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'read_at' => null,
            ]);

            $this->info("Successfully processed notification for user {$userId}: {$message}");

        } catch (\Throwable $e) {
            $this->error("Failed to process event details: " . $e->getMessage());
        }
    }
}
