<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $evidence_id
 * @property int $version_number
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property string $extension
 * @property int $size
 * @property string $checksum
 * @property string|null $change_note
 * @property int $uploaded_by
 * @property CarbonImmutable|null $created_at
 * @property-read Evidence $evidence
 * @property-read User $uploader
 */
class EvidenceVersion extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $hidden = ['disk', 'path'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'version_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Evidence, $this>
     */
    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
