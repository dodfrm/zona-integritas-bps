<?php

namespace App\Http\Requests\Zi;

use App\Models\Indicator;
use App\Models\User;
use App\Models\WorkProgram;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActionPlanRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->route('actionPlan') !== null;

        return [
            'work_program_id' => [$isUpdate ? 'prohibited' : 'required', 'integer', Rule::exists(WorkProgram::class, 'id')],
            'indicator_id' => ['nullable', 'integer', Rule::exists(Indicator::class, 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'output_target' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'pic_user_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'work_program_id' => 'program kerja',
            'indicator_id' => 'indikator',
            'title' => 'judul rencana aksi',
            'output_target' => 'target keluaran',
            'start_date' => 'tanggal mulai',
            'due_date' => 'tenggat waktu',
            'pic_user_id' => 'penanggung jawab',
        ];
    }
}
