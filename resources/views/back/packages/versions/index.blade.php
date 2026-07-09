@extends('back.layouts.master')

@section('title', 'نسخه‌های ' . $package->name)

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="feather icon-git-branch"></i> نسخه‌های {{ $package->name }}
                        </h5>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.packages.versions.create', $package) }}" class="btn btn-sm btn-success">
                                <i class="feather icon-upload-cloud"></i> آپلود نسخه جدید
                            </a>
                            <a href="{{ route('admin.packages.show', $package) }}" class="btn btn-sm btn-secondary">
                                <i class="feather icon-arrow-right"></i> بازگشت
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($versions->count())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>نسخه</th>
                                        <th class="text-center">نوع</th>
                                        <th class="text-center">اجباری</th>
                                        <th class="text-center">حجم فایل</th>
                                        <th class="text-center">دانلودها</th>
                                        <th class="text-center">Min PHP</th>
                                        <th class="text-center">Min Laravel</th>
                                        <th class="text-center">وضعیت</th>
                                        <th>تاریخ انتشار</th>
                                        <th class="text-center">عملیات</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($versions as $version)
                                        <tr>
                                            <td>{{ $version->id }}</td>
                                            <td>
                                                <strong>v{{ $version->version }}</strong>
                                                @if ($version->changelog)
                                                    <br><small class="text-muted text-truncate d-inline-block" style="max-width: 250px;">
                                                        {{ $version->changelog }}
                                                    </small>
                                                @endif
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
                                            <td class="text-center"><small>{{ $version->min_php_version ?? '—' }}</small></td>
                                            <td class="text-center"><small>{{ $version->min_laravel_version ?? '—' }}</small></td>
                                            <td class="text-center">
                                                @switch($version->status)
                                                    @case('draft') <span class="badge bg-secondary">draft</span> @break
                                                    @case('active') <span class="badge bg-success">active</span> @break
                                                    @case('archived') <span class="badge bg-dark">archived</span> @break
                                                @endswitch
                                            </td>
                                            <td>{{ $version->release_date ? jdate($version->release_date)->format('Y/m/d') : '—' }}</td>
                                            <td class="text-center text-nowrap">
                                                <a href="{{ route('admin.packages.versions.show', [$package, $version]) }}"
                                                   class="btn btn-sm btn-outline-info" title="مشاهده">
                                                    <i class="feather icon-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.packages.versions.edit', [$package, $version]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="ویرایش">
                                                    <i class="feather icon-edit-2"></i>
                                                </a>
                                                <form action="{{ route('admin.packages.versions.destroy', [$package, $version]) }}"
                                                      method="POST" class="d-inline" onsubmit="return confirm('حذف نسخه؟')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            {{ $versions->links() }}
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="feather icon-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-3">هیچ نسخه‌ای آپلود نشده.</p>
                                <a href="{{ route('admin.packages.versions.create', $package) }}" class="btn btn-success">
                                    <i class="feather icon-upload-cloud"></i> آپلود اولین نسخه
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
