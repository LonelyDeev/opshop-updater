@extends('back.layouts.master')

@section('title', 'داشبورد مدیریت')

@section('content')
    <div class="container-fluid">
        <h2 class="mb-4 text-gray-800">داشبورد مدیریت</h2>

        {{-- =============================== --}}
        {{-- ردیف اول: کارت‌های آمار کلی --}}
        {{-- =============================== --}}
        <div class="row">
            <!-- کارت مشتریان -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-primary shadow h-100 py-2" style="border-right: 4px solid #4e73df; border-left: none;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">کل مشتریان</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalCustomers }}</div>
                                <small class="text-success">{{ $activeCustomers }} فعال</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- کارت آپدیت‌ها -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-success shadow h-100 py-2" style="border-right: 4px solid #1cc88a; border-left: none;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">آپدیت‌های منتشر شده</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $publishedUpdates }} / {{ $totalUpdates }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-code-branch fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- کارت اشتراک‌ها -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-info shadow h-100 py-2" style="border-right: 4px solid #36b9cc; border-left: none;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">اشتراک‌های فعال</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeSubscriptions }}</div>
                                <small class="text-muted">از {{ $totalSubscriptions }} کل</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- کارت درآمد کل (اشتراک + پکیج) -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-warning shadow h-100 py-2" style="border-right: 4px solid #f6c23e; border-left: none;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">درآمد کل (تومان)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($grandTotalRevenue ?? 0) }}</div>
                                <small class="text-muted">
                                    اشتراک: {{ number_format($totalRevenue ?? 0) }} | پکیج: {{ number_format($packageRevenue ?? 0) }}
                                </small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- =============================== --}}
        {{-- ردیف دوم: کارت‌های آمار پکیج‌ها --}}
        {{-- =============================== --}}
        <div class="row">
            <!-- کارت پکیج‌ها -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-purple shadow h-100 py-2" style="border-right: 4px solid #8b5cf6; border-left: none;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-purple text-uppercase mb-1" style="color:#8b5cf6;">کل پکیج‌ها</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalPackages }}</div>
                                <small class="text-success">{{ $activePackages }} فعال</small>
                                <small class="text-muted"> | {{ $freePackages }} رایگان، {{ $paidPackages }} پولی</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-box fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- کارت لایسنس‌های فعال -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-success shadow h-100 py-2" style="border-right: 4px solid #10b981; border-left: none;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">لایسنس‌های فعال</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeLicenses }}</div>
                                @if ($expiredLicenses > 0)
                                    <small class="text-warning">{{ $expiredLicenses }} منقضی</small>
                                @endif
                                @if ($revokedLicenses > 0)
                                    <small class="text-danger"> | {{ $revokedLicenses }} ابطال شده</small>
                                @endif
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-key fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- کارت خریدهای پکیج -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-info shadow h-100 py-2" style="border-right: 4px solid #0ea5e9; border-left: none;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">خریدهای پکیج</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $paidPurchases }}</div>
                                <small class="text-muted">
                                    از {{ $totalPurchases }} کل
                                </small>
                                @if ($pendingPurchases > 0)
                                    <small class="text-warning"> | {{ $pendingPurchases }} در انتظار</small>
                                @endif
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-shopping-bag fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- کارت دانلودهای پکیج -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-warning shadow h-100 py-2" style="border-right: 4px solid #f59e0b; border-left: none;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">دانلودهای پکیج</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalDownloads) }}</div>
                                <small class="text-success">درآمد پکیج: {{ number_format($packageRevenue) }} تومان</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-download fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- =============================== --}}
        {{-- ردیف نمودارها --}}
        {{-- =============================== --}}
        <div class="row">
            <!-- نمودار مشتریان -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">روند ثبت‌نام مشتریان (6 ماه اخیر)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="customerChart" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- نمودار وضعیت آپدیت‌ها -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">وضعیت آپدیت‌ها</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                            <canvas id="updateStatusChart"></canvas>
                        </div>
                        <div class="mt-4 text-center small">
                            <span class="mr-2"><i class="fas fa-circle text-success"></i> منتشر شده</span>
                            <span class="mr-2"><i class="fas fa-circle text-warning"></i> پیش‌نویس</span>
                            <span class="mr-2"><i class="fas fa-circle text-secondary"></i> آرشیو</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- =============================== --}}
        {{-- نمودار خرید پکیج‌ها (جدید) --}}
        {{-- =============================== --}}
        <div class="row">
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">روند خرید پکیج‌ها و درآمد (6 ماه اخیر)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="packagePurchaseChart" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- نمودار وضعیت لایسنس‌ها -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">وضعیت لایسنس‌ها</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                            <canvas id="licenseStatusChart"></canvas>
                        </div>
                        <div class="mt-4 text-center small">
                            <span class="mr-2"><i class="fas fa-circle" style="color:#10b981;"></i> فعال</span>
                            <span class="mr-2"><i class="fas fa-circle text-warning"></i> منقضی</span>
                            <span class="mr-2"><i class="fas fa-circle text-danger"></i> باطل شده</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- =============================== --}}
        {{-- ردیف جداول: آخرین فعالیت‌ها --}}
        {{-- =============================== --}}
        <div class="row">
            <!-- آخرین مشتریان -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">آخرین مشتریان ثبت‌نام کرده</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                <tr>
                                    <th>نام</th>
                                    <th>سایت</th>
                                    <th>تاریخ</th>
                                    <th>اشتراک‌ها</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($recentCustomers as $customer)
                                    <tr>
                                        <td>{{ $customer->name }}</td>
                                        <td><a href="{{ $customer->website_url }}" target="_blank" class="text-truncate d-block" style="max-width: 150px;">{{ $customer->website_url }}</a></td>
                                        <td>{{ $customer->created_at->diffForHumans() }}</td>
                                        <td><span class="badge bg-info">{{ $customer->subscriptions_count }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center">موردی یافت نشد.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-light w-100 mt-2">مشاهده همه مشتریان</a>
                    </div>
                </div>
            </div>

            <!-- آخرین آپدیت‌ها -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-success">آخرین آپدیت‌های منتشر شده</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                <tr>
                                    <th>عنوان</th>
                                    <th>پروژه</th>
                                    <th>نسخه</th>
                                    <th>تاریخ</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($recentUpdates as $update)
                                    <tr>
                                        <td>{{ $update->title }}</td>
                                        <td>{{ $update->project->name ?? '-' }}</td>
                                        <td><span class="badge bg-secondary">{{ $update->version }}</span></td>
                                        <td>{{ $update->created_at->diffForHumans() }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center">آپدیتی یافت نشد.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <a href="{{ route('admin.updates.index') }}" class="btn btn-sm btn-light w-100 mt-2">مشاهده همه آپدیت‌ها</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- =============================== --}}
        {{-- ردیف جداول پکیج‌ها (جدید) --}}
        {{-- =============================== --}}
        <div class="row">
            <!-- آخرین خریدهای پکیج -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold" style="color:#8b5cf6;">آخرین خریدهای پکیج</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                <tr>
                                    <th>پکیج</th>
                                    <th>مشتری</th>
                                    <th>مبلغ</th>
                                    <th>تاریخ</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($recentPackagePurchases as $purchase)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.packages.show', $purchase->package) }}">
                                                {{ $purchase->package->name ?? '—' }}
                                            </a>
                                        </td>
                                        <td>{{ $purchase->customer->fullname ?? $purchase->customer->name ?? '—' }}</td>
                                        <td><strong>{{ number_format($purchase->amount) }}</strong></td>
                                        <td>{{ $purchase->paid_at ? $purchase->paid_at->diffForHumans() : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center">خریدی ثبت نشده.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <a href="{{ route('admin.packages.purchases.index') }}" class="btn btn-sm btn-light w-100 mt-2">مشاهده همه خریدها</a>
                    </div>
                </div>
            </div>

            <!-- پکیج‌های پرفروش -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold" style="color:#f59e0b;">پکیج‌های پرفروش</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>نام پکیج</th>
                                    <th class="text-center">خریدها</th>
                                    <th class="text-center">دانلودها</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($topPackages as $i => $package)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            <a href="{{ route('admin.packages.show', $package) }}">{{ $package->name }}</a>
                                            <br><small class="text-muted">{{ $package->slug }}</small>
                                        </td>
                                        <td class="text-center"><span class="badge bg-success">{{ $package->paid_purchases_count }}</span></td>
                                        <td class="text-center">{{ number_format($package->downloads_count) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center">هنوز پکیجی فروخته نشده.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <a href="{{ route('admin.packages.index') }}" class="btn btn-sm btn-light w-100 mt-2">مشاهده همه پکیج‌ها</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- =============================== --}}
        {{-- لایسنس‌های در حال انقضا (جدید) --}}
        {{-- =============================== --}}
        @if ($expiringLicenses->count())
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow mb-4 border-warning">
                    <div class="card-header py-3 bg-warning text-dark">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-exclamation-triangle"></i>
                            لایسنس‌های در حال انقضا (14 روز آینده)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                <tr>
                                    <th>کلید لایسنس</th>
                                    <th>پکیج</th>
                                    <th>مشتری</th>
                                    <th class="text-center">تاریخ انقضا</th>
                                    <th class="text-center">روزهای باقی‌مانده</th>
                                    <th class="text-center">عملیات</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($expiringLicenses as $license)
                                    <tr>
                                        <td><code class="small">{{ $license->license_key }}</code></td>
                                        <td>{{ $license->package->name ?? '—' }}</td>
                                        <td>{{ $license->customer->fullname ?? $license->customer->name ?? '—' }}</td>
                                        <td class="text-center">{{ jdate($license->expires_at)->format('Y/m/d') }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark">{{ $license->days_remaining }} روز</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.packages.licenses.show', $license) }}"
                                               class="btn btn-sm btn-outline-info">
                                                <i class="feather icon-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>

    <style>
        .card { border: none; border-radius: 0.5rem; }
        .text-xs { font-size: .7rem; }
        .font-weight-bold { font-weight: 700 !important; }
        .shadow { box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15)!important; }
        .py-2 { padding-top: .5rem!important; padding-bottom: .5rem!important; }
        .h-100 { height: 100%!important; }
        .border-start-primary { border-right: 4px solid #4e73df !important; border-left: none !important; }
        .border-start-success { border-right: 4px solid #1cc88a !important; border-left: none !important; }
        .border-start-info     { border-right: 4px solid #36b9cc !important; border-left: none !important; }
        .border-start-warning  { border-right: 4px solid #f6c23e !important; border-left: none !important; }
        .border-start-purple   { border-right: 4px solid #8b5cf6 !important; border-left: none !important; }

        .card-body { position: relative; overflow: hidden; }
        canvas { max-width: 100%; height: auto !important; }
    </style>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Chart.defaults.font.family = "'Vazirmatn', 'Tahoma', sans-serif";
            Chart.defaults.color = '#5a5c69';

            // --- 1. نمودار خطی مشتریان ---
            const customerCanvas = document.getElementById('customerChart');
            if (customerCanvas) {
                new Chart(customerCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: @json($customerLabels),
                        datasets: [{
                            label: 'مشتریان جدید',
                            data: @json($customerData),
                            lineTension: 0.3,
                            backgroundColor: 'rgba(78, 115, 223, 0.05)',
                            borderColor: 'rgba(78, 115, 223, 1)',
                            pointRadius: 3,
                            pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                            pointBorderColor: 'rgba(78, 115, 223, 1)',
                            pointHoverRadius: 5,
                            borderWidth: 2,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { grid: { display: false } },
                            y: { grid: { color: '#eaecf4' }, ticks: { stepSize: 1, beginAtZero: true } }
                        }
                    }
                });
            }

            // --- 2. نمودار دوناتی وضعیت آپدیت‌ها ---
            const updateCanvas = document.getElementById('updateStatusChart');
            if (updateCanvas) {
                const pStatus = parseInt('{{ $publishedStatus ?? 0 }}') || 0;
                const dUpdates = parseInt('{{ $draftUpdates ?? 0 }}') || 0;
                const aUpdates = parseInt('{{ $archivedUpdates ?? 0 }}') || 0;
                const total = pStatus + dUpdates + aUpdates;

                let chartData = total === 0 ? [1] : [pStatus, dUpdates, aUpdates];
                let chartColors = total === 0 ? ['#eaecf4'] : ['#1cc88a', '#f6c23e', '#858796'];
                let chartLabels = total === 0 ? ['بدون داده'] : ['منتشر شده', 'پیش‌نویس', 'آرشیو'];

                new Chart(updateCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            data: chartData,
                            backgroundColor: chartColors,
                            hoverBackgroundColor: ['#17a673', '#dda20a', '#60616f'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        cutout: '70%',
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } },
                            tooltip: { enabled: total > 0 }
                        }
                    }
                });
            }

            // --- 3. نمودار میله‌ای خرید پکیج‌ها + درآمد (جدید) ---
            const pkgCanvas = document.getElementById('packagePurchaseChart');
            if (pkgCanvas) {
                new Chart(pkgCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json($packagePurchaseLabels),
                        datasets: [
                            {
                                label: 'تعداد خرید',
                                data: @json($packagePurchaseData),
                                backgroundColor: 'rgba(139, 92, 246, 0.7)',
                                borderColor: 'rgba(139, 92, 246, 1)',
                                borderWidth: 1,
                                yAxisID: 'y',
                                order: 2
                            },
                            {
                                label: 'درآمد (تومان)',
                                data: @json($packageRevenueData),
                                type: 'line',
                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                borderColor: 'rgba(245, 158, 11, 1)',
                                pointRadius: 4,
                                pointBackgroundColor: 'rgba(245, 158, 11, 1)',
                                borderWidth: 2,
                                yAxisID: 'y1',
                                order: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: { legend: { position: 'top' } },
                        scales: {
                            x: { grid: { display: false } },
                            y: {
                                type: 'linear',
                                position: 'left',
                                beginAtZero: true,
                                grid: { color: '#eaecf4' },
                                ticks: { stepSize: 1, color: '#8b5cf6' },
                                title: { display: true, text: 'تعداد خرید', color: '#8b5cf6' }
                            },
                            y1: {
                                type: 'linear',
                                position: 'right',
                                beginAtZero: true,
                                grid: { drawOnChartArea: false },
                                ticks: { color: '#f59e0b' },
                                title: { display: true, text: 'درآمد (تومان)', color: '#f59e0b' }
                            }
                        }
                    }
                });
            }

            // --- 4. نمودار دوناتی وضعیت لایسنس‌ها (جدید) ---
            const licCanvas = document.getElementById('licenseStatusChart');
            if (licCanvas) {
                const activeLic = parseInt('{{ $activeLicenses ?? 0 }}') || 0;
                const expiredLic = parseInt('{{ $expiredLicenses ?? 0 }}') || 0;
                const revokedLic = parseInt('{{ $revokedLicenses ?? 0 }}') || 0;
                const total = activeLic + expiredLic + revokedLic;

                let chartData = total === 0 ? [1] : [activeLic, expiredLic, revokedLic];
                let chartColors = total === 0 ? ['#eaecf4'] : ['#10b981', '#f6c23e', '#ef4444'];
                let chartLabels = total === 0 ? ['بدون داده'] : ['فعال', 'منقضی', 'باطل شده'];

                new Chart(licCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            data: chartData,
                            backgroundColor: chartColors,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        cutout: '70%',
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } },
                            tooltip: { enabled: total > 0 }
                        }
                    }
                });
            }
        });
    </script>
@endpush
