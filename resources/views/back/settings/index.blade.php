@extends('back.layouts.master')

@section('title', 'تنظیمات سیستم')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>تنظیمات سیستم</h2>
            <div>
                <form action="{{ route('admin.settings.clear-cache') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning me-2">پاک کردن کش</button>
                </form>
                <form action="{{ route('admin.settings.optimize') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-info text-white">بهینه‌سازی سیستم</button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="settingsTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab">عمومی</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="email-tab" data-bs-toggle="tab" href="#email" role="tab">تنظیمات ایمیل</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.update') }}" method="POST">
                    @csrf
                    <div class="tab-content" id="settingsTabContent">

                        <!-- تب عمومی -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">نام سایت</label>
                                <input type="text" name="general[site_name]" class="form-control" value="{{ $generalSettings['site_name'] ?? 'پنل مدیریت آپدیت' }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">توضیحات کوتاه سایت</label>
                                <textarea name="general[site_description]" class="form-control" rows="3">{{ $generalSettings['site_description'] ?? '' }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">لوگو (URL)</label>
                                <input type="text" name="general[site_logo]" class="form-control" value="{{ $generalSettings['site_logo'] ?? '' }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">رنگ اصلی تم</label>
                                <input type="color" name="general[theme_color]" class="form-control form-control-color" value="{{ $generalSettings['theme_color'] ?? '#0d6efd' }}">
                            </div>
                        </div>

                        <!-- تب ایمیل -->
                        <div class="tab-pane fade" id="email" role="tabpanel">
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="email[mail_enabled]" id="mail_enabled" value="1" {{ ($emailSettings['mail_enabled'] ?? 0) == 1 ? 'checked' : '' }}>
                                <label class="form-check-label" for="mail_enabled">فعال‌سازی ارسال ایمیل</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">آدرس ایمیل فرستنده</label>
                                <input type="email" name="email[mail_from_address]" class="form-control" value="{{ $emailSettings['mail_from_address'] ?? '' }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">نام فرستنده</label>
                                <input type="text" name="email[mail_from_name]" class="form-control" value="{{ $emailSettings['mail_from_name'] ?? '' }}">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" name="email[mail_host]" class="form-control" value="{{ $emailSettings['mail_host'] ?? '' }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">SMTP Port</label>
                                    <input type="number" name="email[mail_port]" class="form-control" value="{{ $emailSettings['mail_port'] ?? 587 }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">SMTP Username</label>
                                    <input type="text" name="email[mail_username]" class="form-control" value="{{ $emailSettings['mail_username'] ?? '' }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">SMTP Password</label>
                                    <input type="password" name="email[mail_password]" class="form-control" value="{{ $emailSettings['mail_password'] ?? '' }}">
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">ذخیره تنظیمات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
