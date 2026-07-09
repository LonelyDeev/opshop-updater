@extends('back.layouts.master')

@section('title', 'جزئیات خرید #' . $purchase->id)

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="feather icon-shopping-bag"></i> جزئیات خرید #{{ $purchase->id }}
                        </h5>
                        <a href="{{ route('admin.packages.purchases.index') }}" class="btn btn-sm btn-secondary">
                            <i class="feather icon-arrow-right"></i> بازگشت
                        </a>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2 mb-3">اطلاعات خرید</h6>
                                <table class="table table-sm table-borderless">
                                    <tr><th width="140">شناسه خرید:</th><td>#{{ $purchase->id }}</td></tr>
                                    <tr><th>پکیج:</th>
                                        <td>
                                            @if ($purchase->package)
                                                <a href="{{ route('admin.packages.show', $purchase->package) }}">
                                                    {{ $purchase->package->name }}
                                                </a>
                                            @else — @endif
                                        </td>
                                    </tr>
                                    <tr><th>نسخه:</th>
                                        <td>
                                            @if ($purchase->version)
                                                <span class="badge bg-info">v{{ $purchase->version->version }}</span>
                                            @else — @endif
                                        </td>
                                    </tr>
                                    <tr><th>طرح قیمت‌گذاری:</th>
                                        <td>
                                            @if ($purchase->pricingPlan)
                                                {{ $purchase->pricingPlan->name }}
                                                ({{ $purchase->pricingPlan->duration_label }})
                                            @else — @endif
                                        </td>
                                    </tr>
                                    <tr><th>مبلغ:</th>
                                        <td><strong>{{ number_format($purchase->amount) }} تومان</strong></td>
                                    </tr>
                                    <tr><th>درگاه:</th>
                                        <td>{{ $purchase->gateway ?? '—' }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2 mb-3">اطلاعات مشتری و پرداخت</h6>
                                <table class="table table-sm table-borderless">
                                    <tr><th width="140">مشتری:</th>
                                        <td>
                                            {{ $purchase->customer->fullname ?? '—' }}
                                            <br><small class="text-muted">{{ $purchase->customer->mobile ?? '' }}</small>
                                            <br><small class="text-muted">{{ $purchase->customer->website_url ?? '' }}</small>
                                        </td>
                                    </tr>
                                    <tr><th>Transaction ID:</th>
                                        <td><code>{{ $purchase->transaction_id ?? '—' }}</code></td>
                                    </tr>
                                    <tr><th>تاریخ خرید:</th>
                                        <td>{{ jdate($purchase->created_at)->format('Y/m/d H:i') }}</td>
                                    </tr>
                                    <tr><th>تاریخ پرداخت:</th>
                                        <td>
                                            @if ($purchase->paid_at)
                                                {{ jdate($purchase->paid_at)->format('Y/m/d H:i') }}
                                            @else — @endif
                                        </td>
                                    </tr>
                                    <tr><th>وضعیت:</th>
                                        <td>
                                            @switch($purchase->status)
                                                @case('pending') <span class="badge bg-warning text-dark">در انتظار</span> @break
                                                @case('paid') <span class="badge bg-success">موفق</span> @break
                                                @case('failed') <span class="badge bg-danger">ناموفق</span> @break
                                                @case('refunded') <span class="badge bg-dark">بازگشتی</span> @break
                                            @endswitch
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        @if ($purchase->license)
                            <div class="alert alert-success mt-3">
                                <h6 class="alert-heading">
                                    <i class="feather icon-key"></i> لایسنس صادر شده
                                </h6>
                                <p class="mb-1">کلید: <code class="text-primary">{{ $purchase->license->license_key }}</code></p>
                                <p class="mb-1">انقضا:
                                    @if ($purchase->license->expires_at)
                                        {{ jdate($purchase->license->expires_at)->format('Y/m/d') }}
                                        ({{ $purchase->license->days_remaining ?? 0 }} روز باقی‌مانده)
                                    @else
                                        <span class="badge bg-info">نامحدود</span>
                                    @endif
                                </p>
                                <a href="{{ route('admin.packages.licenses.show', $purchase->license) }}" class="btn btn-sm btn-outline-success mt-2">
                                    مشاهده لایسنس
                                </a>
                            </div>
                        @endif

                        @if ($purchase->meta)
                            <div class="mt-4">
                                <h6>اطلاعات اضافی (Meta)</h6>
                                <pre class="bg-light p-3"><code>{{ json_encode($purchase->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
