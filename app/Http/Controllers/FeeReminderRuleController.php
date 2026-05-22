<?php

namespace App\Http\Controllers;

use App\Models\FeeReminderRule;
use App\Models\Branch;
use App\Models\FeeType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeeReminderRuleController extends Controller
{
    public function index()
    {
        $rules = FeeReminderRule::with(['branch', 'feeType', 'templates'])->get();
        $branches = Branch::select('id', 'branch_name')->get();
        $feeTypes = FeeType::select('id', 'fee_name')->get();

        return Inertia::render('FeeReminders/Settings/Rules', [
            'rules' => $rules,
            'branches' => $branches,
            'feeTypes' => $feeTypes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rule_name' => 'required|string|max:255',
            'trigger_type' => 'required|string|in:before_due,on_due,after_due',
            'days_offset' => 'required|integer|min:0',
            'channel' => 'required|string|in:sms,whatsapp,email',
            'branch_id' => 'nullable|exists:branches,id',
            'fee_type_id' => 'nullable|exists:fee_types,id',
            'is_active' => 'boolean',
        ]);

        FeeReminderRule::create($validated);

        return back()->with('success', 'Rule created successfully');
    }

    public function update(Request $request, FeeReminderRule $rule)
    {
        $validated = $request->validate([
            'rule_name' => 'required|string|max:255',
            'trigger_type' => 'required|string|in:before_due,on_due,after_due',
            'days_offset' => 'required|integer|min:0',
            'channel' => 'required|string|in:sms,whatsapp,email',
            'branch_id' => 'nullable|exists:branches,id',
            'fee_type_id' => 'nullable|exists:fee_types,id',
            'is_active' => 'boolean',
        ]);

        $rule->update($validated);

        return back()->with('success', 'Rule updated successfully');
    }

    public function destroy(FeeReminderRule $rule)
    {
        $rule->delete();
        return back()->with('success', 'Rule deleted successfully');
    }
}
