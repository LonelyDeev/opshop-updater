@extends('back.layouts.master')

@section('title', 'داشبورد')

@section('content')
    <div class="page-header">
        <h1>داشبورد</h1>
        <div class="breadcrumb">
            <a href="#">خانه</a>
            <span>/</span>
            <span>داشبورد</span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">24</div>
                <div class="stat-label">آپدیت‌های جدید</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">156</div>
                <div class="stat-label">مشتریان فعال</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">12</div>
                <div class="stat-label">در انتظار بررسی</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">3</div>
                <div class="stat-label">خطاهای سیستم</div>
            </div>
        </div>
    </div>

    <!-- Recent Updates Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">آخرین آپدیت‌ها</h3>
            <button class="btn btn-primary">
                <i class="fas fa-plus"></i>
                آپدیت جدید
            </button>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>عنوان</th>
                    <th>نسخه</th>
                    <th>مشتری</th>
                    <th>تاریخ</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>آپدیت ماژول پرداخت</td>
                    <td>v2.1.0</td>
                    <td>شرکت فناوری نوین</td>
                    <td>1403/03/15</td>
                    <td><span class="badge badge-success">موفق</span></td>
                    <td>
                        <button class="btn btn-secondary">مشاهده</button>
                    </td>
                </tr>
                <tr>
                    <td>به‌روزرسانی امنیتی</td>
                    <td>v1.8.5</td>
                    <td>استارتاپ برتر</td>
                    <td>1403/03/14</td>
                    <td><span class="badge badge-warning">در حال انجام</span></td>
                    <td>
                        <button class="btn btn-secondary">مشاهده</button>
                    </td>
                </tr>
                <tr>
                    <td>افزودن قابلیت جدید</td>
                    <td>v3.0.0</td>
                    <td>سازمان پیشرو</td>
                    <td>1403/03/13</td>
                    <td><span class="badge badge-primary">جدید</span></td>
                    <td>
                        <button class="btn btn-secondary">مشاهده</button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Activity Log -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">فعالیت‌های اخیر</h3>
        </div>
        <div class="card-body">
            <p>لیست فعالیت‌های اخیر سیستم در این بخش نمایش داده خواهد شد.</p>
        </div>
    </div>
@endsection
