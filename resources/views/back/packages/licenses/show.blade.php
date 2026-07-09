@extends('back.layouts.master')

@section('title', 'جزئیات لایسنس')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-key"></i> جزئیات لایسنس
                        </h5>
                        <a href="{{ route('admin.packages.licenses.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-right"></i> بازگشت
                        </a>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr><th width="140">کلید لایسنس:</th>
                                        <td><code class="text-primary" style="font-size: 1rem;">{{ $license->license_key }}</code></td>
                                    </tr>
                                    <tr><th>پکیج:</th>
                                        <td>{{ $license->package->name ?? '—' }} ({{ $license->package->slug ?? '—' }})</td>
                                    </tr>
                                    <tr><th>مشتری:</th>
                                        <td>
                                            {{ $license->customer->fullname ?? '—' }}
                                            <br><small class="text-muted">{{ $license->customer->mobile ?? '' }}</small>
                                            <br><small class="text-muted">{{ $license->customer->website_url ?? '' }}</small>
                                        </td>
                                    </tr>
                                    <tr><th>وضعیت:</th>
                                        <td>
                                            @switch($license->status)
                                                @case('active') <span class="badge bg-success">فعال</span> @break
                                                @case('expired') <span class="badge bg-warning text-dark">منقضی</span> @break
                                                @case('revoked') <span class="badge bg-danger">باطل شده</span> @break
                                            @endswitch
                                        </td>
                                    </tr>
                                    <tr><th>مدت:</th>
                                        <td>
                                            @if ($license->duration_months === 0)
                                                نامحدود
                                            @else
                                                {{ $license->duration_months }} ماه
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr><th width="140">شروع:</th>
                                        <td>{{ $license->starts_at ? jdate($license->starts_at)->format('Y/m/d H:i') : '—' }}</td>
                                    </tr>
                                    <tr><th>انقضا:</th>
                                        <td>
                                            @if ($license->expires_at)
                                                {{ jdate($license->expires_at)->format('Y/m/d H:i') }}
                                            @else
                                                <span class="badge bg-info">نامحدود</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr><th>روزهای باقی‌مانده:</th>
                                        <td>
                                            @if ($license->isUnlimited())
                                                <span class="badge bg-info">∞</span>
                                            @elseif ($license->isExpired())
                                                <span class="badge bg-danger">منقضی شده</span>
                                            @else
                                                <span class="badge bg-success">{{ $license->days_remaining }} روز</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr><th>خرید مرتبط:</th>
                                        <td>
                                            @if ($license->purchase)
                                                <a href="{{ route('admin.packages.purchases.show', $license->purchase) }}">
                                                    #{{ $license->purchase->id }} - {{ number_format($license->purchase->amount) }} تومان
                                                </a>
                                            @else — @endif
                                        </td>
                                    </tr>
                                    <tr><th>تمدید شده از:</th>
                                        <td>
                                            @if ($license->renewedFrom)
                                                <code>{{ $license->renewedFrom->license_key }}</code>
                                            @else — @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        @if ($license->notes)
                            <div class="alert alert-light border">
                                <strong>یادداشت:</strong> {{ $license->notes }}
                            </div>
                        @endif

                        {{-- تمدیدها --}}
                        @if ($license->renewals->count())
                            <h6 class="mt-4"><i class="fas fa-refresh-cw"></i> تمدیدهای این لایسنس</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                    <tr>
                                        <th>کلید لایسنس جدید</th>
                                        <th class="text-center">تاریخ صدور</th>
                                        <th class="text-center">انقضا</th>
                                        <th class="text-center">وضعیت</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($license->renewals as $renewal)
                                        <tr>
                                            <td><code>{{ $renewal->license_key }}</code></td>
                                            <td class="text-center">{{ jdate($renewal->created_at)->format('Y/m/d') }}</td>
                                            <td class="text-center">
                                                {{ $renewal->expires_at ? jdate($renewal->expires_at)->format('Y/m/d') : 'نامحدود' }}
                                            </td>
                                            <td class="text-center">
                                                @switch($renewal->status)
                                                    @case('active') <span class="badge bg-success">فعال</span> @break
                                                    @case('expired') <span class="badge bg-warning text-dark">منقضی</span> @break
                                                    @case('revoked') <span class="badge bg-danger">باطل</span> @break
                                                @endswitch
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            @if ($license->status === 'active')
                                <form action="{{ route('admin.packages.licenses.revoke', $license) }}" method="POST"
                                      onsubmit="return confirm('ابطال لایسنس؟ این عمل قابل بازگشت نیست.')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-x-circle"></i> ابطال لایسنس
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.packages.licenses.activate', $license) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas facheck-circle"></i> فعال‌سازی مجدد
                                    </button>
                                </form>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
