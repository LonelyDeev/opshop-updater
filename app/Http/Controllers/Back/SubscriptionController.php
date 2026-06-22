<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Customer;
use App\Models\Project;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::with(['customer', 'project'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('back.subscriptions.index', compact('subscriptions'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->get();
        $projects = Project::where('status', 'active')->get();

        return view('back.subscriptions.create', compact('customers', 'projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'project_id' => 'required|exists:projects,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,expired,suspended',
            'description' => 'nullable|string|max:1000',
        ]);

        // اگر تاریخ پایان گذشته باشد، وضعیت را خودکار منقضی کن
        if (Carbon::parse($validated['end_date'])->isPast()) {
            $validated['status'] = 'expired';
        }

        Subscription::create($validated);

        return redirect()->route('subscriptions.index')
            ->with('success', 'اشتراک با موفقیت ثبت شد.');
    }

    public function edit(Subscription $subscription)
    {
        $customers = Customer::all();
        $projects = Project::all();

        return view('back.subscriptions.edit', compact('subscription', 'customers', 'projects'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'project_id' => 'required|exists:projects,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,expired,suspended',
            'description' => 'nullable|string|max:1000',
        ]);

        if (Carbon::parse($validated['end_date'])->isPast() && $validated['status'] !== 'suspended') {
            $validated['status'] = 'expired';
        }

        $subscription->update($validated);

        return redirect()->route('subscriptions.index')
            ->with('success', 'اشتراک با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();

        return redirect()->route('subscriptions.index')
            ->with('success', 'اشتراک حذف شد.');
    }

    // اکشن کمکی برای تمدید سریع (اختیاری اما کاربردی)
    public function extend(Request $request, Subscription $subscription)
    {
        $request->validate([
            'days' => 'required|integer|min:1'
        ]);

        $newEndDate = $subscription->end_date->addDays($request->days);

        // اگر تاریخ جدید در آینده است، وضعیت را فعال کن
        $newStatus = $newEndDate->isFuture() ? 'active' : 'expired';

        $subscription->update([
            'end_date' => $newEndDate,
            'status' => $newStatus
        ]);

        return back()->with('success', "اشتراک به مدت {$request->days} روز تمدید شد.");
    }
}
