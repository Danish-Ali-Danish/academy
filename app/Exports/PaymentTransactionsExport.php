<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentTransactionsExport implements FromCollection, WithHeadings
{
    protected $transactions;

    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'ID', 'Transaction ID', 'Receipt Number', 'Fee Voucher ID', 'Student ID',
            'Branch ID', 'Amount', 'Payment Method', 'Payment Gateway',
            'Transaction Status', 'Reference Number', 'Transaction Date',
            'Created At', 'Updated At',
        ];
    }
}
