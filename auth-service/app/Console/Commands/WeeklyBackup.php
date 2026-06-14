<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Models\Backup;

class WeeklyBackup extends Command
{
    protected $signature = 'backup:weekly';
    protected $description = 'Run weekly database backup and email to recipients';

    public function handle()
    {
        $this->info('Starting weekly backup...');

        // call the BackupController logic crudely via an artisan call or replicate minimal steps
        try {
            // Attempt to run mysqldump similar to controller
            $db = config('database.connections.'.config('database.default'));
            $user = $db['username'] ?? env('DB_USERNAME');
            $pass = $db['password'] ?? env('DB_PASSWORD');
            $dbName = $db['database'] ?? env('DB_DATABASE');
            $host = $db['host'] ?? env('DB_HOST');

            $fileName = 'backup_' . now()->format('Ymd_His') . '.sql';
            $filePath = 'backups/' . $fileName;

            $cmd = "mysqldump --user=" . escapeshellarg($user) . " --password=" . escapeshellarg($pass) . " --host=" . escapeshellarg($host) . " " . escapeshellarg($dbName);
            $output = null; $returnVar = null;
            exec($cmd, $output, $returnVar);
            if ($returnVar === 0 && !empty($output)) {
                $sql = implode("\n", $output);
                Storage::put($filePath, $sql);
            } else {
                // fallback not implemented here for brevity
                $this->error('mysqldump failed.');
                return 1;
            }

            // save record
            $backup = Backup::create(['filename'=>$fileName,'path'=>$filePath,'size'=>Storage::size($filePath)]);

            // email
            $recipients = explode(',', config('app.backup_recipients') ?? env('ADMIN_BACKUP_EMAILS', ''));
            $recipients = array_filter(array_map('trim', $recipients));
            foreach ($recipients as $r) {
                Mail::raw('Weekly backup terlampir: ' . $fileName, function($m) use ($r, $filePath, $fileName){
                    $m->to($r)->subject('Weekly Backup ' . config('app.name'));
                    $m->attach(Storage::path($filePath), ['as' => $fileName]);
                });
            }

            $this->info('Weekly backup completed: ' . $fileName);
            return 0;
        } catch (\Throwable $e) {
            $this->error('Backup error: ' . $e->getMessage());
            return 1;
        }
    }
}
