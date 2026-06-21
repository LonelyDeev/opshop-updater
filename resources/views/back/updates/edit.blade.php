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

                            <form action="{{ route('admin.updates.update', $update->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <div class="card-body">
                                    <!-- Title -->
                                    <div class="form-group">
                                        <label for="title">عنوان آپدیت <span class="text-danger">*</span></label>
                                        <input type="text"
                                               name="title"
                                               id="title"
                                               class="form-control @error('title') is-invalid @enderror"
                                               value="{{ old('title', $update->title) }}"
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
                                               value="{{ old('version', $update->version) }}"
                                               placeholder="مثال: 2.5.0"
                                               pattern="[0-9]+\.[0-9]+\.[0-9]+"
                                               required>
                                        @error('version')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <small class="form-text text-muted">فرمت: Major.Minor.Patch (مثال: 1.2.3)</small>
                                    </div>

                                    <!-- Type & Status Row -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="type">نوع آپدیت <span class="text-danger">*</span></label>
                                                <select name="type" id="type" class="form-control @error('type') is-invalid @enderror" required>
                                                    <option value="">انتخاب کنید...</option>
                                                    @foreach($types as $key => $label)
                                                        <option value="{{ $key }}" {{ old('type', $update->type) == $key ? 'selected' : '' }}>
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
                                                        <option value="{{ $key }}" {{ old('status', $update->status) == $key ? 'selected' : '' }}>
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
                                                  required>{{ old('description', $update->description) }}</textarea>
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
                                                       value="{{ old('release_date', $update->release_date?->format('Y/m/d')) }}"
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
                                                        {{ old('is_mandatory', $update->is_mandatory) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="is_mandatory">
                                                        اجباری برای کاربران
                                                    </label>
                                                </div>
                                                <small class="form-text text-muted mr-4">اگر فعال باشد، کاربران ملزم به نصب این آپدیت هستند</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Current File Info -->
                                    @if($update->download_link)
                                        <div class="form-group">
                                            <label>فایل فعلی:</label>
                                            <div class="alert alert-info d-flex align-items-center">
                                                <i class="fas fa-file-archive ml-2"></i>
                                                <div>
                                                    <strong>فایل موجود است</strong>
                                                    <br>
                                                    <small>
                                                        <a href="{{ $update->download_link }}" target="_blank" class="text-info">
                                                            <i class="fas fa-download"></i> دانلود فایل فعلی
                                                        </a>
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
                                               value="{{ old('download_link', $update->download_link) }}"
                                               placeholder="https://example.com/downloads/update.zip">
                                        @error('download_link')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <small class="form-text text-muted">در صورت آپلود فایل، این فیلد نادیده گرفته می‌شود</small>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-edit ml-1"></i>
                                        به‌روزرسانی آپدیت
                                    </button>
                                    <a href="{{ route('admin.updates.index') }}" class="btn btn-default">
                                        <i class="fas fa-times"></i>
                                        انصراف
                                    </a>
                                    <div class="float-left">
                                        <form action="{{ route('admin.updates.destroy', $update->id) }}" method="POST" onsubmit="return confirm('آیا از حذف این آپدیت مطمئن هستید؟');">
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

    @push('scripts')
        <script>
            // File Input Label Update
            document.getElementById('file').addEventListener('change', function(e) {
                var fileName = e.target.files[0] ? e.target.files[0].name : 'انتخاب فایل...';
                var label = document.querySelector('.custom-file-label');
                label.textContent = fileName;
            });
        </script>
    @endpush
@endsection
