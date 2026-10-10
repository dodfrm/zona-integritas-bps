<?php

namespace App\Console\Commands;

use App\Enums\ActionPlanStatus;
use App\Enums\PeriodStatus;
use App\Models\ActionPlan;
use App\Services\Notifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('zi:send-deadline-reminders')]
#[Description('Send internal notifications for open action plans that are due soon or overdue')]
class SendDeadlineRemindersCommand extends Command
{
    public function handle(Notifier $notifier): int
    {
        $limit = today()->addDays(config('zi.reminders.days_before_due'));
        $sent = 0;

        ActionPlan::query()
            ->notArchived()
            ->whereNotNull('pic_user_id')
            ->whereIn('status', ActionPlanStatus::openValues())
            ->whereDate('due_date', '<=', $limit)
            ->whereHas('period', fn ($query) => $query->where('status', PeriodStatus::Active->value))
            ->with('pic')
            ->chunkById(200, function ($plans) use ($notifier, &$sent) {
                foreach ($plans as $plan) {
                    $when = $plan->isOverdue()
                        ? 'sudah melewati tenggat ('.$plan->due_date?->format('d/m/Y').')'
                        : 'jatuh tempo '.$plan->due_date?->format('d/m/Y');

                    $notifier->send($plan->pic, 'Pengingat tenggat rencana aksi', "\"{$plan->title}\" {$when}.", route('action-plans.show', $plan, false));
                    $sent++;
                }
            });

        $this->components->info("{$sent} pengingat dikirim.");

        return self::SUCCESS;
    }
}
