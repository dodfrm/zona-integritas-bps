<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Writes append-only audit records for important business actions.
 */
class AuditLogger
{
    /** Keys that must never reach the audit table. */
    private const SENSITIVE = [
        'password', 'password_confirmation', 'current_password', 'remember_token', 'token',
        'two_factor_secret', 'two_factor_recovery_codes', 'secret', 'api_key', 'path',
    ];

    private const IGNORED = ['created_at', 'updated_at'];

    public function __construct(private readonly Request $request) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(
        string $event,
        string $description,
        ?Model $subject = null,
        ?int $organizationId = null,
        array $properties = [],
    ): AuditLog {
        $user = $this->request->user();

        return AuditLog::create([
            'user_id' => $user?->getKey(),
            'organization_id' => $organizationId ?? $this->organizationOf($subject),
            'event' => $event,
            'auditable_type' => $subject ? class_basename($subject) : null,
            'auditable_id' => $subject?->getKey(),
            'description' => Str::limit($description, 495),
            'properties' => $properties === [] ? null : $this->sanitize($properties),
            'ip_address' => $this->request->ip(),
            'user_agent' => Str::limit((string) $this->request->userAgent(), 250, ''),
        ]);
    }

    /**
     * Field-level changes of a model's last save, as [field => [old, new]].
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function changesOf(Model $model): array
    {
        $previous = $model->getPrevious();
        $changes = [];

        foreach ($model->getChanges() as $key => $value) {
            if (in_array($key, self::IGNORED, true)) {
                continue;
            }

            $changes[$key] = ['old' => $previous[$key] ?? null, 'new' => $value];
        }

        return $changes;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(Str::lower($key), self::SENSITIVE, true)) {
                unset($data[$key]);

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }

    private function organizationOf(?Model $subject): ?int
    {
        $organizationId = $subject?->getAttribute('organization_id');

        return is_numeric($organizationId) ? (int) $organizationId : null;
    }
}
