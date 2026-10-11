<?php

namespace App\Models;

use App\Enums\OrganizationType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string|null $code
 * @property string $name
 * @property OrganizationType $type
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Organization|null $parent
 * @property-read Collection<int, Organization> $children
 * @property-read Collection<int, User> $users
 */
#[Fillable(['parent_id', 'code', 'name', 'type', 'is_active'])]
class Organization extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OrganizationType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'parent_id');
    }

    /**
     * @return HasMany<Organization, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Organization::class, 'parent_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Period, $this>
     */
    public function periods(): HasMany
    {
        return $this->hasMany(Period::class);
    }

    /**
     * IDs of this organization and every organization below it.
     *
     * @return list<int>
     */
    public function selfAndDescendantIds(): array
    {
        $ids = [$this->id];
        $frontier = [$this->id];

        // Hierarchy is shallow (pusat > provinsi > kabkota), so a breadth-first walk is cheap.
        while ($frontier !== []) {
            $frontier = Organization::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            $frontier = array_values(array_diff($frontier, $ids));
            $ids = [...$ids, ...$frontier];
        }

        return $ids;
    }

    /**
     * Ancestors from the direct parent up to the root.
     *
     * @return list<Organization>
     */
    public function ancestors(): array
    {
        $ancestors = [];
        $seen = [$this->id];
        $current = $this->parent;

        while ($current && ! in_array($current->id, $seen, true)) {
            $ancestors[] = $current;
            $seen[] = $current->id;
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * @param  Builder<Organization>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function displayName(): string
    {
        return $this->code ? "[{$this->code}] {$this->name}" : $this->name;
    }
}
