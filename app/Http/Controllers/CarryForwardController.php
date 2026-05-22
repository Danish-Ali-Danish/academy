<?php

namespace App\Http\Controllers;

use App\Models\CarryForwardSetting;
use App\Models\StudentCarryForward;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CarryForwardController extends Controller
{
    public function index(Request $request)
    {
        $settings = CarryForwardSetting::first() ?? new CarryForwardSetting();

        $query = StudentCarryForward::with(['studentEnrollment.student', 'studentEnrollment.classSection.branchClass.class', 'fromVoucher'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('month')) {
            $query->where('to_month_name', 'like', '%' . $request->month . '%');
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('studentEnrollment.student', function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                  ->orWhere('roll_no', 'like', "%{$search}%");
            });
        }

        $carryForwards = $query->paginate(15)->withQueryString();

        return Inertia::render('CarryForwards/Index', [
            'settings' => $settings,
            'carryForwards' => $carryForwards,
            'filters' => $request->only(['status', 'search', 'month']),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'is_enabled' => 'required|boolean',
            'scope' => 'required|in:full,fee_only,custom',
            'max_months' => 'required|integer|min:1|max:12',
        ]);

        $setting = CarryForwardSetting::first();
        if ($setting) {
            $setting->update($validated);
        } else {
            CarryForwardSetting::create($validated);
        }

        return redirect()->back()->with('success', 'Carry forward settings updated successfully.');
    }
}
