@extends('back.layouts.master')

@section('title', 'داشبورد گزارشات')

@section('content')
    <div class="container">
        <h2 class="mb-4">داشبورد گزارشات</h2>

        <!-- کارت‌های آمار -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">کل مشتریان</h5>
                        <h2 class="display-6">{{ $totalCustomers }}</h2>
                        <small>فعال: {{ $activeCustomers }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">آپدیت‌های منتشر شده</h5>
                        <h2 class="display-6">{{ $publishedUpdates }} <span class="fs-4">/ {{ $totalUpdates }}</span></h2>
                        <small>مجموع آپدیت‌ها</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">اشتراک‌های فعال</h5>
                        <h2 class="display-6">{{ $activeSubscriptions }}</h2>
                        <small>منقضی شده: {{ $expiredSubscriptions }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body">
                        <h5 class="card-title">کاربران سیستم</h5>
                        <h2 class="display-6">{{ \App\Models\User::count() }}</h2>
                        <small>مدیران و اپراتورها</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- نمودارها -->
        <div class="row g-3">
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header">
                        روند ثبت‌نام مشتریان (6 ماه اخیر)
                    </div>
                    <div class="card-body">
                        <canvas id="customerChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header">
                        وضعیت اشتراک‌ها
                    </div>
                    <div class="card-body">
                        <canvas id="subscriptionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- کتابخانه Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // نمودار خطی مشتریان
        const ctxCustomer = document.getElementById('customerChart').getContext('2d');
        new Chart(ctxCustomer, {
            type: 'line',
            data: {
                labels: @json($customerLabels),
                datasets: [{
                    label: 'مشتریان جدید',
                    data: @json($customerData),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // نمودار دوناتی اشتراک‌ها
        const ctxSub = document.getElementById('subscriptionChart').getContext('2d');
        new Chart(ctxSub, {
            type: 'doughnut',
            data: {
                labels: ['فعال', 'منقضی شده', 'غیرفعال'],
                datasets: [{
                    data: [
                        {{ $subscriptionStatusData['active'] }},
                        {{ $subscriptionStatusData['expired'] }},
                        {{ $subscriptionStatusData['inactive'] }}
                    ],
                    backgroundColor: ['#198754', '#dc3545', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
@endsection
