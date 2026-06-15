<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log($actorUserId = null, string $action, $target = null, string $description = null, array $meta = [])
    {
        $entry = [
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
        ];

        if ($target) {
            if (is_object($target)) {
                $entry['target_type'] = get_class($target);
                $entry['target_id'] = $target->id ?? null;
            } elseif (is_array($target)) {
                $entry['target_type'] = $target['type'] ?? null;
                $entry['target_id'] = $target['id'] ?? null;
            } else {
                $entry['target_type'] = (string) $target;
            }
        }

        if ($meta) {
            $entry['description'] = trim(($entry['description'] ?? '') . ' ' . json_encode($meta));
        }

        return ActivityLog::create($entry);
    }
}