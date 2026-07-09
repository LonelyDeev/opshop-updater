@extends('back.layouts.master')

@section('title', 'تاریخچه خریدها')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-bag"></i> تاریخچه خرید پکیج‌ها
                        </h5>
                    </div>
                    <div class="card-body">

                        {{-- آمار --}}
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h3 class="mb-0">{{ number_format($stats['total']) }}</h3>
                                        <small class="text-muted">کل خریدها</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-0">{{ number_format($stats['paid']) }}</h3>
                                        <small>پرداخت موفق</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body text-center">
                                        <h3 class="mb-0">{{ number_format($stats['pending']) }}</h3>
                                        <small>در انتظار</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-0">{{ number_format($stats['revenue']) }}</h3>
                                        <small>درآمد (تومان)</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="GET" class="mb-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label small">جستجو (transaction / مشتری)</label>
                                    <input type="text" name="search" value="{{ request('search') }}"
                                           class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">وضعیت</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">همه</option>
                                        <option value="pending" @if(request('status')=='pending') selected @endif>در انتظار</option>
                                        <option value="paid" @if(request('status')=='paid') selected @endif>پرداخت شده</option>
                                        <option value="failed" @if(request('status')=='failed') selected @endif>ناموفق</option>
                                        <option value="refunded" @if(request('status')=='refunded') selected @endif>بازگشت داده شده</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        <i class="fas fa-filter"></i> فیلتر
                                    </button>
                                </div>
                            </div>
                        </form>

                        @if ($purchases->count())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>پکیج</th>
                                        <th>مشتری</th>
                                        <th class="text-center">نسخه</th>
                                        <th class="text-center">طرح</th>
                                        <th class="text-center">مبلغ</th>
                                        <th>تراکنش</th>
                                        <th class="text-center">وضعیت</th>
                                        <th>تاریخ</th>
                                        <th class="text-center">عملیات</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($purchases as $purchase)
                                        <tr>
                                            <td>{{ $purchase->id }}</td>
                                            <td>
                                                {{ $purchase->package->name ?? '—' }}
                                                <br><small class="text-muted">{{ $purchase->package->slug ?? '' }}</small>
                                            </td>
                                            <td>
                                                {{ $purchase->customer->fullname ?? '—' }}
                                                <br><small class="text-muted">{{ $purchase->customer->mobile ?? '' }}</small>
                                            </td>
                                            <td class="text-center">
                                                @if ($purchase->version)
                                                    <span class="badge bg-info">v{{ $purchase->version->version }}</span>
                                                @else — @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($purchase->pricingPlan)
                                                    <small>{{ $purchase->pricingPlan->name }}</small>
                                                @else — @endif
                                            </td>
                                            <td class="text-center">{{ number_format($purchase->amount) }}</td>
                                            <td><code class="small">{{ $purchase->transaction_id ?? '—' }}</code></td>
                                            <td class="text-center">
                                                @switch($purchase->status)
                                                    @case('pending') <span class="badge bg-warning text-dark">در انتظار</span> @break
                                                    @case('paid') <span class="badge bg-success">موفق</span> @break
                                                    @case('failed') <span class="badge bg-danger">ناموفق</span> @break
                                                    @case('refunded') <span class="badge bg-dark">بازگشتی</span> @break
                                                @endswitch
                                            </td>
                                            <td>{{ jdate($purchase->created_at)->format('Y/m/d H:i') }}</td>
                                            <td class="text-center">
                                                <a href="{{ route('admin.packages.purchases.show', $purchase) }}"
                                                   class="btn btn-sm btn-outline-info" title="مشاهده">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            {{ $purchases->links() }}
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-shopping-bag" style="font-size: 3rem;"></i>
                                <p class="mt-3">هیچ خریدی ثبت نشده.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
