<?php

namespace App\Console\Commands;

use App\Models\FeeVoucher;
use App\Models\FeeReminderRule;
use App\Models\FeeReminderLog;
use App\Models\FeeReminderTemplate;
use App\Jobs\ProcessFeeReminder;
use Illuminate\Console\Command;
use Carbon\Carbon;

class RunFeeReminders extends Command
{
    protected $signature = 'fees:run-reminders {--date=}';

    protected $description = 'Scan for unpaid/overdue vouchers and queue fee reminders based on configured rules.';

    public function handle(): int
    {
        $today = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $this->info("Scanning vouchers for reminders as of {$today->toDateString()}");

        $rules = FeeReminderRule::where('is_active', true)->get();
        if ($rules->isEmpty()) {
            $this->info("No active reminder rules found. Exiting.");
            return self::SUCCESS;
        }

        $queued = 0;

        foreach ($rules as $rule) {
            $this->info("Processing rule: {$rule->rule_name}");
            
            $targetDate = match($rule->trigger_type) {
                'before_due' => (clone $today)->addDays($rule->days_offset),
                'on_due'     => clone $today,
                'after_due'  => (clone $today)->subDays($rule->days_offset),
                default      => null
            };

            if (!$targetDate) continue;

            $query = FeeVoucher::whereIn('status', ['pending', 'partial'])
                ->whereDate('due_date', $targetDate->toDateString())
                ->with(['studentEnrollment.student.parent', 'feeType']);

            if ($rule->fee_type_id) {
                $query->where('fee_type_id', $rule->fee_type_id);
            }
            
            if ($rule->branch_id) {
                $query->whereHas('studentEnrollment', function ($q) use ($rule) {
                    $q->where('branch_id', $rule->branch_id);
                });
            }

            $vouchers = $query->get();
            $this->info("  - Found {$vouchers->count()} matching vouchers.");

            foreach ($vouchers as $voucher) {
                // Ensure we don't send multiple reminders for the exact same rule and voucher on the same day
                $alreadySent = FeeReminderLog::where('voucher_id', $voucher->id)
                    ->whereHas('template', function($q) use ($rule) {
                        $q->where('rule_id', $rule->id);
                    })
                    ->whereDate('created_at', $today->toDateString())
                    ->exists();
                    
                if ($alreadySent) {
                    continue;
                }

                $template = FeeReminderTemplate::where('rule_id', $rule->id)
                    ->where('channel', $rule->channel)
                    ->first();
                
                if (!$template) {
                    $this->warn("  - No template found for rule {$rule->rule_name} on channel {$rule->channel}. Skipping.");
                    continue;
                }

                $parent = $voucher->studentEnrollment->student->parent ?? null;
                $contactNumber = $parent?->father_phone ?? $parent?->mother_phone ?? null;

                if (!$contactNumber && in_array($rule->channel, ['sms', 'whatsapp', 'call'])) {
                    continue;
                }

                $log = FeeReminderLog::create([
                    'student_enrollment_id' => $voucher->student_enrollment_id,
                    'voucher_id'            => $voucher->id,
                    'template_id'           => $template->id,
                    'channel'               => $rule->channel,
                    'status'                => 'queued',
                ]);

                ProcessFeeReminder::dispatch($log->id);
                $queued++;
            }
        }

        $this->info("Successfully queued {$queued} reminder(s).");
        return self::SUCCESS;
    }
}
