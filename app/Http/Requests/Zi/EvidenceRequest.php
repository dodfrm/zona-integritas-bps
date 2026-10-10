<?php

namespace App\Http\Requests\Zi;

use App\Enums\EvidenceCategory;
use App\Models\ActionPlan;
use App\Models\Indicator;
use App\Models\Period;
use App\Models\WorkProgram;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EvidenceRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->route('evidence') !== null;

        return [
            'period_id' => [$isUpdate ? 'prohibited' : 'required', 'integer', Rule::exists(Period::class, 'id')],
            'indicator_id' => ['nullable', 'integer', Rule::exists(Indicator::class, 'id')],
            'work_program_id' => ['nullable', 'integer', Rule::exists(WorkProgram::class, 'id')],
            'action_plan_id' => ['nullable', 'integer', Rule::exists(ActionPlan::class, 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', Rule::enum(EvidenceCategory::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'file' => [$isUpdate ? 'prohibited' : 'required', ...self::fileRules()],
        ];
    }

    /**
     * Server-side file checks: size and content-detected type (the client's name/MIME is not trusted).
     *
     * @return list<string>
     */
    public static function fileRules(): array
    {
        return [
            'file',
            'max:'.config('zi.evidence.max_kb'),
            'mimes:'.implode(',', config('zi.evidence.allowed_extensions')),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->filled('indicator_id') && ! $this->filled('work_program_id') && ! $this->filled('action_plan_id')) {
                    $validator->errors()->add('indicator_id', 'Kaitkan bukti dukung dengan minimal satu indikator, program kerja, atau rencana aksi.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'period_id' => 'periode',
            'indicator_id' => 'indikator',
            'work_program_id' => 'program kerja',
            'action_plan_id' => 'rencana aksi',
            'title' => 'judul',
            'category' => 'jenis bukti',
            'file' => 'berkas',
        ];
    }
}
