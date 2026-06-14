<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class BackupController extends Controller
{
    public function index()
    {
        $backups = Backup::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.backups.index', compact('backups'));
    }

    public function store(Request $request)
    {
        // Try mysqldump first
        $fileName = 'backup_' . now()->format('Ymd_His') . '.sql';
        $filePath = 'backups/' . $fileName;

        $db = config('database.connections.'.config('database.default'));
        $user = $db['username'] ?? env('DB_USERNAME');
        $pass = $db['password'] ?? env('DB_PASSWORD');
        $dbName = $db['database'] ?? env('DB_DATABASE');
        $host = $db['host'] ?? env('DB_HOST');

        $dumpSuccess = false;

        // Attempt mysqldump if available
        $cmd = null;
        if (str_contains($db['driver'] ?? '', 'mysql') || env('DB_CONNECTION') === 'mysql') {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows - use simple mysqldump command (requires mysqldump in PATH)
                $cmd = "mysqldump --user=" . escapeshellarg($user) . " --password=" . escapeshellarg($pass) . " --host=" . escapeshellarg($host) . " " . escapeshellarg($dbName);
            } else {
                $cmd = "mysqldump --user=" . escapeshellarg($user) . " --password=" . escapeshellarg($pass) . " --host=" . escapeshellarg($host) . " " . escapeshellarg($dbName);
            }
        }

        if ($cmd) {
            try {
                $output = null;
                $returnVar = null;
                exec($cmd, $output, $returnVar);
                if ($returnVar === 0 && !empty($output)) {
                    $sql = implode("\n", $output);
                    Storage::put($filePath, $sql);
                    $dumpSuccess = true;
                }
            } catch (\Throwable $e) {
                // fallback below
            }
        }

        // Fallback: basic sql generator for small DBs
        if (! $dumpSuccess) {
            $sql = '';
            $tables = DB::select('SHOW TABLES');
            $key = 'Tables_in_' . $dbName;
            foreach ($tables as $t) {
                $table = $t->$key;
                $rows = DB::table($table)->get();
                $sql .= "-- Dump table {$table}\n";
                $sql .= "TRUNCATE TABLE `{$table}`;\n";
                foreach ($rows as $row) {
                    $cols = array_map(function($c){return "`$c`";}, array_keys((array)$row));
                    $vals = array_map(function($v){ return is_null($v) ? 'NULL' : "'".str_replace("'","\\'",(string)$v)."'"; }, (array)$row);
                    $sql .= "INSERT INTO `{$table}` (".implode(',', $cols).") VALUES (".implode(',', $vals).");\n";
                }
                $sql .= "\n";
            }
            Storage::put($filePath, $sql);
        }

        $size = Storage::size($filePath);

        $backup = Backup::create([
            'filename' => $fileName,
            'path' => $filePath,
            'size' => $size,
            'created_by' => Auth::id(),
        ]);

        // optionally email to recipients
        $recipients = explode(',', config('app.backup_recipients') ?? env('ADMIN_BACKUP_EMAILS', ''));
        $recipients = array_filter(array_map('trim', $recipients));
        if (!empty($recipients)) {
            try {
                foreach ($recipients as $r) {
                    Mail::raw('Backup terlampir: ' . $fileName, function($m) use ($r, $filePath, $fileName){
                        $m->to($r)->subject('Backup Database ' . config('app.name'));
                        $m->attach(Storage::path($filePath), ['as' => $fileName]);
                    });
                }
            } catch (\Throwable $e) {
                // ignore email failures for now
            }
        }

        return back()->with('success', 'Backup dibuat: ' . $fileName);
    }

    public function download(Backup $backup)
    {
        if (!Storage::exists($backup->path)) {
            return back()->with('error', 'File backup tidak ditemukan.');
        }
        return Storage::download($backup->path, $backup->filename);
    }

    public function destroy(Backup $backup)
    {
        if (Storage::exists($backup->path)) Storage::delete($backup->path);
        $backup->delete();
        return back()->with('success','Backup dihapus.');
    }

    public function restore(Request $request)
    {
        $request->validate([ 'sql_file' => 'required|file|mimes:sql,txt|max:10240' ]);

        $file = $request->file('sql_file');
        $contents = file_get_contents($file->getRealPath());

        try {
            DB::unprepared($contents);
            return back()->with('success', 'Restore berhasil.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Restore gagal: ' . $e->getMessage());
        }
    }
}
