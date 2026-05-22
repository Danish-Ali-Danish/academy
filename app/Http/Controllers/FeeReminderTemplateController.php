<?php

namespace App\Http\Controllers;

use App\Models\FeeReminderTemplate;
use App\Models\FeeReminderRule;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeeReminderTemplateController extends Controller
{
    public function index()
    {
        $templates = FeeReminderTemplate::with(['rule', 'branch'])->get();
        $rules = FeeReminderRule::select('id', 'rule_name')->get();
        $branches = Branch::select('id', 'branch_name')->get();

        return Inertia::render('FeeReminders/Settings/Templates', [
            'templates' => $templates,
            'rules' => $rules,
            'branches' => $branches,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rule_id' => 'nullable|exists:fee_reminder_rules,id',
            'channel' => 'required|string|in:sms,whatsapp,email',
            'template_body' => 'required|string',
            'language' => 'required|string|max:10',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        FeeReminderTemplate::create($validated);

        return back()->with('success', 'Template created successfully');
    }

    public function update(Request $request, FeeReminderTemplate $template)
    {
        $validated = $request->validate([
            'rule_id' => 'nullable|exists:fee_reminder_rules,id',
            'channel' => 'required|string|in:sms,whatsapp,email',
            'template_body' => 'required|string',
            'language' => 'required|string|max:10',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $template->update($validated);

        return back()->with('success', 'Template updated successfully');
    }

    public function destroy(FeeReminderTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'Template deleted successfully');
    }
}
