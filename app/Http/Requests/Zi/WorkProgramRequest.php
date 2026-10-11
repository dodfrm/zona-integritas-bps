<?php

namespace App\Http\Requests\Zi;

use App\Enums\ProgramStatus;
use App\Models\Area;
use App\Models\Indicator;
use App\Models\Period;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkProgramRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->route('program') !== null;

        return [
            'period_id' => [$isUpdate ? 'prohibited' : 'required', 'integer', Rule::exists(Period::class, 'id')],
            'area_id' => ['nullable', 'integer', Rule::exists(Area::class, 'id')],
            'indicator_ids' => ['array'],
            'indicator_ids.*' => ['integer', 'distinct', Rule::exists(Indicator::class, 'id')],
            'code' => ['nullable', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'objective' => ['nullable', 'string', 'max:5000'],
            'pic_user_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'start_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::enum(ProgramStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'period_id' => 'periode',
            'area_id' => 'area',
            'indicator_ids' => 'indikator',
            'name' => 'nama program',
            'objective' => 'tujuan',
            'pic_user_id' => 'penanggung jawab',
            'start_date' => 'tanggal mulai',
            'target_date' => 'target selesai',
        ];
    }
}
