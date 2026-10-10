<?php

namespace App\Services;

use App\Enums\ProgramStatus;
use App\Models\Indicator;
use App\Models\Period;
use App\Models\User;
use App\Models\WorkProgram;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkProgramService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AssignmentService $assignments,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, Period $period, array $data): WorkProgram
    {
        return DB::transaction(function () use ($user, $period, $data) {
            $program = new WorkProgram(Arr::except($data, ['indicator_ids', 'pic_user_id', 'period_id']));
            $program->organization_id = $period->organization_id;
            $program->period_id = $period->id;
            $program->status ??= ProgramStatus::Planned;
            $program->created_by = $user->id;
            $program->setRelation('period', $period);

            $this->ensureMasterDataMatchesPeriod($program, $data['indicator_ids'] ?? []);
            $program->save();
            $program->indicators()->sync($data['indicator_ids'] ?? []);
            $this->setPic($program, $data['pic_user_id'] ?? null);

            $this->audit->record('program.created', "Membuat program kerja \"{$program->name}\"", $program, properties: [
                'period_id' => $period->id,
                'indicator_ids' => $data['indicator_ids'] ?? [],
            ]);

            return $program;
        });
    }

    /**
     * Period and organization are fixed after creation; only content fields change.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(WorkProgram $program, array $data): void
    {
        DB::transaction(function () use ($program, $data) {
            $program->fill(Arr::except($data, ['indicator_ids', 'pic_user_id', 'period_id']));
            $this->ensureMasterDataMatchesPeriod($program, $data['indicator_ids'] ?? []);
            $program->save();

            $changes = $this->audit->changesOf($program);
            $indicatorChanges = $program->indicators()->sync($data['indicator_ids'] ?? []);
            $this->setPic($program, $data['pic_user_id'] ?? null);

            if ($changes || array_filter($indicatorChanges)) {
                $this->audit->record('program.updated', "Mengubah program kerja \"{$program->name}\"", $program, properties: [
                    ...$changes,
                    'indicators' => $indicatorChanges,
                ]);
            }
        });
    }

    public function archive(WorkProgram $program): void
    {
        $program->forceFill(['archived_at' => now()])->save();
        $this->audit->record('program.archived', "Mengarsipkan program kerja \"{$program->name}\"", $program);
    }

    private function setPic(WorkProgram $program, mixed $picUserId): void
    {
        $previous = $program->pic_user_id;
        $pic = $picUserId ? User::query()->findOrFail((int) $picUserId) : null;

        if ($pic) {
            $this->assignments->ensureAssignable($pic, $program->organization_id);
        }

        $program->forceFill(['pic_user_id' => $pic?->id])->save();

        if ($previous !== $pic?->id) {
            $this->assignments->syncPic($program, $pic);
        }
    }

    /**
     * Area and indicators must come from the guideline version used by the program's period.
     *
     * @param  array<int, int|string>  $indicatorIds
     */
    private function ensureMasterDataMatchesPeriod(WorkProgram $program, array $indicatorIds): void
    {
        $versionId = $program->period->guideline_version_id;

        if ($program->area_id && ! $program->area()->where('guideline_version_id', $versionId)->exists()) {
            throw ValidationException::withMessages(['area_id' => 'Area tidak termasuk pedoman yang digunakan periode ini.']);
        }

        if ($indicatorIds === []) {
            return;
        }

        $valid = Indicator::query()
            ->whereIn('id', $indicatorIds)
            ->whereHas('area', fn ($query) => $query->where('guideline_version_id', $versionId))
            ->when($program->area_id, fn ($query) => $query->where('area_id', $program->area_id))
            ->count();

        if ($valid !== count(array_unique($indicatorIds))) {
            throw ValidationException::withMessages(['indicator_ids' => 'Indikator harus berasal dari pedoman periode ini dan dari area yang dipilih.']);
        }
    }
}
