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
            'duration_months' => 'required|integer|min:1', // مدت اشتراک به ماه
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:pending,paid,failed',
            'status' => 'required|in:active,inactive,expired',
            'description' => 'nullable|string',
        ]);

        // محاسبه تاریخ انقضا
        $startDate = Carbon::parse($validated['start_date']);
        $expiresAt = $startDate->copy()->addMonths($validated['duration_months']);

        // محاسبه مبلغ نهایی
        $price = $validated['price'];
        $discount = $validated['discount'] ?? 0;
        $finalAmount = max(0, $price - $discount);

        Subscription::create([
            'customer_id' => $validated['customer_id'],
            'project_id' => $validated['project_id'],
            'start_date' => $startDate,
            'expires_at' => $expiresAt,
            'status' => $validated['status'],
            'price' => $price,
            'discount' => $discount,
            'final_amount' => $finalAmount,
            'payment_status' => $validated['payment_status'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'اشتراک با موفقیت ثبت شد.');
    }

    public function edit(Subscription $subscription)
    {
        $customers = Customer::all();
        $projects = Project::all();
        // محاسبه مدت زمان باقی‌مانده برای نمایش در فرم (اختیاری)
        $duration = 0;
        if ($subscription->start_date && $subscription->expires_at) {
            $duration = $subscription->start_date->diffInMonths($subscription->expires_at);
        }

        return view('back.subscriptions.edit', compact('subscription', 'customers', 'projects', 'duration'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'project_id' => 'required|exists:projects,id',
            'start_date' => 'required|date',
            'duration_months' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:pending,paid,failed',
            'status' => 'required|in:active,inactive,expired',
            'description' => 'nullable|string',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $expiresAt = $startDate->copy()->addMonths($validated['duration_months']);

        $price = $validated['price'];
        $discount = $validated['discount'] ?? 0;
        $finalAmount = max(0, $price - $discount);

        $subscription->update([
            'customer_id' => $validated['customer_id'],
            'project_id' => $validated['project_id'],
            'start_date' => $startDate,
            'expires_at' => $expiresAt,
            'status' => $validated['status'],
            'price' => $price,
            'discount' => $discount,
            'final_amount' => $finalAmount,
            'payment_status' => $validated['payment_status'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'اشتراک با موفقیت ویرایش شد.');
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();
        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'اشتراک حذف شد.');
    }
}
