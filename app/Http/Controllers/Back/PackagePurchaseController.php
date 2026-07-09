<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\PackagePurchase;
use Illuminate\Http\Request;

class PackagePurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = PackagePurchase::with(['package', 'customer', 'pricingPlan', 'license']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('fullname', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchases = $query->latest()->paginate(20)->appends($request->all());

        $stats = [
            'total'    => PackagePurchase::count(),
            'paid'     => PackagePurchase::where('status', 'paid')->count(),
            'pending'  => PackagePurchase::where('status', 'pending')->count(),
            'revenue'  => PackagePurchase::where('status', 'paid')->sum('amount'),
        ];

        return view('back.packages.purchases.index', compact('purchases', 'stats'));
    }

    public function show(PackagePurchase $purchase)
    {
        $purchase->load(['package', 'customer', 'pricingPlan', 'license', 'version']);
        return view('back.packages.purchases.show', compact('purchase'));
    }
}
