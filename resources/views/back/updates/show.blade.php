--- resources/views/admin/updates/show.blade.php (原始)


+++ resources/views/admin/updates/show.blade.php (修改后)
@extends('layouts.admin')

@section('title', 'جزئیات آپدیت')

@section('content')
    <div class="content-wrapper">
        <!-- Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">جزئیات آپدیت</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">داشبورد</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.updates.index') }}">آپدیت‌ها</a></li>
                            <li class="breadcrumb-item active">نسخه {{ $update->version }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-8 offset-lg-2">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-box ml-2"></i>
                                    {{ $update->title }}
                                </h3>
                                <div class="card-tools">
                                    <a href="{{ route('admin.updates.edit', $update->id) }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit ml-1"></i>
                                        ویرایش
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box bg-info">
                                            <span class="info-box-icon"><i class="fas fa-code-branch"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">نسخه</span>
                                                <span class="info-box-number">{{ $update->version }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box bg-success">
                                            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">وضعیت</span>
                                                <span class="info-box-number">
                                                @if($update->status == 'active')
                                                        فعال
                                                    @elseif($update->status == 'draft')
                                                        پیش‌نویس
                                                    @else
                                                        بایگانی
                                                    @endif
                                            </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="card card-secondary">
                                            <div class="card-header">
                                                <h3 class="card-title">نوع آپدیت</h3>
                                            </div>
                                            <div class="card-body text-center">
                                                @if($update->type == 'major')
                                                    <span class="badge badge-danger p-2">اصلی (Major)</span>
                                                @elseif($update->type == 'minor')
                                                    <span class="badge badge-warning p-2">فرعی (Minor)</span>
                                                @else
                                                    <span class="badge badge-secondary p-2">اصلاحی (Patch)</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card card-secondary">
                                            <div class="card-header">
                                                <h3 class="card-title">تاریخ انتشار</h3>
                                            </div>
                                            <div class="card-body text-center">
                                                <i class="far fa-calendar-alt fa-2x text-muted mb-2"></i>
                                                <p class="mb-0">
                                                    {{ $update->release_date ? \Morilog\Jalali\Jalalian::forge($update->release_date)->format('%Y/%m/%d') : 'تعریف نشده' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card card-secondary">
                                            <div class="card-header">
                                                <h3 class="card-title">اجباری</h3>
                                            </div>
                                            <div class="card-body text-center">
                                                @if($update->is_mandatory)
                                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                                    <p class="mt-2 mb-0">بله، اجباری است</p>
                                                @else
                                                    <i class="fas fa-times-circle fa-2x text-muted"></i>
                                                    <p class="mt-2 mb-0">خیر، اختیاری است</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <h5><i class="fas fa-align-right ml-2"></i>توضیحات:</h5>
                                    <div class="alert alert-light border">
                                        <p class="mb-0" style="white-space: pre-line;">{{ $update->description }}</p>
                                    </div>
                                </div>

                                @if($update->download_link)
                                    <div class="mt-4">
                                        <h5><i class="fas fa-download ml-2"></i>دانلود فایل:</h5>
                                        <div class="alert alert-info d-flex align-items-center justify-content-between">
                                            <div>
                                                <i class="fas fa-file-archive fa-2x ml-3"></i>
                                                <strong>فایل آپدیت آماده دانلود است</strong>
                                            </div>
                                            <a href="{{ $update->download_link }}" target="_blank" class="btn btn-primary">
                                                <i class="fas fa-download ml-1"></i>
                                                دانلود فایل
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-4">
                                    <h5><i class="fas fa-clock ml-2"></i>اطلاعات سیستم:</h5>
                                    <table class="table table-bordered table-sm">
                                        <tbody>
                                        <tr>
                                            <th style="width: 200px;">شناسه</th>
                                            <td>{{ $update->id }}</td>
                                        </tr>
                                        <tr>
                                            <th>تاریخ ایجاد</th>
                                            <td>{{ \Morilog\Jalali\Jalalian::forge($update->created_at)->format('%Y/%m/%d %H:%i') }}</td>
                                        </tr>
                                        <tr>
                                            <th>آخرین به‌روزرسانی</th>
                                            <td>{{ \Morilog\Jalali\Jalalian::forge($update->updated_at)->format('%Y/%m/%d %H:%i') }}</td>
                                        </tr>
                                        @if($update->deleted_at)
                                            <tr>
                                                <th>تاریخ حذف</th>
                                                <td>{{ \Morilog\Jalali\Jalalian::forge($update->deleted_at)->format('%Y/%m/%d %H:%i') }}</td>
                                            </tr>
                                        @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="{{ route('admin.updates.index') }}" class="btn btn-default">
                                    <i class="fas fa-arrow-right ml-1"></i>
                                    بازگشت به لیست
                                </a>
                                <div class="float-left">
                                    <form action="{{ route('admin.updates.destroy', $update->id) }}" method="POST" onsubmit="return confirm('آیا از حذف این آپدیت مطمئن هستید؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash ml-1"></i>
                                            حذف آپدیت
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
