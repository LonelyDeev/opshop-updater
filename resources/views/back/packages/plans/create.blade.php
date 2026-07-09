@extends('back.layouts.master')

@section('title', 'طرح قیمت‌گذاری جدید')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle"></i> طرح قیمت‌گذاری جدید
                        </h5>
                        <a href="{{ route('admin.packages.plans.index', $package) }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-right"></i> بازگشت
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.packages.plans.store', $package) }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">نام طرح <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}"
                                       class="form-control" placeholder="مثلاً: طرح 6 ماهه" required>
                                <small class="text-muted">این نام به مشتری نمایش داده می‌شود.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">مدت (ماه) <span class="text-danger">*</span></label>
                                    <input type="number" name="duration_months" value="{{ old('duration_months', 6) }}"
                                           class="form-control" min="0" max="120" required>
                                    <small class="text-muted">0 = نامحدود</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">قیمت اصلی (تومان) <span class="text-danger">*</span></label>
                                    <input type="number" name="price" value="{{ old('price', 0) }}"
                                           class="form-control" min="0" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">قیمت با تخفیف (تومان)</label>
                                    <input type="number" name="discount_price" value="{{ old('discount_price') }}"
                                           class="form-control" min="0">
                                    <small class="text-muted">خالی بگذارید = بدون تخفیف</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">ترتیب</label>
                                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                                           class="form-control" min="0">
                                </div>
                                <div class="col-md-3 mb-3 d-flex align-items-end">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                               id="is_active" @if(old('is_active', true)) checked @endif>
                                        <label for="is_active" class="form-check-label">فعال</label>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info"></i>
                                <strong>نکته:</strong>
                                برای پکیج‌های رایگان، قیمت را 0 قرار دهید. در این حالت، طرح‌های مدت‌دار فقط برای مدیریت انقضای لایسنس استفاده می‌شوند.
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.packages.plans.index', $package) }}" class="btn btn-secondary">انصراف</a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> ذخیره طرح
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
