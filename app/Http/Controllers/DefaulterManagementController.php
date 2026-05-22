<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\FeeReminderLog;
use App\Models\FeeType;
use App\Models\FeeVoucher;
use App\Models\StudentFinanceBlock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DefaulterManagementController extends Controller
{
    public function index()
    {
        return Inertia::render('Defaulters/Index', [
            'academicYears' => AcademicYear::orderByDesc('is_active')->orderByDesc('id')->get(['id', 'year_name']),
            'branches' => Branch::orderBy('branch_name')->get(['id', 'branch_name']),
            'classes' => Classes::orderBy('display_order')->orderBy('class_name')->get(['id', 'class_name']),
            'feeTypes' => FeeType::orderBy('display_order')->orderBy('fee_name')->get(['id', 'fee_name']),
            'defaultGraceDays' => 5,
            'defaultBlockDays' => 30,
        ]);
    }

    public function data(Request $request)
    {
        $rows = $this->buildRows($request);

        if ($request->filled('status')) {
            $rows = $rows->where('status_key', $request->status)->values();
        }
        if ($request->filled('min_amount')) {
            $rows = $rows->filter(fn ($row) => $row['outstanding'] >= (float) $request->min_amount)->values();
        }
        if ($request->filled('max_amount')) {
            $rows = $rows->filter(fn ($row) => $row['outstanding'] <= (float) $request->max_amount)->values();
        }
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $rows = $rows->filter(fn ($row) => str_contains(strtolower($row['student_name'].' '.$row['admission_no'].' '.$row['pending_months'].' '.$row['class_section']), $search))->values();
        }

        return response()->json([
            'summary' => [
                'students' => $rows->count(),
                'total_outstanding' => round($rows->sum('outstanding'), 2),
                'warning' => $rows->where('status_key', 'warning')->count(),
                'defaulter' => $rows->where('status_key', 'defaulter')->count(),
                'blocked' => $rows->where('status_key', 'blocked')->count(),
                'chronic' => $rows->filter(fn ($row) => $row['pending_count'] >= 3)->count(),
            ],
            'rows' => $rows->sortByDesc('days_overdue')->values(),
            'reports' => [
                'class_wise' => $rows->groupBy('class_section')->map(fn ($items, $class) => [
                    'class' => $class,
                    'students' => $items->count(),
                    'outstanding' => round($items->sum('outstanding'), 2),
                ])->values(),
                'month_wise' => $rows->flatMap(fn ($row) => collect(explode(', ', $row['pending_months']))->filter()->map(fn ($month) => [
                    'month' => $month,
                    'outstanding' => $row['outstanding'],
                ]))->groupBy('month')->map(fn ($items, $month) => [
                    'month' => $month,
                    'outstanding' => round($items->sum('outstanding'), 2),
                ])->values(),
            ],
        ]);
    }

    public function sendReminders(Request $request)
    {
        $rows = $this->buildRows($request);
        if ($request->filled('student_enrollment_id')) {
            $rows = $rows->where('student_enrollment_id', (int) $request->student_enrollment_id);
        }

        $created = 0;
        DB::transaction(function () use ($rows, &$created) {
            foreach ($rows as $row) {
                foreach ($row['voucher_ids'] as $voucherId) {
                    $recipient = $row['contact'] ?: 'not_available';
                    FeeReminderLog::create([
                        'voucher_id' => $voucherId,
                        'student_enrollment_id' => $row['student_enrollment_id'],
                        'template_id' => null,
                        'channel' => 'whatsapp',
                        'recipient' => $recipient,
                        'status' => 'queued',
                        'provider_response' => 'Manual reminder queued from Defaulter Management.',
                    ]);
                    $created++;
                }
            }
        });

        return response()->json(['message' => "Queued {$created} reminder(s)."]);
    }

    public function export(Request $request)
    {
        $rows = $this->buildRows($request)->sortByDesc('days_overdue')->values();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Student', 'Admission No', 'Branch', 'Class / Section', 'Pending Months', 'Outstanding', 'Last Payment', 'Days Overdue', 'Status']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['student_name'],
                    $row['admission_no'],
                    $row['branch'],
                    $row['class_section'],
                    $row['pending_months'],
                    $row['outstanding'],
                    $row['last_payment_date'],
                    $row['days_overdue'],
                    $row['status'],
                ]);
            }

            fclose($handle);
        }, 'defaulters.csv');
    }

    public function block(Request $request)
    {
        $validated = $request->validate([
            'student_enrollment_id' => 'required|exists:student_enrollments,id',
            'reason' => 'nullable|string|max:255',
        ]);

        StudentFinanceBlock::updateOrCreate(
            ['student_enrollment_id' => $validated['student_enrollment_id']],
            [
                'is_blocked' => true,
                'block_portal' => true,
                'block_result' => true,
                'block_exam' => true,
                'reason' => $validated['reason'] ?? 'Blocked from Defaulter Management',
                'blocked_by' => auth()->id(),
                'blocked_at' => now(),
                'unblocked_by' => null,
                'unblocked_at' => null,
            ]
        );

        return response()->json(['message' => 'Student financial block applied.']);
    }

    public function unblock(Request $request)
    {
        $validated = $request->validate([
            'student_enrollment_id' => 'required|exists:student_enrollments,id',
        ]);

        StudentFinanceBlock::where('student_enrollment_id', $validated['student_enrollment_id'])
            ->update([
                'is_blocked' => false,
                'unblocked_by' => auth()->id(),
                'unblocked_at' => now(),
            ]);

        return response()->json(['message' => 'Student financial block removed.']);
    }

    private function buildRows(Request $request): Collection
    {
        $asOf = $request->filled('as_of') ? Carbon::parse($request->as_of)->startOfDay() : now()->startOfDay();
        $graceDays = max(0, (int) $request->get('grace_days', 5));
        $blockDays = max($graceDays + 1, (int) $request->get('block_days', 30));

        $query = FeeVoucher::with([
            'feeType',
            'payments',
            'studentEnrollment.student.parent',
            'studentEnrollment.branch',
            'studentEnrollment.classSection.section',
            'studentEnrollment.classSection.branchClass.class',
            'studentEnrollment.financeBlock',
        ])
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->where('remaining_amount', '>', 0)
            ->whereDate('due_date', '<', $asOf);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('fee_type_id')) {
            $query->where('fee_type_id', $request->fee_type_id);
        }
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        if ($request->filled('branch_id')) {
            $query->whereHas('studentEnrollment', fn ($q) => $q->where('branch_id', $request->branch_id));
        }
        if ($request->filled('class_id')) {
            $query->whereHas('studentEnrollment.classSection.branchClass', fn ($q) => $q->where('class_id', $request->class_id));
        }

        return $query->get()
            ->groupBy('student_enrollment_id')
            ->map(function ($vouchers, $enrollmentId) use ($asOf, $graceDays, $blockDays) {
                $first = $vouchers->first();
                $enrollment = $first->studentEnrollment;
                $student = $enrollment?->student;
                $block = $enrollment?->financeBlock;
                $maxDays = $vouchers->max(fn ($voucher) => Carbon::parse($voucher->due_date)->diffInDays($asOf));
                $isBlocked = (bool) ($block?->is_blocked);
                $statusKey = $isBlocked ? 'blocked' : ($maxDays > $graceDays ? 'defaulter' : 'warning');

                if (!$isBlocked && $maxDays >= $blockDays) {
                    $statusKey = 'blocked';
                }

                $lastPaymentDate = $vouchers->flatMap->payments
                    ->sortByDesc('payment_date')
                    ->first()?->payment_date;

                return [
                    'student_enrollment_id' => (int) $enrollmentId,
                    'student_name' => $student?->student_name ?? '-',
                    'admission_no' => $student?->admission_no ?? $enrollment?->roll_number ?? '-',
                    'contact' => $student?->parent?->father_phone ?? $student?->whatsapp_number ?? '',
                    'class_section' => trim(($enrollment?->classSection?->branchClass?->class?->class_name ?? '-').' - '.($enrollment?->classSection?->section?->section_name ?? '-')),
                    'branch' => $enrollment?->branch?->branch_name ?? '-',
                    'pending_months' => $vouchers->map(fn ($voucher) => $voucher->generated_for ?: ($voucher->month.'/'.$voucher->year))->unique()->implode(', '),
                    'pending_count' => $vouchers->count(),
                    'outstanding' => round((float) $vouchers->sum('remaining_amount'), 2),
                    'last_payment_date' => $lastPaymentDate ? Carbon::parse($lastPaymentDate)->format('d M, Y') : '-',
                    'days_overdue' => (int) $maxDays,
                    'status_key' => $statusKey,
                    'status' => ucfirst($statusKey),
                    'voucher_ids' => $vouchers->pluck('id')->values(),
                    'ledger_url' => route('student-ledgers.index', ['student_enrollment_id' => $enrollmentId]),
                    'voucher_url' => route('fee-vouchers.index', ['student_enrollment_id' => $enrollmentId]),
                ];
            })
            ->values();
    }
}
