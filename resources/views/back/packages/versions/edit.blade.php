@extends('back.layouts.master')

@section('title', 'ویرایش نسخه')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="feather icon-edit-2"></i> ویرایش نسخه {{ $version->version }}
                        </h5>
                        <a href="{{ route('admin.packages.versions.index', $package) }}" class="btn btn-sm btn-secondary">
                            <i class="feather icon-arrow-right"></i> بازگشت
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.packages.versions.update', [$package, $version]) }}"
                              method="POST" enctype="multipart/form-data">
                            @csrf @method('PUT')

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">شماره نسخه <span class="text-danger">*</span></label>
                                    <input type="text" name="version" value="{{ old('version', $version->version) }}"
                                           class="form-control" required pattern="\d+\.\d+\.\d+">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">نوع</label>
                                    <select name="type" class="form-select">
                                        @foreach ($types as $key => $label)
                                            <option value="{{ $key }}" @if(old('type', $version->type)==$key) selected @endif>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">تاریخ انتشار</label>
                                    <input type="date" name="release_date"
                                           value="{{ old('release_date', $version->release_date?->format('Y-m-d')) }}"
                                           class="form-control">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">فایل ZIP جدید (اختیاری)</label>
                                <input type="file" name="file" accept=".zip" class="form-control">
                                <small class="text-muted">فایل فعلی: {{ $version->file_path }} ({{ $version->file_size_human }})</small>
                            </div>

                            <div class="row">

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">حداقل نسخه پروژه</label>
                                    <input type="text" name="min_project_version" value="{{ old('min_project_version', $version->min_project_version) }}"
                                           class="form-control">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">حداقل نسخه PHP</label>
                                    <input type="text" name="min_php_version" value="{{ old('min_php_version', $version->min_php_version) }}"
                                           class="form-control">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">حداقل نسخه Laravel</label>
                                    <input type="text" name="min_laravel_version" value="{{ old('min_laravel_version', $version->min_laravel_version) }}"
                                           class="form-control">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">وضعیت</label>
                                    <select name="status" class="form-select">
                                        @foreach ($statuses as $key => $label)
                                            <option value="{{ $key }}" @if(old('status', $version->status)==$key) selected @endif>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3 d-flex align-items-end">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_mandatory" value="1" class="form-check-input"
                                               id="is_mandatory" @if(old('is_mandatory', $version->is_mandatory)) checked @endif>
                                        <label for="is_mandatory" class="form-check-label">آپدیت اجباری</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Changelog</label>
                                <textarea name="changelog" rows="3" class="form-control">{{ old('changelog', $version->changelog) }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">قابلیت‌های اضافه شده</label>
                                    <textarea name="what_added" rows="4" class="form-control">{{ old('what_added', $version->what_added) }}</textarea>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">تغییرات</label>
                                    <textarea name="what_changed" rows="4" class="form-control">{{ old('what_changed', $version->what_changed) }}</textarea>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">رفع باگ‌ها</label>
                                    <textarea name="what_fixed" rows="4" class="form-control">{{ old('what_fixed', $version->what_fixed) }}</textarea>
                                </div>
                            </div>

                            {{-- وابستگی‌ها --}}
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">وابستگی‌ها</label>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="add-dep-btn">
                                        <i class="feather icon-plus"></i> افزودن
                                    </button>
                                </div>
                                <div id="dependencies-container">
                                    @php
                                        $existingDeps = old('dependencies', $version->dependencies ?? []);
                                        $depIndex = 0;
                                    @endphp
                                    @if (!empty($existingDeps))
                                        @foreach ($existingDeps as $depSlug => $depVer)
                                            @php
                                                // برای حالت old (آرایه عددی از [slug, version])
                                                if (is_array($depVer) && isset($depVer['slug'])) {
                                                    $slug = $depVer['slug'];
                                                    $ver = $depVer['version'];
                                                } else {
                                                    $slug = $depSlug;
                                                    $ver = $depVer;
                                                }
                                            @endphp
                                            <div class="row dep-row mb-2">
                                                <div class="col-md-5">
                                                    <input type="text" name="dependencies[{{ $depIndex }}][slug]"
                                                           class="form-control form-control-sm" value="{{ $slug }}">
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="text" name="dependencies[{{ $depIndex }}][version]"
                                                           class="form-control form-control-sm" value="{{ $ver }}">
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-dep-btn">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            @php $depIndex++; @endphp
                                        @endforeach
                                    @else
                                        <div class="row dep-row mb-2">
                                            <div class="col-md-5">
                                                <input type="text" name="dependencies[0][slug]" class="form-control form-control-sm" placeholder="slug">
                                            </div>
                                            <div class="col-md-5">
                                                <input type="text" name="dependencies[0][version]" class="form-control form-control-sm" placeholder=">=1.5">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-dep-btn">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.packages.versions.index', $package) }}" class="btn btn-secondary">انصراف</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="feather icon-save"></i> ذخیره تغییرات
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
            let depIndex = {{ $depIndex ?? 1 }};
            document.getElementById('add-dep-btn').addEventListener('click', function () {
                const container = document.getElementById('dependencies-container');
                const row = document.createElement('div');
                row.className = 'row dep-row mb-2';
                row.innerHTML = `
        <div class="col-md-5">
            <input type="text" name="dependencies[${depIndex}][slug]" class="form-control form-control-sm" placeholder="slug">
        </div>
        <div class="col-md-5">
            <input type="text" name="dependencies[${depIndex}][version]" class="form-control form-control-sm" placeholder=">=1.5">
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
