<?php

namespace App\Observers;

use App\Models\FeeVoucher;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class VoucherObserver
{
    public function updating(FeeVoucher $voucher)
    {
        $original = $voucher->getOriginal();
        $changes  = $voucher->getDirty();

        if (empty($changes)) {
            return;
        }

        ActivityLog::create([
            'user_id'    => Auth::id() ?? null,
            'branch_id'  => $voucher->branch_id ?? null,
            'model_type' => FeeVoucher::class,
            'model_id'   => $voucher->id,
            'action'     => 'update',
            'description'=> 'Voucher updated',
            'old_values' => $original,
            'new_values' => $voucher->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
