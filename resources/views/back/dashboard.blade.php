@extends('back.layouts.master')


@section('title', 'داشبورد مدیریت')

@section('content')
    <div class="container-fluid">
        <h2 class="mb-4 text-gray-800">داشبورد مدیریت</h2>

        <!-- ردیف کارت‌های آمار -->
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
                                <i class="fas fa-users fa-2x text-gray-300" style="color: #dddfeb;"></i>
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

            <!-- کارت درآمد (یا کاربران) -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-warning shadow h-100 py-2" style="border-right: 4px solid #f6c23e; border-left: none;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">درآمد کل (تومان)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalRevenue ?? 0) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ردیف نمودارها -->
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
                        <!-- کانتینر با ارتفاع مشخص برای جلوگیری از بزرگ شدن بی‌رویه -->
                        <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                            <canvas id="updateStatusChart"></canvas>
                        </div>

                        <div class="mt-4 text-center small">
                <span class="mr-2">
                    <i class="fas fa-circle text-success"></i> منتشر شده
                </span>
                            <span class="mr-2">
                    <i class="fas fa-circle text-warning"></i> پیش‌نویس
                </span>
                            <span class="mr-2">
                    <i class="fas fa-circle text-secondary"></i> آرشیو
                </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ردیف جداول آخرین فعالیت‌ها -->
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
                                    <tr>
                                        <td colspan="4" class="text-center">موردی یافت نشد.</td>
                                    </tr>
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
                                    <tr>
                                        <td colspan="4" class="text-center">آپدیتی یافت نشد.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <a href="{{ route('admin.updates.index') }}" class="btn btn-sm btn-light w-100 mt-2">مشاهده همه آپدیت‌ها</a>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <style>
        /* استایل‌های تکمیلی برای زیبایی کارت‌ها */
        .card { border: none; border-radius: 0.5rem; }
        .text-xs { font-size: .7rem; }
        .font-weight-bold { font-weight: 700 !important; }
        .shadow { box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15)!important; }
        .py-2 { padding-top: .5rem!important; padding-bottom: .5rem!important; }
        .h-100 { height: 100%!important; }
        .border-start-primary { border-right: 4px solid #4e73df; }
        .border-start-success { border-right: 4px solid #1cc88a; }
        .border-start-info { border-right: 4px solid #36b9cc; }
        .border-start-warning { border-right: 4px solid #f6c23e; }
    </style>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تنظیمات کلی فونت و رنگ
            Chart.defaults.font.family = "'Vazirmatn', 'Tahoma', sans-serif";
            Chart.defaults.color = '#5a5c69';

            // --- 1. نمودار خطی مشتریان ---
            const customerCanvas = document.getElementById('customerChart');
            if (customerCanvas) {
                const ctxCustomer = customerCanvas.getContext('2d');

                // دریافت داده‌ها از بلید
                const cLabels = @json($customerLabels);
                const cData = @json($customerData);

                new Chart(ctxCustomer, {
                    type: 'line',
                    data: { // <--- نکته مهم: اضافه کردن کلید data
                        labels: cLabels,
                        datasets: [{
                            label: 'مشتریان جدید',
                            data: cData,
                            lineTension: 0.3,
                            backgroundColor: 'rgba(78, 115, 223, 0.05)',
                            borderColor: 'rgba(78, 115, 223, 1)',
                            pointRadius: 3,
                            pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                            pointBorderColor: 'rgba(78, 115, 223, 1)',
                            pointHoverRadius: 3,
                            pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                            pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                            pointHitRadius: 10,
                            pointBorderWidth: 2,
                            borderWidth: 2,
                            fill: true
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // اجازه می‌دهد ارتفاع توسط CSS کنترل شود
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: {
                                grid: { display: false, drawBorder: false },
                                ticks: { maxRotation: 0, minRotation: 0 }
                            },
                            y: {
                                grid: { borderDash: [2], drawBorder: false, color: '#eaecf4' },
                                ticks: { stepSize: 1, beginAtZero: true }
                            }
                        }
                    }
                });
            }

            // --- نمودار دوناتی وضعیت آپدیت‌ها ---
            const ctxUpdate = document.getElementById('updateStatusChart');

// فقط اگر المان وجود داشت اجرا شود
            if (ctxUpdate) {
                const context = ctxUpdate.getContext('2d');

                // دریافت مقادیر با اطمینان از عدد بودن و جلوگیری از NaN
                const pStatus = parseInt({{ $publishedStatus ?? 0 }}) || 0;
                const dUpdates = parseInt({{ $draftUpdates ?? 0 }}) || 0;
                const aUpdates = parseInt({{ $archivedUpdates ?? 0 }}) || 0;

                const totalData = pStatus + dUpdates + aUpdates;

                // اگر مجموع داده‌ها صفر است، یک داده ساختگی با رنگ شفاف می‌سازیم تا نمودار کرش نکند
                let chartData = [pStatus, dUpdates, aUpdates];
                let chartColors = ['#1cc88a', '#f6c23e', '#858796'];

                if (totalData === 0) {
                    chartData = [1]; // یک داده واحد
                    chartColors = ['#eaecf4']; // رنگ خاکستری خیلی کمرنگ
                }

                new Chart(context, {
                    type: 'doughnut',
                    data: {
                        labels: totalData === 0 ? ['بدون داده'] : ['منتشر شده', 'پیش‌نویس', 'آرشیو'],
                        datasets: [{
                            data: chartData,
                            backgroundColor: chartColors,
                            hoverBackgroundColor: ['#17a673', '#dda20a', '#60616f'],
                            hoverBorderColor: "rgba(234, 236, 244, 1)",
                            borderWidth: 0
                        }],
                    },
                    options: {
                        maintainAspectRatio: false, // اجازه می‌دهد ارتفاع از CSS والد پیروی کند
                        cutout: '70%',
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20
                                }
                            },
                            tooltip: {
                                enabled: totalData > 0 // اگر داده‌ای نیست تولتیپ نمایش نده
                            }
                        },
                        animation: {
                            animateScale: true,
                            animateRotate: true
                        }
                    }
                });
            }
        });
    </script>

    <style>
        /* اصلاح استایل‌ها برای جلوگیری از اسکرول اضافی */
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15);
            margin-bottom: 1.5rem; /* فاصله استاندارد */
        }

        /* اطمینان از اینکه کانواس‌ها از والد خود بیرون نمی‌زنند */
        .card-body {
            position: relative;
            overflow: hidden; /* جلوگیری از اسکرول داخلی ناخواسته */
        }

        canvas {
            max-width: 100%;
            height: auto !important; /* اولویت با ارتفاع تعریف شده در HTML است اما ریسپانسیو بماند */
        }

        .text-xs { font-size: .7rem; }
        .font-weight-bold { font-weight: 700 !important; }
        .border-start-primary { border-right: 4px solid #4e73df !important; border-left: none !important; }
        .border-start-success { border-right: 4px solid #1cc88a !important; border-left: none !important; }
        .border-start-info { border-right: 4px solid #36b9cc !important; border-left: none !important; }
        .border-start-warning { border-right: 4px solid #f6c23e !important; border-left: none !important; }
    </style>
@endpush
