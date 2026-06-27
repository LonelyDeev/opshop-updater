@extends('back.layouts.master')

@section('title', 'ویرایش مشتری')

@section('content')
    <div class="content-wrapper">
        <!-- Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">ویرایش مشتری</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">داشبورد</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">مشتری‌ها</a></li>
                            <li class="breadcrumb-item active">ویرایش {{ $customer->name }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-10 offset-lg-1">
                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">ویرایش اطلاعات مشتری - {{ $customer->name }}</h3>
                            </div>

                            <form id="customerForm" action="{{ route('admin.customers.update', $customer->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="card-body">
                                    {{-- محل نمایش پیام موفقیت یا خطا --}}
                                    <div id="alertBox"></div>

                                    <div class="row">
                                        <!-- Name -->
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">نام <span class="text-danger">*</span></label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="name"
                                                   name="name"
                                                   value="{{ old('name', $customer->name) }}"
                                                   required>
                                            <span class="invalid-feedback d-block error-name"></span>
                                        </div>

                                        <!-- Email -->
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">ایمیل <span class="text-danger">*</span></label>
                                            <input type="email"
                                                   class="form-control"
                                                   id="email"
                                                   name="email"
                                                   value="{{ old('email', $customer->email) }}"
                                                   required>
                                            <span class="invalid-feedback d-block error-email"></span>
                                        </div>

                                        <!-- Phone -->
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">تلفن</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="phone"
                                                   name="phone"
                                                   value="{{ old('phone', $customer->phone) }}">
                                            <span class="invalid-feedback d-block error-phone"></span>
                                        </div>

                                        <!-- Website URL -->
                                        <div class="col-md-6 mb-3">
                                            <label for="website_url" class="form-label">آدرس سایت <span class="text-danger">*</span></label>
                                            <input type="url"
                                                   class="form-control"
                                                   id="website_url"
                                                   name="website_url"
                                                   value="{{ old('website_url', $customer->website_url) }}"
                                                   placeholder="https://example.com"
                                                   required>
                                            <span class="invalid-feedback d-block error-website_url"></span>
                                        </div>

                                        <!-- Project -->
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label for="project">پروژه <span class="text-danger">*</span></label>
                                                <select name="project_id" id="project" class="form-control" required>
                                                    <option value="">انتخاب کنید...</option>
                                                    @foreach($projects as $project)
                                                        <option value="{{ $project->id }}" {{ old('project_id', $customer->project_id) == $project->id ? 'selected' : '' }}>
                                                            {{ $project->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="invalid-feedback d-block error-project_id"></span>
                                            </div>
                                        </div>

                                        <!-- Update Code -->
                                        <div class="col-md-6 mb-3">
                                            <label for="update_code" class="form-label">کد آپدیت</label>
                                            <div class="input-group">
                                                <input type="text"
                                                       class="form-control"
                                                       id="update_code"
                                                       name="update_code"
                                                       value="{{ old('update_code', $customer->update_code) }}"
                                                       placeholder="کد آپدیت">
                                                <button class="btn btn-outline-secondary" type="button" id="generateCodeBtn">
                                                    <i class="fas fa-sync-alt"></i> تولید خودکار
                                                </button>
                                            </div>
                                            <div class="form-text text-muted">اگر خالی بگذارید، کد به صورت خودکار تولید می‌شود.</div>
                                            <span class="invalid-feedback d-block error-update_code"></span>
                                        </div>

                                        <!-- Status -->
                                        <div class="col-md-12 mb-3">
                                            <label for="status" class="form-label">وضعیت <span class="text-danger">*</span></label>
                                            <select class="form-control" id="status" name="status" required>
                                                <option value="active" {{ old('status', $customer->status) == 'active' ? 'selected' : '' }}>
                                                    فعال
                                                </option>
                                                <option value="inactive" {{ old('status', $customer->status) == 'inactive' ? 'selected' : '' }}>
                                                    غیرفعال
                                                </option>
                                            </select>
                                            <span class="invalid-feedback d-block error-status"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <button type="submit" id="submitBtn" class="btn btn-warning">
                                        <i class="fas fa-edit ml-1"></i>
                                        <span class="btn-text">به‌روزرسانی مشتری</span>
                                        <span class="spinner-border spinner-border-sm d-none" id="btnSpinner"></span>
                                    </button>
                                    <a href="{{ route('admin.customers.index') }}" class="btn btn-default">
                                        <i class="fas fa-times"></i> انصراف
                                    </a>
                                    <div class="float-left">
                                        <form action="{{ route('admin.customers.destroy', $customer->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('آیا از حذف این مشتری مطمئن هستید؟');"
                                              style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-trash"></i>
                                                حذف مشتری
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        // تولید کد آپدیت
        document.getElementById('generateCodeBtn').addEventListener('click', function() {
            const code = generateRandomCode(12);
            document.getElementById('update_code').value = code;

            // نمایش پیام کوتاه
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> تولید شد';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-outline-secondary');

            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
            }, 2000);
        });

        // تولید کد تصادفی
        function generateRandomCode(length = 12) {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = '';
            for (let i = 0; i < length; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
        }

        // ارسال فرم با Ajax
        document.getElementById('customerForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            const formData = new FormData(form);
            formData.append('_method', 'PUT');

            const submitBtn = document.getElementById('submitBtn');
            const btnSpinner = document.getElementById('btnSpinner');
            const alertBox = document.getElementById('alertBox');

            // پاک کردن خطاهای قبلی
            document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            alertBox.innerHTML = '';

            // فعال کردن حالت لودینگ
            submitBtn.setAttribute('disabled', 'disabled');
            btnSpinner.classList.remove('d-none');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');

            xhr.onload = function() {
                submitBtn.removeAttribute('disabled');
                btnSpinner.classList.add('d-none');

                if (xhr.status === 200 || xhr.status === 201) {
                    const response = JSON.parse(xhr.responseText);
                    alertBox.innerHTML =
                        '<div class="alert alert-success">' + (response.message || 'با موفقیت به‌روزرسانی شد.') + '</div>';

                    setTimeout(() => {
                        window.location.href = response.redirect || '{{ route("admin.customers.index") }}';
                    }, 1200);

                } else if (xhr.status === 422) {
                    const response = JSON.parse(xhr.responseText);
                    const errors = response.errors;

                    for (const field in errors) {
                        const errorEl = document.querySelector('.error-' + field);
                        const inputEl = document.querySelector('[name="' + field + '"]');
                        if (errorEl) errorEl.textContent = errors[field][0];
                        if (inputEl) inputEl.classList.add('is-invalid');
                    }

                    alertBox.innerHTML =
                        '<div class="alert alert-warning">لطفاً خطاهای فرم را برطرف کنید.</div>';

                } else {
                    let msg = 'خطایی رخ داد.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        msg = response.message || msg;
                    } catch (err) {}
                    alertBox.innerHTML = '<div class="alert alert-danger">' + msg + '</div>';
                }
            };

            xhr.onerror = function() {
                submitBtn.removeAttribute('disabled');
                btnSpinner.classList.add('d-none');
                alertBox.innerHTML = '<div class="alert alert-danger">خطا در ارتباط با سرور.</div>';
            };

            xhr.send(formData);
        });
    </script>
@endpush
