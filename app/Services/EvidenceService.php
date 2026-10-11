<?php

namespace App\Services;

use App\Enums\AssignmentType;
use App\Enums\VerificationStatus;
use App\Models\Evidence;
use App\Models\EvidenceVersion;
use App\Models\Period;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EvidenceService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly VerificationService $verification,
        private readonly AssignmentService $assignments,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated metadata (links already checked against the period)
     */
    public function create(User $user, Period $period, array $data, UploadedFile $file, ?string $changeNote = null): Evidence
    {
        return $this->withStoredFileCleanup(function (array &$storedPaths) use ($user, $period, $data, $file, $changeNote) {
            $evidence = new Evidence($data);
            $evidence->organization_id = $period->organization_id;
            $evidence->period_id = $period->id;
            $evidence->uploaded_by = $user->id;
            $evidence->verification_status = VerificationStatus::Pending;
            $evidence->save();

            $version = $this->storeVersion($evidence, $user, $file, $changeNote, $storedPaths);
            $this->verification->startFor($evidence, $version);

            if ($evidence->actionPlan) {
                $this->assignments->completeFor($evidence->actionPlan, $user, AssignmentType::EvidenceCollection);
            }

            $this->audit->record('evidence.uploaded', "Mengunggah bukti dukung \"{$evidence->title}\" (versi 1)", $evidence, properties: [
                'version_id' => $version->id,
                'file' => $version->original_name,
                'size' => $version->size,
            ]);

            return $evidence;
        });
    }

    /**
     * A new file never overwrites the previous one; it becomes the next version and restarts verification.
     */
    public function addVersion(User $user, Evidence $evidence, UploadedFile $file, ?string $changeNote): EvidenceVersion
    {
        return $this->withStoredFileCleanup(function (array &$storedPaths) use ($user, $evidence, $file, $changeNote) {
            $evidence = Evidence::query()->lockForUpdate()->findOrFail($evidence->id);
            $previousStatus = $evidence->verification_status;

            $version = $this->storeVersion($evidence, $user, $file, $changeNote, $storedPaths);

            $evidence->forceFill([
                'verification_status' => in_array($previousStatus, [VerificationStatus::RevisionRequired, VerificationStatus::Rejected], true)
                    ? VerificationStatus::Resubmitted
                    : VerificationStatus::Pending,
            ])->save();

            $this->verification->startFor($evidence, $version);
            $this->assignments->completeFor($evidence, $user, AssignmentType::RevisionFollowUp);

            $this->audit->record('evidence.version_added', "Mengunggah versi {$version->version_number} bukti \"{$evidence->title}\"", $evidence, properties: [
                'version_id' => $version->id,
                'file' => $version->original_name,
                'previous_status' => $previousStatus->value,
                'change_note' => $changeNote,
            ]);

            return $version;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Evidence $evidence, array $data): void
    {
        $evidence->fill($data)->save();

        if ($changes = $this->audit->changesOf($evidence)) {
            $this->audit->record('evidence.updated', "Memperbarui metadata bukti \"{$evidence->title}\"", $evidence, properties: $changes);
        }
    }

    public function archive(Evidence $evidence): void
    {
        $evidence->forceFill(['archived_at' => now()])->save();
        $this->audit->record('evidence.archived', "Mengarsipkan bukti \"{$evidence->title}\"", $evidence);
    }

    public function download(EvidenceVersion $version): StreamedResponse
    {
        $name = Str::of(pathinfo($version->original_name, PATHINFO_FILENAME))->slug('_')->limit(120, '')->whenEmpty(fn () => Str::of('bukti'));

        return Storage::disk($version->disk)->download(
            $version->path,
            "{$name}_v{$version->version_number}.{$version->extension}",
            ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'],
        );
    }

    /**
     * @param  list<string>  $storedPaths
     */
    private function storeVersion(Evidence $evidence, User $user, UploadedFile $file, ?string $changeNote, array &$storedPaths): EvidenceVersion
    {
        $disk = config('zi.evidence.disk');
        // Extension comes from the server-side MIME guess (validated by the "mimes" rule), not the client name.
        $extension = Str::lower((string) $file->extension());
        $directory = "evidence/{$evidence->organization_id}/{$evidence->id}";
        $path = $file->storeAs($directory, Str::uuid()->toString().'.'.$extension, ['disk' => $disk]);

        if ($path === false) {
            throw new \RuntimeException('Berkas gagal disimpan.');
        }

        $storedPaths[] = $path;

        return $evidence->versions()->create([
            'version_number' => (int) $evidence->versions()->max('version_number') + 1,
            'disk' => $disk,
            'path' => $path,
            'original_name' => Str::limit(preg_replace('/[\x00-\x1F\x7F\/\\\\]/u', '', basename($file->getClientOriginalName())) ?: "bukti.{$extension}", 250, ''),
            'mime_type' => (string) $file->getMimeType(),
            'extension' => $extension,
            'size' => (int) $file->getSize(),
            'checksum' => (string) hash_file('sha256', (string) $file->getRealPath()),
            'change_note' => $changeNote,
            'uploaded_by' => $user->id,
        ]);
    }

    /**
     * Runs the callback in a transaction and removes files written by it if anything fails.
     *
     * @template T
     *
     * @param  callable(list<string>&): T  $callback
     * @return T
     */
    private function withStoredFileCleanup(callable $callback): mixed
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($callback, &$storedPaths) {
                return $callback($storedPaths);
            });
        } catch (Throwable $e) {
            Storage::disk(config('zi.evidence.disk'))->delete($storedPaths);

            throw $e;
        }
    }
}
