<?php

namespace App\Http\Controllers;

use App\Models\FeeReminderLog;
use App\Models\FeeReminderFollowup;
use App\Models\FeeVoucher;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class FeeReminderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('draw')) {
            return $this->getDataTablesLogs($request);
        }

        return Inertia::render('FeeReminders/Index');
    }

    private function getDataTablesLogs(Request $request)
    {
        $query = FeeReminderLog::with(['voucher.feeType', 'enrollment.student', 'template']);

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->whereHas('enrollment.student', function($sq) use ($search) {
                    $sq->where('student_name', 'like', "%{$search}%")
                       ->orWhere('admission_no', 'like', "%{$search}%");
                })->orWhere('channel', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        $totalData = $query->count();

        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        
        // Define exact mapping between DataTables columns and DB columns
        $columns = [
            0 => 'id',
            1 => 'student_enrollment_id',
            2 => 'voucher_id',
            3 => 'channel',
            4 => 'status',
            5 => 'sent_at'
        ];

        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDir);
        }

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $logs = $query->skip($start)->take($length)->get();

        $data = $logs->map(function ($log, $index) use ($start) {
            return [
                'DT_RowIndex'  => $start + $index + 1,
                'id'           => $log->id,
                'student_name' => $log->enrollment->student->student_name ?? '-',
                'voucher_no'   => $log->voucher->voucher_no ?? '-',
                'channel'      => ucfirst($log->channel),
                'status'       => ucfirst($log->status),
                'sent_at'      => $log->sent_at ? $log->sent_at->format('d M, Y h:i A') : '-',
                'action'       => '
                    <button onclick="viewLog(' . $log->id . ')" class="text-blue-600 hover:text-blue-800">
                        View
                    </button>
                ',
            ];
        });

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalData,
            'recordsFiltered' => $totalData,
            'data'            => $data,
        ]);
    }

    public function show(FeeReminderLog $feeReminder) // Route parameter is named fee_reminder
    {
        $feeReminder->load(['voucher', 'enrollment.student', 'template', 'followups.handler']);
        return response()->json($feeReminder);
    }
    
    public function storeFollowup(Request $request, FeeReminderLog $feeReminder)
    {
        $validated = $request->validate([
            'outcome' => 'required|in:promised_to_pay,no_response,paid,call_back',
            'promised_pay_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        
        $validated['handled_by'] = auth()->id();
        $validated['reminder_log_id'] = $feeReminder->id;
        
        FeeReminderFollowup::create($validated);
        
        return back()->with('success', 'Follow-up saved successfully');
    }
}
