@extends('back.layouts.master')

@section('title', 'ایجاد آپدیت جدید')

@section('content')
    <div class="content-wrapper">
        <!-- Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">ایجاد آپدیت جدید</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">داشبورد</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.updates.index') }}">آپدیت‌ها</a></li>
                            <li class="breadcrumb-item active">ایجاد جدید</li>
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
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">اطلاعات آپدیت</h3>
                            </div>

                            <form id="updateForm" enctype="multipart/form-data">
                                @csrf

                                <div class="card-body">
                                    <!-- Progress Bar -->
                                    <div id="progressWrapper" style="display: none;" class="mb-3">
                                        <div class="progress">
                                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                                 role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                                0%
                                            </div>
                                        </div>
                                        <small id="progressText" class="text-muted mt-1">در حال آپلود...</small>
                                    </div>

                                    <!-- Title -->
                                    <div class="form-group">
                                        <label for="title">عنوان آپدیت <span class="text-danger">*</span></label>
                                        <input type="text"
                                               name="title"
                                               id="title"
                                               class="form-control @error('title') is-invalid @enderror"
                                               value="{{ old('title') }}"
                                               placeholder="مثال: نسخه 2.5.0 - اضافه شدن امکانات جدید"
                                               required>
                                        @error('title')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Version -->
                                    <div class="form-group">
                                        <label for="version">نسخه <span class="text-danger">*</span></label>
                                        <input type="text"
                                               name="version"
                                               id="version"
                                               class="form-control @error('version') is-invalid @enderror"
                                               value="{{ old('version') }}"
                                               placeholder="مثال: 2.5.0"
                                               pattern="[0-9]+\.[0-9]+\.[0-9]+"
                                               required>
                                        @error('version')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <small class="form-text text-muted">فرمت: Major.Minor.Patch (مثال: 1.2.3)</small>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="project">پروژه <span class="text-danger">*</span></label>
                                                <select name="project_id" id="project" class="form-control @error('project_id') is-invalid @enderror" required>
                                                    <option value="">انتخاب کنید...</option>
                                                    @foreach($projects as $project)
                                                        <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                                            {{ $project->name}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('project_id')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Type & Status Row -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="type">نوع آپدیت <span class="text-danger">*</span></label>
                                                <select name="type" id="type" class="form-control @error('type') is-invalid @enderror" required>
                                                    <option value="">انتخاب کنید...</option>
                                                    @foreach($types as $key => $label)
                                                        <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('type')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="status">وضعیت <span class="text-danger">*</span></label>
                                                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                                    <option value="">انتخاب کنید...</option>
                                                    @foreach($statuses as $key => $label)
                                                        <option value="{{ $key }}" {{ old('status') == $key ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('status')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <div class="form-group">
                                        <label for="description">توضیحات <span class="text-danger">*</span></label>
                                        <textarea name="description"
                                                  id="description"
                                                  rows="6"
                                                  class="form-control @error('description') is-invalid @enderror"
                                                  placeholder="توضیحات کامل درباره تغییرات، ویژگی‌های جدید و باگ‌های رفع شده..."
                                                  required>{{ old('description') }}</textarea>
                                        @error('description')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Release Date & Mandatory Checkbox Row -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="release_date">تاریخ انتشار</label>
                                                <input type="text"
                                                       name="release_date"
                                                       id="release_date"
                                                       class="form-control @error('release_date') is-invalid @enderror"
                                                       value="{{ old('release_date') }}"
                                                       placeholder="1403/01/15">
                                                @error('release_date')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                                <small class="form-text text-muted">فرمت: سال/ماه/روز</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch mt-4">
                                                    <input type="checkbox"
                                                           name="is_mandatory"
                                                           id="is_mandatory"
                                                           class="custom-control-input"
                                                           value="1"
                                                        {{ old('is_mandatory') ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="is_mandatory">
                                                        اجباری برای کاربران
                                                    </label>
                                                </div>
                                                <small class="form-text text-muted mr-4">اگر فعال باشد، کاربران ملزم به نصب این آپدیت هستند</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- File Upload -->
                                    <div class="form-group">
                                        <label for="file">فایل آپدیت</label>
                                        <div class="custom-file">
                                            <input type="file"
                                                   name="file"
                                                   id="file"
                                                   class="custom-file-input @error('file') is-invalid @enderror"
                                                   accept=".zip,.rar,.tar,.gz">
                                            <label class="custom-file-label" for="file">انتخاب فایل...</label>
                                        </div>
                                        @error('file')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                        <small class="form-text text-muted">فرمت‌های مجاز: ZIP, RAR, TAR, GZ - حداکثر حجم: 100 مگابایت</small>
                                    </div>

                                    <!-- Download Link (Alternative) -->
                                    <div class="form-group">
                                        <label for="download_link">لینک دانلود (اختیاری)</label>
                                        <input type="url"
                                               name="download_link"
                                               id="download_link"
                                               class="form-control @error('download_link') is-invalid @enderror"
                                               value="{{ old('download_link') }}"
                                               placeholder="https://example.com/downloads/update.zip">
                                        @error('download_link')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <small class="form-text text-muted">در صورت آپلود فایل، این فیلد نادیده گرفته می‌شود</small>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <button type="submit" id="submitBtn" class="btn btn-primary">
                                        <i class="fas fa-save ml-1"></i>
                                        ذخیره آپدیت
                                    </button>
                                    <a href="{{ route('admin.updates.index') }}" class="btn btn-default">
                                        <i class="fas fa-times"></i>
                                        انصراف
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            // File Input Label Update
            document.getElementById('file').addEventListener('change', function(e) {
                var fileName = e.target.files[0] ? e.target.files[0].name : 'انتخاب فایل...';
                var label = document.querySelector('.custom-file-label');
                label.textContent = fileName;
            });

            // Ajax Form Submit with Progress
            document.getElementById('updateForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = this;
                const formData = new FormData(form);
                const submitBtn = document.getElementById('submitBtn');
                const progressWrapper = document.getElementById('progressWrapper');
                const progressBar = document.getElementById('progressBar');
                const progressText = document.getElementById('progressText');

                // Disable submit button
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-1"></i> در حال ارسال...';

                // Show progress bar
                progressWrapper.style.display = 'block';
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                progressText.textContent = 'در حال آماده‌سازی...';

                // Create XMLHttpRequest
                const xhr = new XMLHttpRequest();

                // Progress event
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = percentComplete + '%';
                        progressBar.textContent = percentComplete + '%';
                        progressText.textContent = 'در حال آپلود فایل... ' + percentComplete + '%';

                        // Change progress bar color based on progress
                        if (percentComplete < 30) {
                            progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-primary';
                        } else if (percentComplete < 70) {
                            progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-info';
                        } else if (percentComplete < 100) {
                            progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-success';
                        }
                    }
                });

                // Load complete
                xhr.addEventListener('load', function() {
                    progressText.textContent = 'در حال پردازش اطلاعات...';

                    try {
                        const response = JSON.parse(xhr.responseText);

                        if (xhr.status === 200 && response.success) {
                            progressBar.style.width = '100%';
                            progressBar.textContent = '100%';
                            progressBar.className = 'progress-bar bg-success';
                            progressText.textContent = '✓ آپدیت با موفقیت ایجاد شد!';

                            // Redirect after success
                            setTimeout(() => {
                                window.location.href = response.redirect || '{{ route("admin.updates.index") }}';
                            }, 1500);
                        } else {
                            progressBar.className = 'progress-bar bg-danger';
                            progressText.textContent = '❌ ' + (response.message || 'خطا در ذخیره آپدیت');

                            // Show error messages
                            if (response.errors) {
                                let errorHtml = '<div class="alert alert-danger mt-3"><ul class="mb-0">';
                                for (const [field, messages] of Object.entries(response.errors)) {
                                    messages.forEach(msg => {
                                        errorHtml += `<li>${msg}</li>`;
                                    });
                                }
                                errorHtml += '</ul></div>';
                                document.querySelector('.card-body').insertAdjacentHTML('afterbegin', errorHtml);
                            }

                            // Re-enable submit button
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-save ml-1"></i> ذخیره آپدیت';
                        }
                    } catch (error) {
                        progressBar.className = 'progress-bar bg-danger';
                        progressText.textContent = '❌ خطا در پردازش پاسخ سرور';

                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-save ml-1"></i> ذخیره آپدیت';
                    }
                });

                // Error event
                xhr.addEventListener('error', function() {
                    progressBar.className = 'progress-bar bg-danger';
                    progressBar.textContent = 'خطا!';
                    progressText.textContent = '❌ خطا در ارتباط با سرور';

                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save ml-1"></i> ذخیره آپدیت';
                });

                // Abort event
                xhr.addEventListener('abort', function() {
                    progressText.textContent = '❌ آپلود لغو شد';

                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save ml-1"></i> ذخیره آپدیت';
                });

                // Open and send request
                xhr.open('POST', '{{ route("admin.updates.store") }}');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(formData);
            });

            // Optional: Add cancel upload functionality
            // You can add a cancel button that calls xhr.abort()
        </script>
    @endpush
@endsection
