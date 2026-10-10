<?php

namespace App\Services;

use App\Enums\GuidelineStatus;
use App\Enums\PeriodStatus;
use App\Models\GuidelineVersion;
use App\Models\Organization;
use App\Models\Period;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PeriodService
{
    /** Allowed status transitions. Closing never touches operational data. */
    private const TRANSITIONS = [
        'draft' => ['active', 'archived'],
        'active' => ['closed'],
        'closed' => ['active', 'archived'],
        'archived' => [],
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, Organization $organization, GuidelineVersion $version, array $data): Period
    {
        $this->ensurePublished($version);

        $period = new Period($data);
        $period->organization_id = $organization->id;
        $period->guideline_version_id = $version->id;
        $period->status = PeriodStatus::Draft;
        $period->created_by = $user->id;
        $period->save();

        $this->audit->record('period.created', "Membuat periode \"{$period->name}\" untuk {$organization->name}", $period, properties: [
            'guideline_version_id' => $version->id,
        ]);

        return $period;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Period $period, array $data, ?GuidelineVersion $version): void
    {
        $period->fill($data);

        if ($version && $version->id !== $period->guideline_version_id) {
            // The guideline snapshot may only change before any work is recorded under it.
            if ($period->status !== PeriodStatus::Draft || $period->workPrograms()->exists()) {
                throw ValidationException::withMessages(['guideline_version_id' => 'Pedoman hanya dapat diganti saat periode masih draf dan belum memiliki program kerja.']);
            }

            $this->ensurePublished($version);
            $period->guideline_version_id = $version->id;
        }

        $period->save();

        if ($changes = $this->audit->changesOf($period)) {
            $this->audit->record('period.updated', "Mengubah periode \"{$period->name}\"", $period, properties: $changes);
        }
    }

    public function changeStatus(User $user, Period $period, PeriodStatus $status): void
    {
        if (! in_array($status->value, self::TRANSITIONS[$period->status->value], true)) {
            throw ValidationException::withMessages(['status' => "Periode berstatus {$period->status->label()} tidak dapat diubah menjadi {$status->label()}."]);
        }

        DB::transaction(function () use ($user, $period, $status) {
            if ($status === PeriodStatus::Active) {
                $hasOtherActive = Period::query()
                    ->where('organization_id', $period->organization_id)
                    ->where('status', PeriodStatus::Active->value)
                    ->whereKeyNot($period->id)
                    ->lockForUpdate()
                    ->exists();

                if ($hasOtherActive) {
                    throw ValidationException::withMessages(['status' => 'Organisasi ini sudah memiliki periode aktif. Tutup periode tersebut terlebih dahulu.']);
                }
            }

            $from = $period->status;
            $period->forceFill([
                'status' => $status,
                'closed_at' => $status === PeriodStatus::Closed ? now() : $period->closed_at,
                'closed_by' => $status === PeriodStatus::Closed ? $user->id : $period->closed_by,
            ])->save();

            $this->audit->record('period.status_changed', "Status periode \"{$period->name}\": {$from->label()} → {$status->label()}", $period, properties: [
                'from' => $from->value,
                'to' => $status->value,
            ]);
        });
    }

    /**
     * @return list<string>
     */
    public function allowedTransitions(Period $period): array
    {
        return self::TRANSITIONS[$period->status->value];
    }

    private function ensurePublished(GuidelineVersion $version): void
    {
        if ($version->status !== GuidelineStatus::Published) {
            throw ValidationException::withMessages(['guideline_version_id' => 'Periode hanya dapat menggunakan pedoman yang berstatus berlaku.']);
        }
    }
}
