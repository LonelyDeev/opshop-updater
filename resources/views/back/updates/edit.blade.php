@extends('back.layouts.master')

@section('title', 'ویرایش آپدیت')

@section('content')
    <div class="content-wrapper">
        <!-- Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">ویرایش آپدیت</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">داشبورد</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.updates.index') }}">آپدیت‌ها</a></li>
                            <li class="breadcrumb-item active">ویرایش نسخه {{ $update->version }}</li>
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
                                <h3 class="card-title">ویرایش اطلاعات آپدیت - نسخه {{ $update->version }}</h3>
                            </div>

                            <form id="updateForm" action="{{ route('admin.updates.update', $update->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <div class="card-body">
                                    {{-- محل نمایش پیام موفقیت یا خطا --}}
                                    <div id="alertBox"></div>

                                    <!-- Title -->
                                    <div class="form-group">
                                        <label for="title">عنوان آپدیت <span class="text-danger">*</span></label>
                                        <input type="text"
                                               name="title"
                                               id="title"
                                               class="form-control"
                                               value="{{ old('title', $update->title) }}"
                                               placeholder="مثال: نسخه 2.5.0 - اضافه شدن امکانات جدید"
                                               required>
                                        <span class="invalid-feedback d-block error-title"></span>
                                    </div>

                                    <!-- Version -->
                                    <div class="form-group">
                                        <label for="version">نسخه <span class="text-danger">*</span></label>
                                        <input type="text"
                                               name="version"
                                               id="version"
                                               class="form-control"
                                               value="{{ old('version', $update->version) }}"
                                               placeholder="مثال: 2.5.0"
                                               pattern="[0-9]+\.[0-9]+\.[0-9]+"
                                               required>
                                        <span class="invalid-feedback d-block error-version"></span>
                                        <small class="form-text text-muted">فرمت: Major.Minor.Patch (مثال: 1.2.3)</small>
                                    </div>

                                    <!-- Project -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="project">پروژه <span class="text-danger">*</span></label>
                                                <select name="project_id" id="project" class="form-control" required>
                                                    <option value="">انتخاب کنید...</option>
                                                    @foreach($projects as $project)
                                                        <option value="{{ $project->id }}" {{ old('project_id', $update->project_id) == $project->id ? 'selected' : '' }}>
                                                            {{ $project->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="invalid-feedback d-block error-project_id"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Type & Status Row -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="type">نوع آپدیت <span class="text-danger">*</span></label>
                                                <select name="type" id="type" class="form-control" required>
                                                    <option value="">انتخاب کنید...</option>
                                                    @foreach($types as $key => $label)
                                                        <option value="{{ $key }}" {{ old('type', $update->type) == $key ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="invalid-feedback d-block error-type"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="status">وضعیت <span class="text-danger">*</span></label>
                                                <select name="status" id="status" class="form-control" required>
                                                    <option value="">انتخاب کنید...</option>
                                                    @foreach($statuses as $key => $label)
                                                        <option value="{{ $key }}" {{ old('status', $update->status) == $key ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="invalid-feedback d-block error-status"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <div class="form-group">
                                        <label for="description">توضیحات <span class="text-danger">*</span></label>
                                        <textarea name="description"
                                                  id="description"
                                                  rows="6"
                                                  class="form-control"
                                                  placeholder="توضیحات کامل درباره تغییرات..."
                                                  required>{{ old('description', $update->description) }}</textarea>
                                        <span class="invalid-feedback d-block error-description"></span>
                                    </div>

                                    <!-- Release Date & Mandatory -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="release_date">تاریخ انتشار</label>
                                                <input type="text"
                                                       name="release_date"
                                                       id="release_date"
                                                       class="form-control"
                                                       value="{{ old('release_date', $update->release_date?->format('Y/m/d')) }}"
                                                       placeholder="1403/01/15">
                                                <span class="invalid-feedback d-block error-release_date"></span>
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
                                                        {{ old('is_mandatory', $update->is_mandatory) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="is_mandatory">اجباری برای کاربران</label>
                                                </div>
                                                <small class="form-text text-muted mr-4">اگر فعال باشد، کاربران ملزم به نصب این آپدیت هستند</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Current File Info -->
                                    @if($update->file_path || $update->download_link)
                                        <div class="form-group">
                                            <label>فایل فعلی:</label>
                                            <div class="alert alert-info d-flex align-items-center">
                                                <i class="fas fa-file-archive ml-2"></i>
                                                <div>
                                                    <strong>فایل موجود است</strong>
                                                    <br>
                                                    <small>
                                                        @if($update->file_path)
                                                            <span class="text-muted">نام فایل: {{ basename($update->file_path) }}</span>
                                                        @endif
                                                        @if($update->download_link)
                                                            <a href="{{ $update->download_link }}" target="_blank" class="text-info">
                                                                <i class="fas fa-download"></i> دانلود فایل فعلی
                                                            </a>
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">برای جایگزینی، فایل جدید آپلود کنید</small>
                                        </div>
                                    @endif

                                    <!-- File Upload -->
                                    <div class="form-group">
                                        <label for="file">آپلود فایل جدید (اختیاری)</label>
                                        <div class="custom-file">
                                            <input type="file"
                                                   name="file"
                                                   id="file"
                                                   class="custom-file-input"
                                                   accept=".zip,.rar,.tar,.gz">
                                            <label class="custom-file-label" for="file">انتخاب فایل...</label>
                                        </div>
                                        <span class="invalid-feedback d-block error-file"></span>
                                        <small class="form-text text-muted">فرمت‌های مجاز: ZIP, RAR, TAR, GZ - حداکثر حجم: 100 مگابایت</small>
                                    </div>

                                    {{-- Progress Bar --}}
                                    <div class="form-group" id="progressWrapper" style="display: none;">
                                        <label>درصد آپلود فایل:</label>
                                        <div class="progress" style="height: 25px;">
                                            <div id="uploadProgress"
                                                 class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                                 role="progressbar" style="width: 0%;">0%</div>
                                        </div>
                                        <small id="uploadSpeed" class="form-text text-muted"></small>
                                    </div>

                                    <!-- Download Link -->
                                    <div class="form-group">
                                        <label for="download_link">لینک دانلود (اختیاری)</label>
                                        <input type="url"
                                               name="download_link"
                                               id="download_link"
                                               class="form-control"
                                               value="{{ old('download_link', $update->download_link) }}"
                                               placeholder="https://example.com/downloads/update.zip">
                                        <span class="invalid-feedback d-block error-download_link"></span>
                                        <small class="form-text text-muted">در صورت آپلود فایل، این فیلد نادیده گرفته می‌شود</small>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <button type="submit" id="submitBtn" class="btn btn-warning">
                                        <i class="fas fa-edit ml-1"></i>
                                        <span class="btn-text">به‌روزرسانی آپدیت</span>
                                        <span class="spinner-border spinner-border-sm d-none" id="btnSpinner"></span>
                                    </button>
                                    <a href="{{ route('admin.updates.index') }}" class="btn btn-default">
                                        <i class="fas fa-times"></i> انصراف
                                    </a>
                                    <div class="float-left">
                                        <form action="{{ route('admin.updates.destroy', $update->id) }}" method="POST" onsubmit="return confirm('آیا از حذف این آپدیت مطمئن هستید؟');" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-trash"></i>
                                                حذف آپدیت
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
        // به‌روزرسانی نام فایل انتخاب شده
        document.getElementById('file').addEventListener('change', function (e) {
            var fileName = e.target.files[0] ? e.target.files[0].name : 'انتخاب فایل...';
            document.querySelector('.custom-file-label').textContent = fileName;
        });

        document.getElementById('updateForm').addEventListener('submit', function (e) {
            e.preventDefault();

            var form = this;
            var formData = new FormData(form);

            // اضافه کردن متد PUT برای Ajax
            formData.append('_method', 'PUT');

            var submitBtn = document.getElementById('submitBtn');
            var btnSpinner = document.getElementById('btnSpinner');
            var progressWrapper = document.getElementById('progressWrapper');
            var progressBar = document.getElementById('uploadProgress');
            var uploadSpeed = document.getElementById('uploadSpeed');
            var alertBox = document.getElementById('alertBox');

            // پاک کردن خطاهای قبلی
            document.querySelectorAll('.invalid-feedback').forEach(function (el) {
                el.textContent = '';
            });
            document.querySelectorAll('.is-invalid').forEach(function (el) {
                el.classList.remove('is-invalid');
            });
            alertBox.innerHTML = '';

            // فعال کردن حالت لودینگ
            submitBtn.setAttribute('disabled', 'disabled');
            btnSpinner.classList.remove('d-none');
            progressWrapper.style.display = 'block';
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';

            var xhr = new XMLHttpRequest();
            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');

            var startTime = Date.now();

            // ----- رویداد پیشرفت آپلود -----
            xhr.upload.addEventListener('progress', function (event) {
                if (event.lengthComputable) {
                    var percent = Math.round((event.loaded / event.total) * 100);
                    progressBar.style.width = percent + '%';
                    progressBar.textContent = percent + '%';

                    // محاسبه سرعت آپلود
                    var elapsed = (Date.now() - startTime) / 1000; // ثانیه
                    var speed = event.loaded / elapsed; // بایت بر ثانیه
                    var speedText = formatBytes(speed) + '/s';
                    var loadedText = formatBytes(event.loaded) + ' از ' + formatBytes(event.total);
                    uploadSpeed.textContent = loadedText + ' - سرعت: ' + speedText;
                }
            });

            // ----- پایان درخواست -----
            xhr.onload = function () {
                submitBtn.removeAttribute('disabled');
                btnSpinner.classList.add('d-none');

                if (xhr.status === 200 || xhr.status === 201) {
                    progressBar.classList.remove('bg-success');
                    progressBar.classList.add('bg-success');
                    progressBar.textContent = '100% - تکمیل شد';

                    var response = JSON.parse(xhr.responseText);
                    alertBox.innerHTML =
                        '<div class="alert alert-success">' + (response.message || 'با موفقیت به‌روزرسانی شد.') + '</div>';

                    // ریدایرکت بعد از کمی تأخیر
                    setTimeout(function () {
                        window.location.href = response.redirect || '{{ route("admin.updates.index") }}';
                    }, 1200);

                } else if (xhr.status === 422) {
                    // خطاهای اعتبارسنجی
                    progressWrapper.style.display = 'none';
                    var response = JSON.parse(xhr.responseText);
                    var errors = response.errors;

                    for (var field in errors) {
                        var errorEl = document.querySelector('.error-' + field);
                        var inputEl = document.querySelector('[name="' + field + '"]');
                        if (errorEl) errorEl.textContent = errors[field][0];
                        if (inputEl) inputEl.classList.add('is-invalid');
                    }

                    alertBox.innerHTML =
                        '<div class="alert alert-warning">لطفاً خطاهای فرم را برطرف کنید.</div>';
                } else {
                    progressWrapper.style.display = 'none';
                    var msg = 'خطایی رخ داد.';
                    try {
                        var response = JSON.parse(xhr.responseText);
                        msg = response.message || msg;
                    } catch (err) {}
                    alertBox.innerHTML = '<div class="alert alert-danger">' + msg + '</div>';
                }
            };

            // ----- خطای شبکه -----
            xhr.onerror = function () {
                submitBtn.removeAttribute('disabled');
                btnSpinner.classList.add('d-none');
                progressWrapper.style.display = 'none';
                alertBox.innerHTML =
                    '<div class="alert alert-danger">خطا در ارتباط با سرور.</div>';
            };

            xhr.send(formData);
        });

        // تابع کمکی برای تبدیل بایت به فرمت خوانا
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(decimals)) + ' ' + sizes[i];
        }
    </script>
@endpush
