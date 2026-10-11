<?php

namespace App\Http\Requests\Zi;

use App\Models\GuidelineVersion;
use App\Models\Organization;
use App\Models\Period;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PeriodRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Period|null $period */
        $period = $this->route('period');
        $organizationId = $period->organization_id ?? $this->integer('organization_id');

        return [
            'organization_id' => [$period ? 'prohibited' : 'required', 'integer', Rule::exists(Organization::class, 'id')],
            'guideline_version_id' => ['required', 'integer', Rule::exists(GuidelineVersion::class, 'id')],
            'name' => ['required', 'string', 'max:255', Rule::unique(Period::class)->where('organization_id', $organizationId)->ignore($period?->id)],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'organization_id' => 'organisasi',
            'guideline_version_id' => 'versi pedoman',
            'name' => 'nama periode',
            'year' => 'tahun',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
        ];
    }
}
