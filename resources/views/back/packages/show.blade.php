@extends('back.layouts.master')

@section('title', $package->name)

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="feather icon-package"></i> {{ $package->name }}
                            <span class="badge bg-light text-dark ms-2">{{ $package->slug }}</span>
                        </h5>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.packages.versions.create', $package) }}" class="btn btn-sm btn-success">
                                <i class="feather icon-upload-cloud"></i> آپلود نسخه جدید
                            </a>
                            <a href="{{ route('admin.packages.plans.create', $package) }}" class="btn btn-sm btn-warning">
                                <i class="feather icon-tag"></i> طرح قیمت
                            </a>
                            <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-sm btn-primary">
                                <i class="feather icon-edit-2"></i> ویرایش
                            </a>
                            <a href="{{ route('admin.packages.index') }}" class="btn btn-sm btn-secondary">
                                <i class="feather icon-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">

                        {{-- اطلاعات کلی --}}
                        <div class="row mb-4">
                            <div class="col-md-3 text-center">
                                <img src="{{ $package->thumbnail_url }}" class="img-thumbnail" style="max-height: 200px; object-fit: cover; width: 100%;">
                                <small class="text-muted d-block mt-1">تصویر شاخص</small>
                            </div>
                            <div class="col-md-9">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="120">پروژه:</th>
                                        <td>{{ $package->project->name ?? '—' }}</td>
                                        <th width="120">دسته:</th>
                                        <td>{{ $package->category ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th>نویسنده:</th>
                                        <td>{{ $package->author ?? '—' }}</td>
                                        <th>نام ماژول:</th>
                                        <td><code>{{ $package->module_name ?? '—' }}</code></td>
                                    </tr>
                                    <tr>
                                        <th>وضعیت:</th>
                                        <td>
                                            @switch($package->status)
                                                @case('draft') <span class="badge bg-secondary">پیش‌نویس</span> @break
                                                @case('active') <span class="badge bg-success">منتشر شده</span> @break
                                                @case('archived') <span class="badge bg-dark">آرشیو</span> @break
                                            @endswitch
                                        </td>
                                        <th>رایگان:</th>
                                        <td>
                                            @if ($package->is_free)
                                                <span class="badge bg-success">بله</span>
                                            @else
                                                <span class="badge bg-warning">خیر</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>دانلودها:</th>
                                        <td>{{ number_format($package->downloads_count) }}</td>
                                        <th>خریدها:</th>
                                        <td>{{ number_format($package->purchases_count) }}</td>
                                    </tr>
                                </table>

                                @if ($package->short_description)
                                    <p class="text-muted"><strong>خلاصه:</strong> {{ $package->short_description }}</p>
                                @endif
                                @if ($package->description)
                                    <div class="border-top pt-2 mt-2">{!! $package->description !!}</div>
                                @endif
                            </div>
                        </div>

                        {{-- گالری تصاویر --}}
                        @if ($package->images->count())
                            <div class="mb-4">
                                <h6 class="mb-2"><i class="feather icon-grid"></i> گالری تصاویر ({{ $package->images->count() }})</h6>
                                <div class="row g-2">
                                    @foreach ($package->images as $image)
                                        <div class="col-6 col-md-3 col-lg-2">
                                            <a href="{{ $image->url }}" target="_blank">
                                                <img src="{{ $image->url }}" class="img-thumbnail w-100"
                                                     style="height: 120px; object-fit: cover;" title="{{ $image->alt ?: $image->original_name }}">
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- آخرین نسخه‌ها --}}
                        <h6 class="mb-2">
                            <i class="feather icon-git-branch"></i> نسخه‌ها ({{ $package->versions->count() }})
                            <a href="{{ route('admin.packages.versions.index', $package) }}" class="btn btn-sm btn-link">همه →</a>
                        </h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                <tr>
                                    <th>نسخه</th>
                                    <th class="text-center">نوع</th>
                                    <th class="text-center">اجباری</th>
                                    <th class="text-center">فایل</th>
                                    <th class="text-center">دانلود</th>
                                    <th class="text-center">وضعیت</th>
                                    <th>تاریخ انتشار</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($package->versions->take(5) as $version)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.packages.versions.show', [$package, $version]) }}">
                                                <strong>v{{ $version->version }}</strong>
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            @switch($version->type)
                                                @case('major') <span class="badge bg-danger">major</span> @break
                                                @case('minor') <span class="badge bg-warning text-dark">minor</span> @break
                                                @case('patch') <span class="badge bg-info">patch</span> @break
                                            @endswitch
                                        </td>
                                        <td class="text-center">
                                            @if ($version->is_mandatory)
                                                <i class="feather icon-alert-triangle text-danger"></i>
                                            @else — @endif
                                        </td>
                                        <td class="text-center"><small>{{ $version->file_size_human }}</small></td>
                                        <td class="text-center">{{ number_format($version->downloads_count) }}</td>
                                        <td class="text-center">
                                            @switch($version->status)
                                                @case('draft') <span class="badge bg-secondary">draft</span> @break
                                                @case('active') <span class="badge bg-success">active</span> @break
                                                @case('archived') <span class="badge bg-dark">archived</span> @break
                                            @endswitch
                                        </td>
                                        <td>{{ $version->release_date ? jdate($version->release_date)->format('Y/m/d') : '—' }}</td>
                                    </tr>
                                @endforeach
                                @if ($package->versions->isEmpty())
                                    <tr><td colspan="7" class="text-center text-muted py-3">نسخه‌ای آپلود نشده.</td></tr>
                                @endif
                                </tbody>
                            </table>
                        </div>

                        {{-- طرح‌های قیمت‌گذاری --}}
                        <h6 class="mb-2">
                            <i class="feather icon-tag"></i> طرح‌های قیمت‌گذاری ({{ $package->pricingPlans->count() }})
                            <a href="{{ route('admin.packages.plans.index', $package) }}" class="btn btn-sm btn-link">مدیریت →</a>
                        </h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                <tr>
                                    <th>نام طرح</th>
                                    <th class="text-center">مدت</th>
                                    <th class="text-center">قیمت اصلی</th>
                                    <th class="text-center">قیمت با تخفیف</th>
                                    <th class="text-center">تخفیف</th>
                                    <th class="text-center">وضعیت</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($package->pricingPlans as $plan)
                                    <tr>
                                        <td>
                                            {{ $plan->name }}
                                            @if ($plan->is_one_time)
                                                <span class="badge bg-warning text-dark" title="طرح یک‌بار مصرف">
                                                <i class="feather icon-gift"></i> یک‌بار مصرف
                                            </span>
                                            @endif
                                            @if ($plan->description)
                                                <br><small class="text-muted">{{ $plan->description }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $plan->duration_label }}</td>
                                        <td class="text-center">{{ number_format($plan->price) }}</td>
                                        <td class="text-center">
                                            @if ($plan->has_discount)
                                                <span class="text-success">{{ number_format($plan->discount_price) }}</span>
                                            @else — @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($plan->has_discount)
                                                <span class="badge bg-danger">{{ $plan->discount_percent }}%</span>
                                            @else — @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($plan->is_active)
                                                <span class="badge bg-success">فعال</span>
                                            @else
                                                <span class="badge bg-secondary">غیرفعال</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                @if ($package->pricingPlans->isEmpty())
                                    <tr><td colspan="6" class="text-center text-muted py-3">طرحی تعریف نشده.</td></tr>
                                @endif
                                </tbody>
                            </table>
                        </div>

                        {{-- لایسنس‌های اخیر --}}
                        <h6 class="mb-2">
                            <i class="feather icon-key"></i> لایسنس‌های اخیر
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                <tr>
                                    <th>کلید لایسنس</th>
                                    <th>مشتری</th>
                                    <th class="text-center">انقضا</th>
                                    <th class="text-center">روزهای باقی‌مانده</th>
                                    <th class="text-center">وضعیت</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($package->licenses->take(10) as $license)
                                    <tr>
                                        <td><code>{{ $license->license_key }}</code></td>
                                        <td>{{ $license->customer->fullname ?? '—' }}</td>
                                        <td class="text-center">
                                            {{ $license->expires_at ? jdate($license->expires_at)->format('Y/m/d') : 'نامحدود' }}
                                        </td>
                                        <td class="text-center">
                                            @if ($license->isUnlimited())
                                                <span class="badge bg-info">∞</span>
                                            @else
                                                {{ $license->days_remaining }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @switch($license->status)
                                                @case('active') <span class="badge bg-success">فعال</span> @break
                                                @case('expired') <span class="badge bg-warning text-dark">منقضی</span> @break
                                                @case('revoked') <span class="badge bg-danger">باطل شده</span> @break
                                            @endswitch
                                        </td>
                                    </tr>
                                @endforeach
                                @if ($package->licenses->isEmpty())
                                    <tr><td colspan="5" class="text-center text-muted py-3">لایسنسی صادر نشده.</td></tr>
                                @endif
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
