<?php

namespace App\Jobs;

use App\Models\FeeReminderLog;
use App\Services\CommunicationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class ProcessFeeReminder implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $logId;

    public function __construct(int $logId)
    {
        $this->logId = $logId;
    }

    public function handle(CommunicationService $comm)
    {
        $log = FeeReminderLog::with(['voucher.studentEnrollment.student', 'template'])->find($this->logId);
        if (!$log) return;

        try {
            $student = $log->voucher->studentEnrollment->student;
            $parent = $student->parent ?? null;
            $contactNumber = $parent?->father_phone ?? $parent?->mother_phone ?? null;
            
            $messageContent = $this->parseTemplate($log->template->template_body, $log->voucher, $student);

            $log->update(['status' => 'processing']);

            // Send using CommunicationService (this service logic handles actual API calls)
            $result = $comm->send(
                $log->channel,
                $contactNumber,
                $messageContent,
                ['student_enrollment_id' => $log->student_enrollment_id]
            );
            
            $status = $result['status'] ?? 'failed';

            $log->update([
                'status' => $status,
                'sent_at' => now(),
            ]);

        } catch (Exception $e) {
            $log->update([
                'status' => 'failed',
                'sent_at' => now(), // Still mark time of failure attempt
            ]);
            throw $e;
        }
    }

    private function parseTemplate(string $templateBody, $voucher, $student): string
    {
        $replacements = [
            '{student_name}' => $student->student_name,
            '{voucher_no}' => $voucher->voucher_no,
            '{net_amount}' => number_format($voucher->net_amount, 2),
            '{due_date}' => $voucher->due_date ? $voucher->due_date->format('d M Y') : 'N/A',
            '{remaining_amount}' => number_format($voucher->remaining_amount, 2),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $templateBody);
    }
}
