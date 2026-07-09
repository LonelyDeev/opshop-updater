<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageLicense;
use App\Services\LicenseService;
use Illuminate\Http\Request;

class PackageLicenseController extends Controller
{
    public function __construct(private LicenseService $licenseService) {}

    public function index(Request $request)
    {
        $query = PackageLicense::with(['package', 'customer']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('license_key', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('fullname', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('package_id')) {
            $query->where('package_id', $request->package_id);
        }

        $licenses = $query->latest()->paginate(20)->appends($request->all());
        $packages = Package::orderBy('name')->get();

        return view('back.packages.licenses.index', compact('licenses', 'packages'));
    }

    public function show(PackageLicense $license)
    {
        $license->load(['package', 'customer', 'purchase', 'renewedFrom', 'renewals']);
        return view('back.packages.licenses.show', compact('license'));
    }

    public function revoke(PackageLicense $license)
    {
        $license->revoke();
        return redirect()->back()
            ->with('success', 'لایسنس باطل شد.');
    }

    public function activate(PackageLicense $license)
    {
        $license->update(['status' => PackageLicense::STATUS_ACTIVE]);
        return redirect()->back()
            ->with('success', 'لایسنس فعال شد.');
    }

    public function expireOld()
    {
        $count = $this->licenseService->expireOldLicenses();
        return redirect()->back()
            ->with('success', "{$count} لایسنس منقضی به‌روزرسانی شد.");
    }
}
