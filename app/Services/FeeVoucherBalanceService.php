<?php

namespace App\Services;

use App\Models\FeeWaiver;
use App\Models\FeeRefund;
use App\Models\FeeVoucher;

class FeeVoucherBalanceService
{
    public function sync(FeeVoucher $voucher): FeeVoucher
    {
        $voucher->loadMissing(['payments', 'fines', 'waiver']);

        $paymentIds = $voucher->payments()->pluck('id');
        $grossPaidAmount = (float) $voucher->payments()->sum('paid_amount');
        $approvedRefundAmount = $paymentIds->isEmpty()
            ? 0
            : (float) FeeRefund::query()
                ->whereIn('payment_id', $paymentIds)
                ->whereRaw('LOWER(status) = ?', ['approved'])
                ->sum('refund_amount');
        $paidAmount = max(0, round($grossPaidAmount - $approvedRefundAmount, 2));
        $breakdownCount = $voucher->discountBreakdowns()->count();
        $discountAmount = $breakdownCount > 0
            ? (float) $voucher->discountBreakdowns()->sum('calculated_amount')
            : (float) $voucher->discount_amount;
        $fineAmount = (float) $voucher->fines()
            ->where('is_waived', false)
            ->sum('calculated_amount');
        $waiverAmount = (float) FeeWaiver::query()
            ->where('voucher_id', $voucher->id)
            ->whereRaw('LOWER(status) = ?', ['approved'])
            ->sum('waived_amount');

        $netAmount = max(
            0,
            round(
                (float) $voucher->original_amount
                - $discountAmount
                + $fineAmount
                - $waiverAmount,
                2
            )
        );

        $remainingAmount = max(0, round($netAmount - $paidAmount, 2));

        $voucher->forceFill([
            'fine_amount' => round($fineAmount, 2),
            'discount_amount' => round($discountAmount, 2),
            'net_amount' => $netAmount,
            'paid_amount' => round($paidAmount, 2),
            'remaining_amount' => $remainingAmount,
            'status' => $this->statusFromAmounts($paidAmount, $remainingAmount, $netAmount),
        ])->save();

        return $voucher->refresh();
    }

    public function statusFromAmounts(float $paidAmount, float $remainingAmount, float $netAmount): string
    {
        if ($netAmount <= 0 || $remainingAmount <= 0) {
            return 'paid';
        }

        return $paidAmount > 0 ? 'partial' : 'pending';
    }
}
