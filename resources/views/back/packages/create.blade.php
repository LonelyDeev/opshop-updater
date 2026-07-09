@extends('back.layouts.master')

@section('title', 'ایجاد پکیج جدید')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle"></i> ایجاد پکیج جدید
                        </h5>
                        <a href="{{ route('admin.packages.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-right"></i> بازگشت
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.packages.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">نام پکیج <span class="text-danger">*</span></label>
                                    <input type="text" name="name" value="{{ old('name') }}"
                                           class="form-control @error('name') is-invalid @enderror" required autofocus>
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <small class="text-muted">مثلاً: ماژول بلاگ پیشرفته</small>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="slug" value="{{ old('slug') }}"
                                           class="form-control @error('slug') is-invalid @enderror" placeholder="auto">
                                    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <small class="text-muted">اگر خالی بگذارید، خودکار ساخته می‌شود.</small>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">نام ماژول (پوشه)</label>
                                    <input type="text" name="module_name" value="{{ old('module_name') }}"
                                           class="form-control" placeholder="auto">
                                    <small class="text-muted">نام پوشه در Modules/ - مثلاً Blog</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">پروژه <span class="text-danger">*</span></label>
                                    <select name="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                                        <option value="">انتخاب کنید...</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->id }}" @if(old('project_id')==$project->id) selected @endif>
                                                {{ $project->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">دسته‌بندی</label>
                                    <select name="category" class="form-select">
                                        <option value="">—</option>
                                        @foreach ($categories as $key => $label)
                                            <option value="{{ $key }}" @if(old('category')==$key) selected @endif>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">نویسنده</label>
                                    <input type="text" name="author" value="{{ old('author') }}" class="form-control">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">توضیح کوتاه</label>
                                <input type="text" name="short_description" value="{{ old('short_description') }}"
                                       class="form-control" maxlength="255">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">توضیحات کامل</label>
                                <textarea name="description" rows="5" class="form-control">{{ old('description') }}</textarea>
                            </div>

                            {{-- تصویر شاخص --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <strong><i class="fas fa-image"></i> تصویر شاخص</strong>
                                    <small class="text-muted">— می‌توانید فایل آپلود کنید یا URL وارد کنید</small>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label small">آپلود فایل</label>
                                            <input type="file" name="thumbnail_file" accept="image/jpeg,image/png,image/webp,image/gif"
                                                   class="form-control @error('thumbnail_file') is-invalid @enderror">
                                            @error('thumbnail_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            <small class="text-muted">JPG, PNG, WEBP, GIF - حداکثر 3MB</small>

                                            <div id="thumbnail-preview-container" class="mt-2"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">یا URL تصویر</label>
                                            <input type="url" name="thumbnail_url" value="{{ old('thumbnail_url') }}"
                                                   class="form-control @error('thumbnail_url') is-invalid @enderror"
                                                   placeholder="https://example.com/image.jpg">
                                            @error('thumbnail_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            <small class="text-muted">اگر فایل آپلود می‌کنید، این فیلد را خالی بگذارید.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- گالری تصاویر --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><i class="fas fa-grid"></i> گالری تصاویر</strong>
                                        <small class="text-muted">— اسکرین‌شات‌ها و تصاویر نمونه</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="add-gallery-input">
                                        <i class="fas fa-plus"></i> افزودن تصویر
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div id="gallery-inputs">
                                        <div class="gallery-input-row mb-2">
                                            <div class="input-group">
                                                <input type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp,image/gif"
                                                       class="form-control" multiple>
                                                <button type="button" class="btn btn-outline-danger remove-gallery-input" disabled>
                                                    <i class="fas fa-x"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted">می‌توانید چند فایل را همزمان انتخاب کنید. حداکثر 10 تصویر.</small>
                                    <div id="gallery-preview" class="row mt-2 g-2"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">وضعیت <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select" required>
                                        @foreach ($statuses as $key => $label)
                                            <option value="{{ $key }}" @if(old('status', 'draft')==$key) selected @endif>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">قیمت پیش‌فرض (تومان)</label>
                                    <input type="number" name="default_price" value="{{ old('default_price', 0) }}"
                                           class="form-control" min="0">
                                    <small class="text-muted">قیمت اصلی در طرح‌های قیمت‌گذاری تعیین می‌شود.</small>
                                </div>

                                <div class="col-md-2 mb-3">
                                    <label class="form-label">ترتیب</label>
                                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                                           class="form-control" min="0">
                                </div>

                                <div class="col-md-4 mb-3 d-flex align-items-center pt-4">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_free" value="1" class="form-check-input"
                                               id="is_free" @if(old('is_free')) checked @endif>
                                        <label for="is_free" class="form-check-label">
                                            این پکیج رایگان است (نیازی به پرداخت ندارد)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.packages.index') }}" class="btn btn-secondary">انصراف</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> ذخیره پکیج
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
            // پیش‌نمایش تصویر شاخص
            document.querySelector('input[name="thumbnail_file"]').addEventListener('change', function (e) {
                const container = document.getElementById('thumbnail-preview-container');
                container.innerHTML = '';
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        container.innerHTML = `<img src="${ev.target.result}" class="img-thumbnail" style="max-height: 120px;">`;
                    };
                    reader.readAsDataURL(e.target.files[0]);
                }
            });

            // پیش‌نمایش گالری
            document.querySelector('input[name="gallery[]"]').addEventListener('change', function (e) {
                const preview = document.getElementById('gallery-preview');
                preview.innerHTML = '';
                Array.from(e.target.files).forEach(function (file) {
                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        const col = document.createElement('div');
                        col.className = 'col-4 col-md-3';
                        col.innerHTML = `<img src="${ev.target.result}" class="img-thumbnail" style="height: 80px; object-fit: cover; width: 100%;">`;
                        preview.appendChild(col);
                    };
                    reader.readAsDataURL(file);
                });
            });
        </script>
    @endpush
@endsection
