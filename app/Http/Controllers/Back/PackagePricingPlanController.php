<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackagePricingPlan;
use Illuminate\Http\Request;

class PackagePricingPlanController extends Controller
{
    public function index(Package $package)
    {
        $plans = $package->pricingPlans()->orderBy('sort_order')->get();
        return view('back.packages.plans.index', compact('package', 'plans'));
    }

    public function create(Package $package)
    {
        return view('back.packages.plans.create', compact('package'));
    }

    public function store(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:50',
            'duration_months' => 'required|integer|min:0|max:120',
            'price'           => 'required|integer|min:0',
            'discount_price'  => 'nullable|integer|min:0|lt:price',
            'is_one_time'     => 'boolean',
            'description'     => 'nullable|string|max:500',
            'is_active'       => 'boolean',
            'sort_order'      => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['is_one_time'] = $request->has('is_one_time');
        $validated['discount_price'] = $validated['discount_price'] ?? null;
        $validated['description'] = $validated['description'] ?? null;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $package->pricingPlans()->create($validated);

        return redirect()->route('admin.packages.plans.index', $package)
            ->with('success', 'طرح قیمت‌گذاری ایجاد شد.');
    }

    public function edit(Package $package, PackagePricingPlan $plan)
    {
        return view('back.packages.plans.edit', compact('package', 'plan'));
    }

    public function update(Request $request, Package $package, PackagePricingPlan $plan)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:50',
            'duration_months' => 'required|integer|min:0|max:120',
            'price'           => 'required|integer|min:0',
            'discount_price'  => 'nullable|integer|min:0|lt:price',
            'is_one_time'     => 'boolean',
            'description'     => 'nullable|string|max:500',
            'is_active'       => 'boolean',
            'sort_order'      => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['is_one_time'] = $request->has('is_one_time');
        $validated['discount_price'] = $validated['discount_price'] ?? null;
        $validated['description'] = $validated['description'] ?? null;

        $plan->update($validated);

        return redirect()->route('admin.packages.plans.index', $package)
            ->with('success', 'طرح قیمت‌گذاری به‌روزرسانی شد.');
    }

    public function destroy(Package $package, PackagePricingPlan $plan)
    {
        $plan->delete();
        return redirect()->route('admin.packages.plans.index', $package)
            ->with('success', 'طرح قیمت‌گذاری حذف شد.');
    }
}
