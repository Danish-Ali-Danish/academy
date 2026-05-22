<?php

namespace App\Observers;

use App\Models\FeeVoucher;
use App\Models\FeeVoucherEditHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;

class VoucherEditHistoryObserver
{
    public function updating(FeeVoucher $voucher): void
    {
        $original = $voucher->getOriginal();
        $changes  = $voucher->getDirty();

        if (empty($changes)) {
            return;
        }

        $filtered = Arr::except($changes, ['updated_at', 'created_at']);
        if (empty($filtered)) {
            return;
        }

        $formattedChanges = [];
        foreach ($filtered as $key => $newValue) {
            $formattedChanges[$key] = [
                'old' => $original[$key] ?? null,
                'new' => $newValue,
            ];
        }

        FeeVoucherEditHistory::create([
            'voucher_id'           => $voucher->id,
            'student_enrollment_id'=> $voucher->student_enrollment_id,
            'edit_reason'          => $voucher->edit_reason ?? 'System automatic update',
            'changes'              => $formattedChanges,
            'requires_approval'    => false,
            'approval_request_id'  => null,
            'edited_by'            => Auth::id(),
            'edited_at'            => now(),
        ]);
    }
}
