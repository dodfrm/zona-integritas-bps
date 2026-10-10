<?php

namespace App\Http\Controllers;

use App\Enums\EvidenceCategory;
use App\Enums\ExecutorLevel;
use App\Models\VerificationStage;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Stage configuration only affects evidence submitted AFTER a change; existing reviews keep their snapshot.
 */
class VerificationStageController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): Response
    {
        Gate::authorize('manageVerificationFlow');

        return Inertia::render('verification-stages/index', [
            'stages' => VerificationStage::query()->orderBy('sequence')->get()->map(fn (VerificationStage $stage) => [
                'id' => $stage->id,
                'name' => $stage->name,
                'description' => $stage->description,
                'sequence' => $stage->sequence,
                'executor_level' => $stage->executor_level->value,
                'executor_level_label' => $stage->executor_level->label(),
                'evidence_category' => $stage->evidence_category?->value,
                'evidence_category_label' => $stage->evidence_category?->label(),
                'is_active' => $stage->is_active,
            ]),
            'levelOptions' => ExecutorLevel::options(),
            'categoryOptions' => EvidenceCategory::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manageVerificationFlow');

        $stage = VerificationStage::create($this->validated($request));
        $this->audit->record('verification_stage.created', "Menambah tahap verifikasi \"{$stage->name}\"", $stage, properties: $stage->only(['sequence', 'executor_level', 'evidence_category', 'is_active']));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tahap verifikasi ditambahkan.']);

        return to_route('verification-stages.index');
    }

    public function update(Request $request, VerificationStage $stage): RedirectResponse
    {
        Gate::authorize('manageVerificationFlow');

        $data = $this->validated($request, $stage);
        $stage->fill($data);

        if (! $stage->is_active && ! VerificationStage::query()->whereKeyNot($stage->id)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['is_active' => 'Minimal satu tahap verifikasi harus aktif.']);
        }

        $stage->save();

        if ($changes = $this->audit->changesOf($stage)) {
            $this->audit->record('verification_stage.updated', "Mengubah tahap verifikasi \"{$stage->name}\"", $stage, properties: $changes);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tahap verifikasi diperbarui. Perubahan berlaku untuk pengajuan berikutnya.']);

        return to_route('verification-stages.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?VerificationStage $stage = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sequence' => ['required', 'integer', 'min:1', 'max:100', Rule::unique(VerificationStage::class)->ignore($stage?->id)],
            'executor_level' => ['required', Rule::enum(ExecutorLevel::class)],
            'evidence_category' => ['nullable', Rule::enum(EvidenceCategory::class)],
            'is_active' => ['boolean'],
        ], attributes: ['name' => 'nama tahap', 'sequence' => 'urutan', 'executor_level' => 'pelaksana', 'evidence_category' => 'jenis bukti']);
    }
}
