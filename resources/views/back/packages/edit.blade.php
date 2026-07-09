@extends('back.layouts.master')

@section('title', 'ویرایش پکیج')
@push('styles')
    <style>
            .delete-image-btn {
                left: 0;
                width: 25px;
                margin: 7px 0 0 7px !important;
                height: 25px;
                padding: 0 7px;
                text-align: center;
                border-radius: 5px;
        }
        .sort-handle{
            left: 0;
            top: 36px;
            margin: 0 0 0 5px !important;
            border-radius: 5px;
            padding: 5px 8px;
        }
    </style>
@endpush
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-edit-2"></i> ویرایش پکیج: {{ $package->name }}
                        </h5>
                        <a href="{{ route('admin.packages.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-right"></i> بازگشت
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.packages.update', $package) }}" method="POST" enctype="multipart/form-data">
                            @csrf @method('PUT')

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">نام پکیج <span class="text-danger">*</span></label>
                                    <input type="text" name="name" value="{{ old('name', $package->name) }}"
                                           class="form-control @error('name') is-invalid @enderror" required>
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Slug <span class="text-danger">*</span></label>
                                    <input type="text" name="slug" value="{{ old('slug', $package->slug) }}"
                                           class="form-control @error('slug') is-invalid @enderror" required>
                                    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">نام ماژول</label>
                                    <input type="text" name="module_name" value="{{ old('module_name', $package->module_name) }}"
                                           class="form-control">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">پروژه <span class="text-danger">*</span></label>
                                    <select name="project_id" class="form-select" required>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->id }}" @if(old('project_id', $package->project_id)==$project->id) selected @endif>
                                                {{ $project->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">دسته‌بندی</label>
                                    <select name="category" class="form-select">
                                        <option value="">—</option>
                                        @foreach ($categories as $key => $label)
                                            <option value="{{ $key }}" @if(old('category', $package->category)==$key) selected @endif>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">نویسنده</label>
                                    <input type="text" name="author" value="{{ old('author', $package->author) }}" class="form-control">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">توضیح کوتاه</label>
                                <input type="text" name="short_description" value="{{ old('short_description', $package->short_description) }}"
                                       class="form-control" maxlength="255">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">توضیحات کامل</label>
                                <textarea name="description" rows="5" class="form-control">{{ old('description', $package->description) }}</textarea>
                            </div>

                            {{-- تصویر شاخص --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <strong><i class="fas fa-image"></i> تصویر شاخص</strong>
                                </div>
                                <div class="card-body">
                                    {{-- تصویر فعلی --}}
                                    @if ($package->thumbnail)
                                        <div class="mb-3 d-flex align-items-center gap-2">
                                            <img src="{{ asset($package->thumbnail)}}" class="img-thumbnail" style="max-height: 100px;">
                                            <div>
                                                <small class="d-block text-muted">
                                                    @if ($package->is_thumbnail_uploaded)
                                                        <i class="fas fa-upload"></i> فایل آپلود شده
                                                    @else
                                                        <i class="fas fa-link"></i> از URL
                                                    @endif
                                                </small>
                                                <label class="form-check-label small text-danger">
                                                    <input type="checkbox" name="remove_thumbnail" value="1">
                                                    حذف تصویر فعلی
                                                </label>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label small">آپلود فایل جدید</label>
                                            <input type="file" name="thumbnail_file" accept="image/jpeg,image/png,image/webp,image/gif"
                                                   class="form-control @error('thumbnail_file') is-invalid @enderror">
                                            @error('thumbnail_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            <small class="text-muted">JPG, PNG, WEBP, GIF - حداکثر 3MB</small>

                                            <div id="thumbnail-preview-container" class="mt-2"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">یا URL جدید</label>
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
                                        <strong><i class="fas fagrid"></i> گالری تصاویر</strong>
                                        <small class="text-muted">— {{ $package->images->count() }} تصویر</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="add-gallery-input">
                                        <i class="fas fa-plus"></i> افزودن تصویر
                                    </button>
                                </div>
                                <div class="card-body">
                                    {{-- گالری موجود --}}
                                    @if ($package->images->count())
                                        <div class="row g-2 mb-3" id="gallery-existing">
                                            @foreach ($package->images as $image)
                                                <div class="col-6 col-md-3 gallery-item" data-id="{{ $image->id }}">
                                                    <div class="position-relative">
                                                        <img src="{{ asset($image->url) }}" class="img-thumbnail w-100" style="height: 100px; object-fit: cover;">
                                                        <button type="button"
                                                                class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 delete-image-btn"
                                                                data-package-id="{{ $package->id }}"
                                                                data-image-id="{{ $image->id }}"
                                                                title="حذف">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <span class="badge bg-secondary position-absolute bottom-0 end-0 m-1 sort-handle">
                                               <i class="fa-solid fa-up-down-left-right"></i>
                                            </span>
                                                    </div>
                                                    <small class="text-muted d-block text-truncate">{{ $image->original_name }}</small>
                                                </div>
                                            @endforeach
                                        </div>
                                        <small class="text-muted d-block mb-3">
                                            <i class="fas fa-info"></i> برای تغییر ترتیب، تصاویر را بکشید و رها کنید.
                                        </small>
                                    @endif

                                    {{-- افزودن تصاویر جدید --}}
                                    <label class="form-label small">افزودن تصاویر جدید</label>
                                    <div id="gallery-inputs">
                                        <div class="gallery-input-row mb-2">
                                            <div class="input-group">
                                                <input type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp,image/gif"
                                                       class="form-control" multiple>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted">می‌توانید چند فایل را همزمان انتخاب کنید. حداکثر 10 تصویر در هر بار.</small>
                                    <div id="gallery-preview" class="row mt-2 g-2"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">وضعیت <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select" required>
                                        @foreach ($statuses as $key => $label)
                                            <option value="{{ $key }}" @if(old('status', $package->status)==$key) selected @endif>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">قیمت پیش‌فرض (تومان)</label>
                                    <input type="number" name="default_price" value="{{ old('default_price', $package->default_price) }}"
                                           class="form-control" min="0">
                                </div>

                                <div class="col-md-2 mb-3">
                                    <label class="form-label">ترتیب</label>
                                    <input type="number" name="sort_order" value="{{ old('sort_order', $package->sort_order) }}"
                                           class="form-control" min="0">
                                </div>

                                <div class="col-md-4 mb-3 d-flex align-items-center pt-4">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_free" value="1" class="form-check-input"
                                               id="is_free" @if(old('is_free', $package->is_free)) checked @endif>
                                        <label for="is_free" class="form-check-label">این پکیج رایگان است</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.packages.index') }}" class="btn btn-secondary">انصراف</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> ذخیره تغییرات
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.css">
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <script>
            const csrfToken = '{{ csrf_token() }}';
            const reorderUrl = '{{ route("admin.packages.images.reorder", $package) }}';

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

            // پیش‌نمایش گالری جدید
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

            // حذف تصویر گالری
            document.querySelectorAll('.delete-image-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!confirm('حذف این تصویر؟')) return;
                    const packageId = this.dataset.packageId;
                    const imageId = this.dataset.imageId;
                    const item = this.closest('.gallery-item');

                    fetch(`/admin/packages/${packageId}/images/${imageId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                        .then(r => r.json())
                        .then(resp => {
                            if (resp.success) {
                                item.remove();
                            } else {
                                alert(resp.message || 'خطا در حذف');
                            }
                        })
                        .catch(err => alert('خطا در ارتباط با سرور'));
                });
            });

            // قابلیت drag & drop برای مرتب‌سازی گالری
            const galleryExisting = document.getElementById('gallery-existing');
            if (galleryExisting) {
                Sortable.create(galleryExisting, {
                    animation: 150,
                    handle: '.sort-handle',
                    onEnd: function () {
                        const orderedIds = Array.from(galleryExisting.querySelectorAll('.gallery-item'))
                            .map(el => parseInt(el.dataset.id));

                        fetch(reorderUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ ordered_ids: orderedIds })
                        })
                            .then(r => r.json())
                            .then(resp => {
                                if (!resp.success) console.error('Reorder failed');
                            })
                            .catch(err => console.error(err));
                    }
                });
            }
        </script>
    @endpush
@endsection
