@extends('back.layouts.master')

@section('title', 'نسخه ' . $version->version)

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="feather icon-git-commit"></i>
                            نسخه {{ $version->version }} - {{ $package->name }}
                        </h5>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.packages.versions.edit', [$package, $version]) }}" class="btn btn-sm btn-primary">
                                <i class="feather icon-edit-2"></i> ویرایش
                            </a>
                            <a href="{{ route('admin.packages.versions.index', $package) }}" class="btn btn-sm btn-secondary">
                                <i class="feather icon-arrow-right"></i> بازگشت
                            </a>
                        </div>
                    </div>
                    <div class="card-body">

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr><th width="140">نسخه:</th><td><strong>v{{ $version->version }}</strong></td></tr>
                                    <tr><th>نوع:</th>
                                        <td>
                                            @switch($version->type)
                                                @case('major') <span class="badge bg-danger">major</span> @break
                                                @case('minor') <span class="badge bg-warning text-dark">minor</span> @break
                                                @case('patch') <span class="badge bg-info">patch</span> @break
                                            @endswitch
                                        </td>
                                    </tr>
                                    <tr><th>وضعیت:</th>
                                        <td>
                                            @switch($version->status)
                                                @case('draft') <span class="badge bg-secondary">draft</span> @break
                                                @case('active') <span class="badge bg-success">active</span> @break
                                                @case('archived') <span class="badge bg-dark">archived</span> @break
                                            @endswitch
                                        </td>
                                    </tr>
                                    <tr><th>آپدیت اجباری:</th>
                                        <td>
                                            @if ($version->is_mandatory)
                                                <span class="badge bg-danger">بله</span>
                                            @else
                                                <span class="text-muted">خیر</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr><th>تاریخ انتشار:</th>
                                        <td>{{ $version->release_date ? jdate($version->release_date)->format('Y/m/d H:i') : '—' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr><th width="140">حجم فایل:</th><td>{{ $version->file_size_human }}</td></tr>
                                    <tr><th>هش SHA-256:</th><td><code class="text-break" style="font-size: 0.7rem;">{{ $version->file_hash }}</code></td></tr>
                                    <tr><th>Min PHP:</th><td>{{ $version->min_php_version ?? '—' }}</td></tr>
                                    <tr><th>Min Laravel:</th><td>{{ $version->min_laravel_version ?? '—' }}</td></tr>
                                    <tr><th>دانلودها:</th><td>{{ number_format($version->downloads_count) }}</td></tr>
                                </table>
                            </div>
                        </div>

                        {{-- Changelog --}}
                        @if ($version->changelog)
                            <div class="mb-3">
                                <h6 class="border-bottom pb-2"><i class="feather icon-file-text"></i> Changelog</h6>
                                <p class="text-muted">{!! nl2br(e($version->changelog)) !!}</p>
                            </div>
                        @endif

                        @if ($version->what_added)
                            <div class="mb-3">
                                <h6 class="text-success border-bottom pb-2"><i class="feather icon-plus-circle"></i> قابلیت‌های اضافه شده</h6>
                                <p class="text-muted">{!! nl2br(e($version->what_added)) !!}</p>
                            </div>
                        @endif

                        @if ($version->what_changed)
                            <div class="mb-3">
                                <h6 class="text-primary border-bottom pb-2"><i class="feather icon-edit-3"></i> تغییرات</h6>
                                <p class="text-muted">{!! nl2br(e($version->what_changed)) !!}</p>
                            </div>
                        @endif

                        @if ($version->what_fixed)
                            <div class="mb-3">
                                <h6 class="text-warning border-bottom pb-2"><i class="feather icon-check-circle"></i> رفع باگ‌ها</h6>
                                <p class="text-muted">{!! nl2br(e($version->what_fixed)) !!}</p>
                            </div>
                        @endif

                        {{-- وابستگی‌ها --}}
                        @if (!empty($version->dependencies))
                            <div class="mb-3">
                                <h6 class="border-bottom pb-2"><i class="feather icon-link"></i> وابستگی‌ها</h6>
                                <ul class="list-group">
                                    @foreach ($version->dependencies as $slug => $ver)
                                        <li class="list-group-item d-flex justify-content-between">
                                            <code>{{ $slug }}</code>
                                            <span class="badge bg-info">{{ $ver }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- اطلاعات فایل --}}
                        <div class="alert alert-light border">
                            <h6 class="alert-heading"><i class="feather icon-file"></i> اطلاعات فایل</h6>
                            <p class="mb-1"><strong>مسیر ذخیره‌سازی:</strong> <code>{{ $version->file_path }}</code></p>
                            <p class="mb-0"><strong>هش:</strong> <code class="text-break">{{ $version->file_hash }}</code></p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
