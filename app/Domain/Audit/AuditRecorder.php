<?php

namespace App\Domain\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditRecorder
{
    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
    public function record(string $action, ?Model $target = null, ?array $before = null, ?array $after = null, ?User $actor = null, ?Request $request = null): AuditLog
    {
        $request ??= app()->bound('request') ? request() : null;

        return AuditLog::query()->create([
            'user_id' => $actor?->id ?? $request?->user()?->id,
            'action' => $action,
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->getKey(),
            'before_values' => $this->sanitize($before),
            'after_values' => $this->sanitize($after),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? mb_substr($request->userAgent(), 0, 1000) : null,
            'created_at' => now(),
        ]);
    }

    /** @param array<string, mixed>|null $values @return array<string, mixed>|null */
    private function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }
        foreach ($values as $key => $value) {
            if (preg_match('/password|token|secret|recovery|hash/i', (string) $key)) {
                $values[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $values[$key] = $this->sanitize($value);
            } elseif (is_string($value) && mb_strlen($value) > 2000) {
                $values[$key] = mb_substr($value, 0, 2000).'…';
            }
        }

        return $values;
    }
}
