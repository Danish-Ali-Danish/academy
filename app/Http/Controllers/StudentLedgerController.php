<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\FeeAdvanceAdjustment;
use App\Models\FeeRefund;
use App\Models\FeeType;
use App\Models\FeeVoucher;
use App\Models\StudentEnrollment;
use App\Models\StudentLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StudentLedgerController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('StudentLedgers/Index', [
            'academicYears' => AcademicYear::orderByDesc('is_active')->orderByDesc('id')
                ->get(['id', 'year_name']),
            'branches' => Branch::orderBy('branch_name')->get(['id', 'branch_name']),
            'classes' => Classes::orderBy('display_order')->orderBy('class_name')->get(['id', 'class_name']),
            'feeTypes' => FeeType::orderBy('display_order')->orderBy('fee_name')->get(['id', 'fee_name']),
            'initialEnrollmentId' => $request->integer('student_enrollment_id') ?: null,
        ]);
    }

    public function data(Request $request)
    {
        $rows = $this->buildStatementRows($request);
        $filteredRows = $this->filterRows($rows, $request);

        $openingBalance = 0.0;
        if ($request->filled('date_from')) {
            $from = Carbon::parse($request->date_from)->startOfDay();
            $openingBalance = $rows
                ->filter(fn ($row) => $row['sort_date']->lt($from))
                ->reduce(fn ($carry, $row) => $carry + $row['debit'] - $row['credit'], 0.0);
        }

        $running = $openingBalance;
        $statement = $filteredRows
            ->sortBy(fn ($row) => [$row['sort_date']->timestamp, $row['sort_id']])
            ->values()
            ->map(function ($row) use (&$running) {
                $running += $row['debit'] - $row['credit'];
                $row['balance'] = round($running, 2);
                $row['date'] = $row['sort_date']->format('d M, Y');
                unset($row['sort_date'], $row['sort_id']);

                return $row;
            });

        $summary = [
            'opening_balance' => round($openingBalance, 2),
            'total_due' => round($statement->sum('debit'), 2),
            'total_paid' => round($statement->sum('credit'), 2),
            'current_outstanding' => round($running, 2),
            'total_entries' => $statement->count(),
        ];

        return response()->json([
            'summary' => $summary,
            'student' => $this->selectedStudent($request),
            'rows' => $statement,
        ]);
    }

    public function manualEntry(Request $request)
    {
        $validated = $request->validate([
            'student_enrollment_id' => 'required|exists:student_enrollments,id',
            'transaction_type' => 'required|in:debit,credit',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated) {
            $currentBalance = $this->calculateRawBalance((int) $validated['student_enrollment_id']);
            $amount = (float) $validated['amount'];
            $balanceAfter = $validated['transaction_type'] === 'debit'
                ? $currentBalance + $amount
                : $currentBalance - $amount;

            StudentLedger::create([
                'student_enrollment_id' => $validated['student_enrollment_id'],
                'transaction_type' => $validated['transaction_type'],
                'amount' => $amount,
                'description' => 'Manual correction: '.$validated['description'],
                'reference_type' => 'manual_adjustment',
                'reference_id' => null,
                'balance_after' => $balanceAfter,
                'created_by' => auth()->id(),
            ]);
        });

        return response()->json(['message' => 'Manual ledger entry added successfully.']);
    }

    private function buildStatementRows(Request $request): Collection
    {
        $voucherQuery = FeeVoucher::with([
            'feeType',
            'academicYear',
            'studentEnrollment.student.parent',
            'studentEnrollment.branch',
            'studentEnrollment.classSection.section',
            'studentEnrollment.classSection.branchClass.class',
            'discountBreakdowns.appliedBy',
            'payments.receivedBy',
            'fines.appliedBy',
            'waiver.approvedBy',
        ]);

        $this->applyVoucherFilters($voucherQuery, $request);

        $rows = collect();

        $voucherQuery->get()->each(function (FeeVoucher $voucher) use (&$rows) {
            $enrollment = $voucher->studentEnrollment;
            $student = $enrollment?->student;
            $studentName = $student?->student_name ?? '-';
            $studentCode = $student?->admission_no ?? $enrollment?->roll_number ?? '-';
            $feeType = $voucher->feeType?->fee_name ?? 'Fee';
            $baseDate = $voucher->created_at ?: $voucher->due_date ?: now();

            $rows->push($this->row($baseDate, $voucher->id * 10, $enrollment?->id, $studentName, $studentCode,
                'debit', (float) $voucher->original_amount, "Voucher Generated - {$feeType} ({$voucher->generated_for})",
                'voucher', $voucher->id, $voucher->voucher_no, route('fee-vouchers.edit', $voucher->id)
            ));

            foreach ($voucher->discountBreakdowns as $discount) {
                $label = $discount->source_label ?: str_replace('_', ' ', (string) $discount->discount_source);
                $rows->push($this->row($baseDate, $voucher->id * 10 + 1, $enrollment?->id, $studentName, $studentCode,
                    'credit', (float) $discount->calculated_amount, "Discount Applied - {$label}",
                    'discount', $discount->id, $voucher->voucher_no, null
                ));
            }

            if ($voucher->waiver && strtolower((string) $voucher->waiver->status) === 'approved') {
                $date = $voucher->waiver->approved_on ?: $baseDate;
                $rows->push($this->row($date, $voucher->id * 10 + 2, $enrollment?->id, $studentName, $studentCode,
                    'credit', (float) $voucher->waiver->waived_amount, 'Waiver Approved - '.$voucher->waiver->waiver_reason,
                    'waiver', $voucher->waiver->id, $voucher->voucher_no, route('fee-waivers.edit', $voucher->waiver->id)
                ));
            }

            foreach ($voucher->fines->where('is_waived', false) as $fine) {
                $date = $fine->applied_on ?: $baseDate;
                $rows->push($this->row($date, $voucher->id * 10 + 3, $enrollment?->id, $studentName, $studentCode,
                    'debit', (float) $fine->calculated_amount, 'Fine Applied - '.$voucher->voucher_no,
                    'fine', $fine->id, $voucher->voucher_no, route('fee-voucher-fines.edit', $fine->id)
                ));
            }

            foreach ($voucher->payments as $payment) {
                $date = $payment->payment_date ?: $payment->created_at ?: $baseDate;
                $rows->push($this->row($date, $voucher->id * 10 + 4, $enrollment?->id, $studentName, $studentCode,
                    'credit', (float) $payment->paid_amount, 'Payment Received - '.ucwords(str_replace('_', ' ', (string) $payment->payment_method)),
                    'payment', $payment->id, $payment->receipt_no, route('fee-payments.show', $payment->id)
                ));
            }
        });

        $this->appendAdvanceRows($rows, $request);
        $this->appendRefundRows($rows, $request);
        $this->appendManualRows($rows, $request);

        return $rows;
    }

    private function applyVoucherFilters($query, Request $request): void
    {
        if ($request->filled('student_enrollment_id')) {
            $query->where('student_enrollment_id', $request->student_enrollment_id);
        }
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('fee_type_id')) {
            $query->where('fee_type_id', $request->fee_type_id);
        }
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        if ($request->filled('status')) {
            $request->status === 'paid'
                ? $query->where('status', 'paid')
                : $query->whereIn('status', ['pending', 'partial', 'overdue']);
        }
        if ($request->filled('branch_id')) {
            $query->whereHas('studentEnrollment', fn ($q) => $q->where('branch_id', $request->branch_id));
        }
        if ($request->filled('class_id')) {
            $query->whereHas('studentEnrollment.classSection.branchClass', fn ($q) => $q->where('class_id', $request->class_id));
        }
    }

    private function filterRows(Collection $rows, Request $request): Collection
    {
        if ($request->filled('date_from')) {
            $from = Carbon::parse($request->date_from)->startOfDay();
            $rows = $rows->filter(fn ($row) => $row['sort_date']->gte($from));
        }
        if ($request->filled('date_to')) {
            $to = Carbon::parse($request->date_to)->endOfDay();
            $rows = $rows->filter(fn ($row) => $row['sort_date']->lte($to));
        }
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $rows = $rows->filter(fn ($row) => str_contains(strtolower($row['description'].' '.$row['student_name'].' '.$row['student_code'].' '.$row['reference_label']), $search));
        }

        return $rows;
    }

    private function appendAdvanceRows(Collection &$rows, Request $request): void
    {
        $query = FeeAdvanceAdjustment::with(['studentEnrollment.student', 'toVoucher']);
        if ($request->filled('student_enrollment_id')) {
            $query->where('student_enrollment_id', $request->student_enrollment_id);
        }

        $query->get()->each(function (FeeAdvanceAdjustment $adjustment) use (&$rows) {
            $enrollment = $adjustment->studentEnrollment;
            $student = $enrollment?->student;
            $rows->push($this->row($adjustment->adjusted_at ?: $adjustment->created_at ?: now(), 700000 + $adjustment->id,
                $enrollment?->id, $student?->student_name ?? '-', $student?->admission_no ?? $enrollment?->roll_number ?? '-',
                'credit', (float) $adjustment->adjusted_amount, 'Advance Adjusted'.($adjustment->toVoucher ? ' - '.$adjustment->toVoucher->voucher_no : ''),
                'advance_adjustment', $adjustment->id, $adjustment->toVoucher?->voucher_no ?? '-', null
            ));
        });
    }

    private function appendRefundRows(Collection &$rows, Request $request): void
    {
        $query = FeeRefund::with(['studentEnrollment.student', 'payment']);
        if ($request->filled('student_enrollment_id')) {
            $query->where('student_enrollment_id', $request->student_enrollment_id);
        }

        $query->whereIn('status', ['Approved', 'approved', 'Completed', 'completed'])->get()
            ->each(function (FeeRefund $refund) use (&$rows) {
                $enrollment = $refund->studentEnrollment;
                $student = $enrollment?->student;
                $rows->push($this->row($refund->refund_date ?: $refund->created_at ?: now(), 800000 + $refund->id,
                    $enrollment?->id, $student?->student_name ?? '-', $student?->admission_no ?? $enrollment?->roll_number ?? '-',
                    'credit', (float) $refund->refund_amount, 'Refund Issued - '.$refund->reason,
                    'refund', $refund->id, $refund->payment?->receipt_no ?? '-', route('fee-refunds.edit', $refund->id)
                ));
            });
    }

    private function appendManualRows(Collection &$rows, Request $request): void
    {
        $query = StudentLedger::query()
            ->with(['studentEnrollment.student'])
            ->where('reference_type', 'manual_adjustment');

        if ($request->filled('student_enrollment_id')) {
            $query->where('student_enrollment_id', $request->student_enrollment_id);
        }

        $query->get()->each(function (StudentLedger $entry) use (&$rows) {
            $enrollment = $entry->studentEnrollment;
            $student = $enrollment?->student;
            $type = $entry->transaction_type === 'debit' ? 'debit' : 'credit';

            $rows->push($this->row($entry->created_at ?: now(), 900000 + $entry->id,
                $enrollment?->id, $student?->student_name ?? '-', $student?->admission_no ?? $enrollment?->roll_number ?? '-',
                $type, (float) $entry->amount, $entry->description,
                'manual_adjustment', $entry->id, 'Manual', null
            ));
        });
    }

    private function row($date, int $sortId, ?int $enrollmentId, string $studentName, string $studentCode, string $type, float $amount, string $description, string $referenceType, ?int $referenceId, string $referenceLabel, ?string $url): array
    {
        return [
            'sort_date' => $date instanceof Carbon ? $date : Carbon::parse($date),
            'sort_id' => $sortId,
            'student_enrollment_id' => $enrollmentId,
            'student_name' => $studentName,
            'student_code' => $studentCode,
            'description' => $description,
            'debit' => $type === 'debit' ? round($amount, 2) : 0.0,
            'credit' => $type === 'credit' ? round($amount, 2) : 0.0,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reference_label' => $referenceLabel,
            'url' => $url,
        ];
    }

    private function selectedStudent(Request $request): ?array
    {
        if (!$request->filled('student_enrollment_id')) {
            return null;
        }

        $enrollment = StudentEnrollment::with([
            'student.parent',
            'academicYear',
            'branch',
            'classSection.section',
            'classSection.branchClass.class',
        ])->find($request->student_enrollment_id);

        if (!$enrollment) {
            return null;
        }

        return [
            'name' => $enrollment->student?->student_name ?? '-',
            'admission_no' => $enrollment->student?->admission_no ?? '-',
            'roll_no' => $enrollment->roll_number ?? '-',
            'father_name' => $enrollment->student?->parent?->father_name ?? '-',
            'contact' => $enrollment->student?->parent?->father_phone ?? $enrollment->student?->whatsapp_number ?? '-',
            'class' => $enrollment->classSection?->branchClass?->class?->class_name ?? '-',
            'section' => $enrollment->classSection?->section?->section_name ?? '-',
            'branch' => $enrollment->branch?->branch_name ?? '-',
            'academic_year' => $enrollment->academicYear?->year_name ?? '-',
        ];
    }

    private function calculateRawBalance(int $enrollmentId): float
    {
        $debits = StudentLedger::where('student_enrollment_id', $enrollmentId)
            ->where('transaction_type', 'debit')
            ->sum('amount');
        $credits = StudentLedger::where('student_enrollment_id', $enrollmentId)
            ->where('transaction_type', 'credit')
            ->sum('amount');

        return (float) $debits - (float) $credits;
    }
}
