<?php

namespace App\Services;

use App\Models\FeeFineRule;
use App\Models\FeeVoucher;
use App\Models\FeeVoucherFine;
use App\Models\SiblingDiscountRule;
use App\Models\StudentFeeConcession;
use App\Models\StudentFeeStructure;
use App\Models\StudentLedger;
use App\Models\StudentScholarship;
use App\Models\StudentAdvanceBalance;
use App\Models\VoucherDiscountBreakdown;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class FeeGenerationService
{
    public function previewMonthlyVouchers(array $filters): array
    {
        $rows = $this->buildVoucherRows($filters);

        return [
            'success' => true,
            'summary' => $this->summarizeRows($rows),
            'rows' => $rows,
        ];
    }

    /**
     * Backwards compatible: accepts either old arguments or a filter array.
     */
    public function generateMonthlyVouchers($branchIdOrFilters, $academicYearId = null, $month = null, $year = null): array
    {
        $filters = is_array($branchIdOrFilters)
            ? $branchIdOrFilters
            : [
                'branch_id' => $branchIdOrFilters,
                'academic_year_id' => $academicYearId,
                'month' => $month,
                'year' => $year,
            ];

        DB::beginTransaction();

        try {
            $rows = $this->buildVoucherRows($filters);
            $generatedCount = 0;

            foreach ($rows as &$row) {
                if ($row['status'] !== 'ready') {
                    continue;
                }

                $existingVoucher = $this->findActiveExistingVoucher(
                    (int) $row['student_enrollment_id'],
                    (int) $row['fee_type_id'],
                    (string) $row['generated_for']
                );

                if ($existingVoucher) {
                    $row['status'] = 'existing';
                    $row['existing_voucher_no'] = $existingVoucher->voucher_no;
                    continue;
                }

                $this->releaseTrashedDuplicateVoucherKeys(
                    (int) $row['student_enrollment_id'],
                    (int) $row['fee_type_id'],
                    (string) $row['generated_for']
                );

                $advanceNotes = ($row['advance_deduction'] ?? 0) > 0
                    ? (' [Advance: Rs ' . number_format((float) $row['advance_deduction'], 2) . ']')
                    : '';

                $voucher = FeeVoucher::create([
                    'voucher_no' => $this->generateVoucherNo(),
                    'student_enrollment_id' => $row['student_enrollment_id'],
                    'fee_type_id' => $row['fee_type_id'],
                    'academic_year_id' => $row['academic_year_id'],
                    'fee_structure_id' => $row['fee_structure_id'] ?? null,
                    'fee_structure_version_id' => $row['fee_structure_version_id'] ?? null,
                    'month' => $row['month'],
                    'year' => $row['year'],
                    'generated_for' => $row['generated_for'],
                    'original_amount' => $row['original_amount'],
                    'discount_amount' => $row['discount_amount'],
                    'fine_amount' => $row['fine_amount'],
                    'arrears_amount' => $row['arrears_amount'] ?? 0,
                    'net_amount' => $row['net_amount'],
                    'paid_amount' => 0,
                    'remaining_amount' => $row['net_amount'],
                    'due_date' => $row['due_date'],
                    'status' => 'pending',
                    'generated_by' => auth()->id(),
                    'notes' => 'Generated from monthly voucher generator' . $advanceNotes,
                ]);

                if (!empty($row['carry_forward_records'])) {
                    foreach ($row['carry_forward_records'] as $cfRecord) {
                        \App\Models\StudentCarryForward::create([
                            'student_enrollment_id' => $row['student_enrollment_id'],
                            'branch_id' => \App\Models\StudentEnrollment::find($row['student_enrollment_id'])?->branch_id,
                            'academic_year_id' => $row['academic_year_id'],
                            'from_voucher_id' => $cfRecord['from_voucher_id'],
                            'from_month_name' => $cfRecord['from_month_name'],
                            'to_month_name' => $cfRecord['to_month_name'],
                            'original_amount' => $cfRecord['original_amount'],
                            'carry_amount' => $cfRecord['carry_amount'],
                            'status' => 'pending',
                        ]);

                        // Close old voucher
                        $oldVoucher = FeeVoucher::find($cfRecord['from_voucher_id']);
                        if ($oldVoucher) {
                            $oldVoucher->status = 'carried_forward';
                            $oldVoucher->remaining_amount = 0;
                            $oldVoucher->notes = trim($oldVoucher->notes . " [Carried Forward to " . $cfRecord['to_month_name'] . "]");
                            $oldVoucher->save();
                            
                            // To balance ledger, the old voucher debt is now covered by the new voucher, 
                            // we don't strictly need a new ledger entry for the old voucher because the new voucher 
                            // ledger entry will add the arrears, but we should credit the old voucher amount 
                            // so the ledger doesn't double-count the debt.
                            \App\Models\StudentLedger::create([
                                'student_enrollment_id' => $row['student_enrollment_id'],
                                'transaction_type' => 'credit',
                                'amount' => $cfRecord['carry_amount'],
                                'description' => 'Carry Forward transferred to: ' . $cfRecord['to_month_name'],
                                'reference_type' => 'carry_forward',
                                'reference_id' => $cfRecord['from_voucher_id'],
                                'balance_after' => $this->calculateStudentBalance($row['student_enrollment_id']) - $cfRecord['carry_amount'],
                                'created_by' => auth()->id(),
                            ]);
                        }
                    }
                }

                foreach ($row['breakdown'] as $discount) {
                    // 'advance' source discounts are not persisted as discount breakdowns;
                    // they use the StudentAdvanceBalance / StudentLedger system.
                    if (($discount['source'] ?? '') === 'advance') {
                        continue;
                    }

                    VoucherDiscountBreakdown::create([
                        'voucher_id' => $voucher->id,
                        'discount_source' => $discount['source'],
                        'source_id' => $discount['source_id'],
                        'source_label' => $discount['label'],
                        'discount_type' => $discount['discount_type'],
                        'discount_value' => $discount['discount_value'],
                        'calculated_amount' => $discount['amount'],
                        'applied_by' => auth()->id(),
                    ]);
                }

                StudentLedger::create([
                    'student_enrollment_id' => $row['student_enrollment_id'],
                    'transaction_type' => 'debit',
                    'amount' => $row['net_amount'],
                    'description' => 'Fee voucher generated: ' . $voucher->voucher_no,
                    'reference_type' => 'voucher',
                    'reference_id' => $voucher->id,
                    'balance_after' => $this->calculateStudentBalance($row['student_enrollment_id']) + $row['net_amount'],
                    'created_by' => auth()->id(),
                ]);

                $generatedCount++;
            }
            unset($row);

            DB::commit();

            $summary = $this->summarizeRows($rows);

            return [
                'success' => true,
                'message' => "Generated {$generatedCount} vouchers. Skipped {$summary['existing_count']} existing vouchers.",
                'count' => $generatedCount,
                'skipped' => $summary['existing_count'],
                'summary' => $summary,
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error generating vouchers: ' . $e->getMessage(),
            ];
        }
    }

    public function recalculateVoucher(FeeVoucher $voucher): array
    {
        DB::beginTransaction();

        try {
            $studentFeeStructure = null;

            if ($voucher->fee_structure_id) {
                $studentFeeStructure = StudentFeeStructure::withTrashed()
                    ->where('student_enrollment_id', $voucher->student_enrollment_id)
                    ->where('fee_structure_id', $voucher->fee_structure_id)
                    ->with(['studentEnrollment', 'feeStructure'])
                    ->first();
            }

            if (!$studentFeeStructure) {
                $studentFeeStructure = StudentFeeStructure::active()
                    ->where('student_enrollment_id', $voucher->student_enrollment_id)
                    ->whereHas('feeStructure', fn ($q) => $q
                        ->where('fee_type_id', $voucher->fee_type_id)
                        ->where('is_active', true))
                    ->with(['studentEnrollment', 'feeStructure'])
                    ->first();
            }

            if (!$studentFeeStructure) {
                DB::rollBack();

                return ['success' => false, 'message' => 'Fee structure not assigned to this student'];
            }

            $baseAmount = $studentFeeStructure->effective_amount;
            $discounts = $this->calculateDiscounts(
                $studentFeeStructure->studentEnrollment,
                $voucher->fee_type_id,
                $baseAmount,
                $voucher->academic_year_id,
                (int) $voucher->month,
                (int) $voucher->year
            );

            $netAmount = max(0, $baseAmount - $discounts['total_discount'] + (float) $voucher->fine_amount);

            $voucher->original_amount = $baseAmount;
            $voucher->discount_amount = $discounts['total_discount'];
            $voucher->net_amount = $netAmount;
            $voucher->remaining_amount = max(0, $netAmount - (float) $voucher->paid_amount);
            $voucher->status = $this->statusFromAmounts((float) $voucher->paid_amount, $voucher->remaining_amount);
            $voucher->save();

            VoucherDiscountBreakdown::where('voucher_id', $voucher->id)->delete();
            foreach ($discounts['breakdown'] as $discount) {
                VoucherDiscountBreakdown::create([
                    'voucher_id' => $voucher->id,
                    'discount_source' => $discount['source'],
                    'source_id' => $discount['source_id'],
                    'source_label' => $discount['label'],
                    'discount_type' => $discount['discount_type'],
                    'discount_value' => $discount['discount_value'],
                    'calculated_amount' => $discount['amount'],
                    'applied_by' => auth()->id(),
                ]);
            }

            app(FeeVoucherBalanceService::class)->sync($voucher);

            DB::commit();

            return ['success' => true, 'message' => 'Voucher recalculated successfully'];
        } catch (Exception $e) {
            DB::rollBack();

            return ['success' => false, 'message' => 'Error recalculating voucher: ' . $e->getMessage()];
        }
    }

    private function buildVoucherRows(array $filters): array
    {
        $month = (int) $filters['month'];
        $year = (int) $filters['year'];
        $academicYearId = (int) $filters['academic_year_id'];
        $branchId = $filters['branch_id'] ?? null;
        $classId = $filters['class_id'] ?? null;
        $feeTypeId = $filters['fee_type_id'] ?? null;
        $studentEnrollmentId = $filters['student_enrollment_id'] ?? null;

        $query = StudentFeeStructure::active()
            ->with([
                'studentEnrollment.student',
                'studentEnrollment.academicYear',
                'studentEnrollment.branch',
                'studentEnrollment.classSection.branchClass.class',
                'studentEnrollment.classSection.section',
                'feeStructure.feeType',
            ])
            ->whereHas('studentEnrollment', function ($q) use ($academicYearId, $branchId, $classId, $studentEnrollmentId) {
                $q->where('status', 'active')
                    ->where('academic_year_id', $academicYearId);

                if ($studentEnrollmentId) {
                    $q->where('id', $studentEnrollmentId);
                }

                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }

                if ($classId) {
                    $q->whereHas('classSection.branchClass', fn ($classQuery) => $classQuery->where('class_id', $classId));
                }
            })
            ->whereHas('feeStructure', function ($q) use ($academicYearId, $branchId, $classId, $feeTypeId) {
                $q->where('is_active', true)
                    ->where('academic_year_id', $academicYearId);

                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }

                if ($classId) {
                    $q->where('class_id', $classId);
                }

                if ($feeTypeId) {
                    $q->where('fee_type_id', $feeTypeId);
                }
            });

        $studentFeeStructures = $query->get()
            ->filter(fn ($studentFeeStructure) => $this->feeTypeAppliesToMonth($studentFeeStructure->feeStructure?->feeType, $month));

        // Load Carry Forward Settings and Unpaid Vouchers
        $cfSetting = \App\Models\CarryForwardSetting::where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)->orWhereNull('branch_id');
        })->first();

        $unpaidVouchersByStudent = [];
        if ($cfSetting && $cfSetting->is_enabled) {
            $enrollmentIds = $studentFeeStructures->pluck('student_enrollment_id')->unique();
            $unpaidVouchers = \App\Models\FeeVoucher::whereIn('student_enrollment_id', $enrollmentIds)
                ->whereIn('status', ['pending', 'partial'])
                ->get();
            $unpaidVouchersByStudent = $unpaidVouchers->groupBy('student_enrollment_id')->toArray();
        }

        return $studentFeeStructures
            ->map(fn ($studentFeeStructure) => $this->makeVoucherRow($studentFeeStructure, $academicYearId, $month, $year, $cfSetting, $unpaidVouchersByStudent))
            ->values()
            ->all();
    }

    private function makeVoucherRow(StudentFeeStructure $studentFeeStructure, int $academicYearId, int $month, int $year, $cfSetting = null, array $unpaidVouchersByStudent = []): array
    {
        $enrollment = $studentFeeStructure->studentEnrollment;
        $structure = $studentFeeStructure->feeStructure;
        $feeType = $structure?->feeType;
        $baseAmount = round((float) $studentFeeStructure->effective_amount, 2);
        $discounts = $this->calculateDiscounts($enrollment, $structure->fee_type_id, $baseAmount, $academicYearId, $month, $year);
        $fineAmount = round($this->calculateApplicableFines($enrollment, $structure->fee_type_id, $month, $year), 2);
        
        // Calculate Arrears (Carry Forward)
        $arrearsAmount = 0.0;
        $carryForwardRecords = [];
        if ($cfSetting && $cfSetting->is_enabled && isset($unpaidVouchersByStudent[$enrollment->id])) {
            $previousVouchers = collect($unpaidVouchersByStudent[$enrollment->id])
                ->sortByDesc('id')
                ->take($cfSetting->max_months);
                
            foreach ($previousVouchers as $pv) {
                $rem = (float) $pv['remaining_amount'];
                if ($cfSetting->scope === 'fee_only') {
                    // Approximate fee portion by subtracting fines if any remain (simple approximation)
                    $rem = min($rem, (float) $pv['original_amount'] - (float) $pv['discount_amount']);
                }
                $arrearsAmount += $rem;
                $carryForwardRecords[] = [
                    'from_voucher_id' => $pv['id'],
                    'from_month_name' => $this->getMonthName($pv['month']) . ' ' . $pv['year'],
                    'to_month_name' => $this->getMonthName($month) . ' ' . $year,
                    'original_amount' => $pv['original_amount'],
                    'carry_amount' => $rem,
                ];
            }
        }

        $netAmount = max(0, round($baseAmount - $discounts['total_discount'] + $fineAmount + $arrearsAmount, 2));
        $generatedFor = $this->getMonthName($month) . ' ' . $year;
        $existingVoucher = $this->findActiveExistingVoucher($enrollment->id, $structure->fee_type_id, $generatedFor);

        // Advance balance check: auto-apply any available advance for this student
        $advanceDeduction = 0.0;
        $advanceNarration = '';
        if ($netAmount > 0 && !$existingVoucher) {
            $balanceRecord = StudentAdvanceBalance::where('student_enrollment_id', $enrollment->id)->first();
            $available = (float) ($balanceRecord?->balance ?? 0);
            if ($available > 0) {
                $advanceDeduction = min($available, $netAmount);
                $netAmount       = max(0, $netAmount - $advanceDeduction);
                $advanceNarration = "Advance applied: Rs " . number_format($advanceDeduction, 2);
            }
        }

        $breakdown = $discounts['breakdown'];
        if ($advanceDeduction > 0) {
            $breakdown[] = [
                'source'      => 'advance',
                'source_id'   => 'auto',
                'label'       => 'Student Advance',
                'discount_type' => 'existing_credit',
                'discount_value' => $advanceDeduction,
                'amount'      => $advanceDeduction,
            ];
        }

        return [
            'status' => $existingVoucher ? 'existing' : 'ready',
            'existing_voucher_no' => $existingVoucher?->voucher_no,
            'student_enrollment_id' => $enrollment->id,
            'student_name' => $enrollment->student?->student_name ?? '-',
            'roll_no' => $enrollment->student?->roll_no ?? '-',
            'class_name' => $enrollment->classSection?->branchClass?->class?->class_name ?? '-',
            'section_name' => $enrollment->classSection?->section?->section_name ?? '-',
            'branch_name' => $enrollment->branch?->branch_name ?? '-',
            'fee_type_id' => $structure->fee_type_id,
            'fee_structure_id' => $structure->id,
            'fee_structure_version_id' => app(FeeStructureVersioningService::class)->currentVersionId($structure),
            'fee_type_name' => $feeType?->fee_name ?? '-',
            'academic_year_id' => $academicYearId,
            'month' => $month,
            'year' => $year,
            'generated_for' => $generatedFor,
            'original_amount' => $baseAmount,
            'concession_amount' => $discounts['totals']['concession'],
            'sibling_discount_amount' => $discounts['totals']['sibling_discount'],
            'scholarship_discount_amount' => $discounts['totals']['scholarship'],
            'discount_amount' => $discounts['total_discount'],
            'fine_amount' => $fineAmount,
            'arrears_amount' => $arrearsAmount,
            'net_amount' => $netAmount,
            'advance_deduction' => $advanceDeduction,
            'due_date' => $this->calculateDueDate($year, $month, $structure->due_day),
            'breakdown' => $breakdown,
            'carry_forward_records' => $carryForwardRecords,
        ];
    }

    private function calculateDiscounts($enrollment, int $feeTypeId, float $baseAmount, int $academicYearId, int $month, int $year): array
    {
        $discounts = [
            'total_discount' => 0.0,
            'totals' => [
                'concession' => 0.0,
                'sibling_discount' => 0.0,
                'scholarship' => 0.0,
            ],
            'breakdown' => [],
        ];

        $remainingDiscountable = $baseAmount;
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = (clone $periodStart)->endOfMonth();

        $concessions = StudentFeeConcession::active()
            ->where('student_enrollment_id', $enrollment->id)
            ->where(function ($q) use ($feeTypeId) {
                $q->where('fee_type_id', $feeTypeId)->orWhereNull('fee_type_id');
            })
            ->where(function ($q) use ($periodEnd) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $periodEnd);
            })
            ->where(function ($q) use ($periodStart) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $periodStart);
            })
            ->with('concessionType')
            ->get();

        foreach ($concessions as $concession) {
            $this->addDiscount($discounts, $remainingDiscountable, [
                'bucket' => 'concession',
                'source' => 'student_fee_concession',
                'source_id' => $concession->id,
                'label' => $concession->concessionType?->concession_name ?? 'Fee Concession',
                'discount_type' => $concession->discount_type,
                'discount_value' => (float) $concession->discount_value,
                'base_amount' => $baseAmount,
            ]);
        }

        if ($enrollment->sibling_order && $enrollment->sibling_order > 1) {
            $siblingRules = SiblingDiscountRule::active()
                ->where('child_number', $enrollment->sibling_order)
                ->where(function ($q) use ($feeTypeId) {
                    $q->where('applies_to_fee_type_id', $feeTypeId)->orWhereNull('applies_to_fee_type_id');
                })
                ->get();

            foreach ($siblingRules as $rule) {
                $this->addDiscount($discounts, $remainingDiscountable, [
                    'bucket' => 'sibling_discount',
                    'source' => 'sibling_discount_rule',
                    'source_id' => $rule->id,
                    'label' => "Sibling Discount (Child #{$rule->child_number})",
                    'discount_type' => $rule->discount_type,
                    'discount_value' => (float) $rule->discount_value,
                    'base_amount' => $baseAmount,
                ]);
            }
        }

        $studentScholarships = StudentScholarship::where('student_enrollment_id', $enrollment->id)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->where(function ($q) use ($periodEnd) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $periodEnd);
            })
            ->where(function ($q) use ($periodStart) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $periodStart);
            })
            ->whereHas('scholarship', function ($q) use ($feeTypeId) {
                $q->where('is_active', true)
                    ->where(function ($scholarshipQuery) use ($feeTypeId) {
                        $scholarshipQuery->where('applicable_fee_type_id', $feeTypeId)
                            ->orWhereNull('applicable_fee_type_id');
                    });
            })
            ->with('scholarship')
            ->get();

        foreach ($studentScholarships as $studentScholarship) {
            $scholarship = $studentScholarship->scholarship;
            if (!$scholarship) {
                continue;
            }

            $this->addDiscount($discounts, $remainingDiscountable, [
                'bucket' => 'scholarship',
                'source' => 'student_scholarship',
                'source_id' => $studentScholarship->id,
                'label' => $scholarship->scholarship_name,
                'discount_type' => $scholarship->discount_type,
                'discount_value' => (float) $scholarship->discount_value,
                'base_amount' => $baseAmount,
            ]);
        }

        $discounts['total_discount'] = round($discounts['total_discount'], 2);

        return $discounts;
    }

    private function addDiscount(array &$discounts, float &$remainingDiscountable, array $discount): void
    {
        if ($remainingDiscountable <= 0) {
            return;
        }

        $rawAmount = $discount['discount_type'] === 'percentage'
            ? ((float) $discount['base_amount'] * (float) $discount['discount_value']) / 100
            : (float) $discount['discount_value'];

        $amount = round(min(max(0, $rawAmount), $remainingDiscountable), 2);
        if ($amount <= 0) {
            return;
        }

        $remainingDiscountable = round($remainingDiscountable - $amount, 2);
        $bucket = $discount['bucket'];

        $discounts['total_discount'] += $amount;
        $discounts['totals'][$bucket] += $amount;
        $discounts['breakdown'][] = [
            'source' => $discount['source'],
            'source_id' => $discount['source_id'],
            'label' => $discount['label'],
            'discount_type' => $discount['discount_type'],
            'discount_value' => round((float) $discount['discount_value'], 2),
            'amount' => $amount,
        ];
    }

    private function calculateApplicableFines($enrollment, int $feeTypeId, int $month, int $year): float
    {
        return 0;
    }

    private function calculateStudentBalance($enrollmentId): float
    {
        $debits = StudentLedger::where('student_enrollment_id', $enrollmentId)
            ->where('transaction_type', 'debit')
            ->sum('amount');
        $credits = StudentLedger::where('student_enrollment_id', $enrollmentId)
            ->where('transaction_type', 'credit')
            ->sum('amount');

        return (float) $debits - (float) $credits;
    }

    private function generateVoucherNo(): string
    {
        $latest = FeeVoucher::withTrashed()->lockForUpdate()->latest('id')->first();
        $nextId = $latest ? $latest->id + 1 : 1;

        return 'VCH-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

    private function findActiveExistingVoucher(int $studentEnrollmentId, int $feeTypeId, string $generatedFor): ?FeeVoucher
    {
        return FeeVoucher::query()
            ->where('student_enrollment_id', $studentEnrollmentId)
            ->where('fee_type_id', $feeTypeId)
            ->where('generated_for', $generatedFor)
            ->first();
    }

    private function releaseTrashedDuplicateVoucherKeys(int $studentEnrollmentId, int $feeTypeId, string $generatedFor): void
    {
        FeeVoucher::onlyTrashed()
            ->where('student_enrollment_id', $studentEnrollmentId)
            ->where('fee_type_id', $feeTypeId)
            ->where('generated_for', $generatedFor)
            ->get()
            ->each(function (FeeVoucher $voucher) {
                $voucher->forceFill([
                    'generated_for' => 'DEL-' . $voucher->id,
                ])->saveQuietly();
            });
    }

    private function feeTypeAppliesToMonth($feeType, int $month): bool
    {
        if (!$feeType) {
            return false;
        }

        if (!($feeType->is_recurring ?? false)) {
            return true;
        }

        return in_array($month, $this->parseRecurringMonths($feeType->recurring_months ?? null), true);
    }

    private function parseRecurringMonths(?string $months): array
    {
        if (!$months) {
            return range(1, 12);
        }

        return array_map('intval', explode(',', $months));
    }

    private function summarizeRows(array $rows): array
    {
        $readyRows = array_values(array_filter($rows, fn ($row) => $row['status'] === 'ready'));
        $existingRows = array_values(array_filter($rows, fn ($row) => $row['status'] === 'existing'));

        return [
            'total_candidates' => count($rows),
            'ready_count' => count($readyRows),
            'existing_count' => count($existingRows),
            'original_amount' => round(array_sum(array_column($readyRows, 'original_amount')), 2),
            'discount_amount' => round(array_sum(array_column($readyRows, 'discount_amount')), 2),
            'fine_amount' => round(array_sum(array_column($readyRows, 'fine_amount')), 2),
            'net_amount' => round(array_sum(array_column($readyRows, 'net_amount')), 2),
        ];
    }

    private function statusFromAmounts(float $paidAmount, float $remainingAmount): string
    {
        if ($remainingAmount <= 0) {
            return 'paid';
        }

        return $paidAmount > 0 ? 'partial' : 'pending';
    }

    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        return $months[$month] ?? 'Unknown';
    }

    private function calculateDueDate(int $year, int $month, ?int $dueDay): string
    {
        $day = $dueDay ?? 10;
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        return sprintf('%04d-%02d-%02d', $year, $month, min($day, $daysInMonth));
    }

    public function previewOverdueFines(array $filters = []): array
    {
        $rows = $this->buildOverdueFineRows($filters);

        return [
            'success' => true,
            'summary' => $this->summarizeFineRows($rows),
            'rows' => $rows,
        ];
    }

    public function applyFinesToOverdueVouchers(array $filters = []): array
    {
        DB::beginTransaction();

        try {
            $rows = $this->buildOverdueFineRows($filters);

            $appliedCount = 0;
            $skippedCount = count(array_filter($rows, fn ($row) => $row['status'] !== 'ready'));

            foreach ($rows as $row) {
                if ($row['status'] !== 'ready') {
                    continue;
                }

                $voucher = FeeVoucher::lockForUpdate()->find($row['voucher_id']);
                $rule = FeeFineRule::active()->find($row['fine_rule_id']);

                if (!$voucher || !$rule || (float) $row['calculated_amount'] <= 0) {
                    $skippedCount++;
                    continue;
                }

                $alreadyApplied = FeeVoucherFine::where('voucher_id', $voucher->id)
                    ->where('fine_rule_id', $rule->id)
                    ->exists();

                if ($alreadyApplied) {
                    $skippedCount++;
                    continue;
                }

                $fineAmount = (float) $row['calculated_amount'];
                $fine = FeeVoucherFine::create([
                    'voucher_id' => $voucher->id,
                    'fine_rule_id' => $rule->id,
                    'days_overdue' => $row['days_overdue'],
                    'fine_type' => $rule->fine_type,
                    'fine_value' => $rule->fine_value,
                    'calculated_amount' => $fineAmount,
                    'applied_on' => $filters['as_of_date'] ?? now()->toDateString(),
                    'applied_by' => auth()->id(),
                    'is_waived' => false,
                    'notes' => 'Auto applied overdue fine',
                ]);

                app(FeeVoucherBalanceService::class)->sync($voucher);

                StudentLedger::create([
                    'student_enrollment_id' => $voucher->student_enrollment_id,
                    'transaction_type' => 'debit',
                    'amount' => $fineAmount,
                    'description' => 'Overdue fine applied: ' . $voucher->voucher_no,
                    'reference_type' => 'voucher_fine',
                    'reference_id' => $fine->id,
                    'balance_after' => $this->calculateStudentBalance($voucher->student_enrollment_id) + $fineAmount,
                    'created_by' => auth()->id(),
                ]);

                $appliedCount++;
            }

            DB::commit();

            return [
                'success' => true,
                'count' => $appliedCount,
                'skipped' => $skippedCount,
                'message' => "Applied {$appliedCount} overdue fines. Skipped {$skippedCount} already applied rules.",
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function buildOverdueFineRows(array $filters = []): array
    {
        $asOfDate = Carbon::parse($filters['as_of_date'] ?? now()->toDateString())->startOfDay();
        $branchId = $filters['branch_id'] ?? null;
        $classId = $filters['class_id'] ?? null;
        $academicYearId = $filters['academic_year_id'] ?? null;
        $feeTypeId = $filters['fee_type_id'] ?? null;

        $vouchers = FeeVoucher::whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', $asOfDate->toDateString())
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($feeTypeId, fn ($q) => $q->where('fee_type_id', $feeTypeId))
            ->whereHas('studentEnrollment', function ($q) use ($branchId, $classId) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }

                if ($classId) {
                    $q->whereHas('classSection.branchClass', fn ($classQuery) => $classQuery->where('class_id', $classId));
                }
            })
            ->with([
                'academicYear',
                'feeType',
                'studentEnrollment.student',
                'studentEnrollment.branch',
                'studentEnrollment.classSection.branchClass.class',
                'studentEnrollment.classSection.section',
            ])
            ->orderBy('due_date')
            ->get();

        $rows = [];

        foreach ($vouchers as $voucher) {
            $daysOverdue = $voucher->due_date
                ? $voucher->due_date->copy()->startOfDay()->diffInDays($asOfDate)
                : 0;

            if ($daysOverdue <= 0 || (float) $voucher->remaining_amount <= 0) {
                continue;
            }

            $rules = FeeFineRule::active()
                ->where(function ($q) use ($voucher) {
                    $q->whereNull('branch_id')
                        ->orWhere('branch_id', $voucher->studentEnrollment?->branch_id);
                })
                ->where(function ($q) use ($voucher) {
                    $q->where('applies_to_all_fee_types', true)
                        ->orWhere('fee_type_id', $voucher->fee_type_id);
                })
                ->where('days_after_due', '<=', $daysOverdue)
                ->orderBy('days_after_due')
                ->get();

            foreach ($rules as $rule) {
                $alreadyApplied = FeeVoucherFine::where('voucher_id', $voucher->id)
                    ->where('fine_rule_id', $rule->id)
                    ->exists();
                $amount = $this->calculateFineFromRule($voucher, $rule, $daysOverdue);
                $enrollment = $voucher->studentEnrollment;

                $rows[] = [
                    'status' => $alreadyApplied ? 'already_applied' : ($amount > 0 ? 'ready' : 'zero_amount'),
                    'voucher_id' => $voucher->id,
                    'voucher_no' => $voucher->voucher_no,
                    'student_name' => $enrollment?->student?->student_name ?? '-',
                    'roll_no' => $enrollment?->roll_number ?? $enrollment?->student?->roll_no ?? '-',
                    'branch_name' => $enrollment?->branch?->branch_name ?? '-',
                    'class_name' => $enrollment?->classSection?->branchClass?->class?->class_name ?? '-',
                    'section_name' => $enrollment?->classSection?->section?->section_name ?? '-',
                    'academic_year' => $voucher->academicYear?->year_name ?? '-',
                    'fee_type' => $voucher->feeType?->fee_name ?? '-',
                    'due_date' => $voucher->due_date?->format('Y-m-d'),
                    'days_overdue' => $daysOverdue,
                    'remaining_amount' => round((float) $voucher->remaining_amount, 2),
                    'fine_rule_id' => $rule->id,
                    'rule_label' => $rule->description ?: $this->fineRuleLabel($rule),
                    'fine_type' => $rule->fine_type,
                    'fine_value' => round((float) $rule->fine_value, 2),
                    'calculated_amount' => $amount,
                ];
            }
        }

        return $rows;
    }

    private function calculateFineFromRule(FeeVoucher $voucher, FeeFineRule $rule, int $daysOverdue): float
    {
        $baseAmount = max(0, (float) $voucher->remaining_amount);
        $fineAmount = match ($rule->fine_type) {
            'percentage' => ($baseAmount * (float) $rule->fine_value) / 100,
            'daily_fixed' => (float) $rule->fine_value * max(1, $daysOverdue),
            'daily_percentage' => (($baseAmount * (float) $rule->fine_value) / 100) * max(1, $daysOverdue),
            default => (float) $rule->fine_value,
        };

        if ($rule->max_fine && $fineAmount > $rule->max_fine) {
            $fineAmount = (float) $rule->max_fine;
        }

        return round(max(0, $fineAmount), 2);
    }

    private function summarizeFineRows(array $rows): array
    {
        $readyRows = array_values(array_filter($rows, fn ($row) => $row['status'] === 'ready'));
        $alreadyApplied = array_values(array_filter($rows, fn ($row) => $row['status'] === 'already_applied'));

        return [
            'total_candidates' => count($rows),
            'ready_count' => count($readyRows),
            'already_applied_count' => count($alreadyApplied),
            'voucher_count' => count(array_unique(array_column($readyRows, 'voucher_id'))),
            'fine_amount' => round(array_sum(array_column($readyRows, 'calculated_amount')), 2),
        ];
    }

    private function fineRuleLabel(FeeFineRule $rule): string
    {
        $type = str_replace('_', ' ', $rule->fine_type);
        $value = str_contains($rule->fine_type, 'percentage')
            ? round((float) $rule->fine_value, 2) . '%'
            : 'Rs ' . number_format((float) $rule->fine_value, 2);

        return ucwords($type) . ' ' . $value . ' after ' . $rule->days_after_due . ' days';
    }
}
