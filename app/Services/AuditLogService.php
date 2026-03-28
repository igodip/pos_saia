<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function record(string $eventType, Model $auditable, ?int $userId, array $payload = []): AuditLog
    {
        return $auditable->auditLogs()->create([
            'event_type' => $eventType,
            'user_id' => $userId,
            'payload_json' => $payload,
            'created_at' => now(),
        ]);
    }
}
