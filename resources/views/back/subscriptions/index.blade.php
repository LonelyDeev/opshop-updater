--- resources/views/admin/subscriptions/index.blade.php (原始)


+++ resources/views/admin/subscriptions/index.blade.php (修改后)
@extends('back.layouts.master')

@section('title', 'اشتراک‌ها')

@section('content')
    <div class="page-header">
        <h1>اشتراک‌ها</h1>
        <div class="breadcrumb">
            <a href="{{ route('admin.dashboard') }}">خانه</a>
            <span>/</span>
            <span>اشتراک‌ها</span>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">لیست اشتراک‌ها</h3>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>مشتری</th>
                    <th>نوع اشتراک</th>
                    <th>تاریخ شروع</th>
                    <th>تاریخ پایان</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>1</td>
                    <td>شرکت فناوری نوین</td>
                    <td>طلایی</td>
                    <td>1403/01/01</td>
                    <td>1404/01/01</td>
                    <td><span class="badge badge-success">فعال</span></td>
                    <td>
                        <button class="btn btn-secondary">مشاهده</button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
