@extends('back.layouts.master')

@section('title', 'آپلود نسخه جدید')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="feather icon-upload-cloud"></i> آپلود نسخه جدید برای {{ $package->name }}
                    </h5>
                    <a href="{{ route('admin.packages.versions.index', $package) }}" class="btn btn-sm btn-secondary">
                        <i class="feather icon-arrow-right"></i> بازگشت
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.packages.versions.store', $package) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        @if ($errors->any())
                            <div class="mt-3">
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">شماره نسخه <span class="text-danger">*</span></label>
                                <input type="text" name="version" value="{{ old('version') }}"
                                       class="form-control @error('version') is-invalid @enderror"
                                       placeholder="1.0.0" required pattern="\d+\.\d+\.\d+">
                                @error('version') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">فرمت: major.minor.patch (مثلاً 2.1.0)</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">نوع نسخه <span class="text-danger">*</span></label>
                                <select name="type" class="form-select">
                                    @foreach ($types as $key => $label)
                                    <option value="{{ $key }}" @if(old('type', 'patch')==$key) selected @endif>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">تاریخ انتشار</label>
                                <input type="date" name="release_date" value="{{ old('release_date', date('Y-m-d')) }}"
                                       class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">فایل ZIP پکیج <span class="text-danger">*</span></label>
                            <input type="file" name="file" accept=".zip"
                                   class="form-control @error('file') is-invalid @enderror" required>
                            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">حداکثر 300 مگابایت. فایل ZIP باید شامل پوشه ماژول با module.json باشد.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">حداقل نسخه پروژه </label>
                                <input type="text" name="min_project_version" value="{{ old('min_project_version', '1.0.0') }}"
                                       class="form-control" placeholder="1.0.0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">حداقل نسخه PHP</label>
                                <input type="text" name="min_php_version" value="{{ old('min_php_version', '8.2') }}"
                                       class="form-control" placeholder="8.2">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">حداقل نسخه Laravel</label>
                                <input type="text" name="min_laravel_version" value="{{ old('min_laravel_version', '11.0') }}"
                                       class="form-control" placeholder="11.0">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">وضعیت <span class="text-danger">*</span></label>
                                <select name="status" class="form-select">
                                    @foreach ($statuses as $key => $label)
                                    <option value="{{ $key }}" @if(old('status', 'draft')==$key) selected @endif>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" name="is_mandatory" value="1" class="form-check-input"
                                           id="is_mandatory" @if(old('is_mandatory')) checked @endif>
                                    <label for="is_mandatory" class="form-check-label">آپدیت اجباری</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">تغییرات (Changelog)</label>
                            <textarea name="changelog" rows="3" class="form-control" placeholder="خلاصه تغییرات این نسخه">{{ old('changelog') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">قابلیت‌های اضافه شده</label>
                                <textarea name="what_added" rows="4" class="form-control" placeholder="ویژگی‌های جدید">{{ old('what_added') }}</textarea>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">تغییرات</label>
                                <textarea name="what_changed" rows="4" class="form-control" placeholder="تغییرات اعمال شده">{{ old('what_changed') }}</textarea>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">رفع باگ‌ها</label>
                                <textarea name="what_fixed" rows="4" class="form-control" placeholder="باگ‌های رفع شده">{{ old('what_fixed') }}</textarea>
                            </div>
                        </div>

                        {{-- وابستگی‌ها (dynamic) --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">وابستگی‌ها (پکیج‌های موردنیاز)</label>
                                <button type="button" class="btn btn-sm btn-outline-success" id="add-dep-btn">
                                    <i class="feather icon-plus"></i> افزودن وابستگی
                                </button>
                            </div>
                            <div id="dependencies-container">
                                <div class="row dep-row mb-2">
                                    <div class="col-md-5">
                                        <input type="text" name="dependencies[0][slug]" class="form-control form-control-sm"
                                               placeholder="slug پکیج موردنیاز" value="{{ old('dependencies.0.slug') }}">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" name="dependencies[0][version]" class="form-control form-control-sm"
                                               placeholder="نسخه (مثلاً >=1.5)" value="{{ old('dependencies.0.version') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-dep-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">مثلاً: blog-slug با نسخه >=1.5.0</small>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.packages.versions.index', $package) }}" class="btn btn-secondary">انصراف</a>
                            <button type="submit" class="btn btn-success">
                                <i class="feather icon-upload"></i> آپلود نسخه
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let depIndex = 1;
    document.getElementById('add-dep-btn').addEventListener('click', function () {
        const container = document.getElementById('dependencies-container');
        const row = document.createElement('div');
        row.className = 'row dep-row mb-2';
        row.innerHTML = `
        <div class="col-md-5">
            <input type="text" name="dependencies[${depIndex}][slug]" class="form-control form-control-sm" placeholder="slug پکیج موردنیاز">
        </div>
        <div class="col-md-5">
            <input type="text" name="dependencies[${depIndex}][version]" class="form-control form-control-sm" placeholder="نسخه (مثلاً >=1.5)">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-dep-btn">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
        container.appendChild(row);
        depIndex++;
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-dep-btn')) {
            const rows = document.querySelectorAll('.dep-row');
            if (rows.length > 1) {
                e.target.closest('.dep-row').remove();
            }
        }
    });
</script>
@endpush
@endsection
