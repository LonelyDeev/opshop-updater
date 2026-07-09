@extends('back.layouts.master')

@section('title', 'مدیریت پکیج‌ها')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-package"></i> مدیریت پکیج‌ها
                        </h5>
                        <a href="{{ route('admin.packages.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> پکیج جدید
                        </a>
                    </div>
                    <div class="card-body">

                        {{-- فیلترها --}}
                        <form method="GET" class="mb-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small">جستجو</label>
                                    <input type="text" name="search" value="{{ request('search') }}"
                                           class="form-control form-control-sm" placeholder="نام یا slug...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">پروژه</label>
                                    <select name="project_id" class="form-select form-select-sm">
                                        <option value="">همه</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->id }}" @if(request('project_id')==$project->id) selected @endif>
                                                {{ $project->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">وضعیت</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">همه</option>
                                        <option value="draft" @if(request('status')=='draft') selected @endif>پیش‌نویس</option>
                                        <option value="active" @if(request('status')=='active') selected @endif>منتشر شده</option>
                                        <option value="archived" @if(request('status')=='archived') selected @endif>آرشیو شده</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex gap-1">
                                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                                        <i class="fas fa-filter"></i>
                                    </button>
                                    <a href="{{ route('admin.packages.index') }}" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-x"></i>
                                    </a>
                                </div>
                            </div>
                        </form>

                        {{-- جدول --}}
                        @if ($packages->count())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>نام پکیج</th>
                                        <th>پروژه</th>
                                        <th class="text-center">آخرین نسخه</th>
                                        <th class="text-center">قیمت</th>
                                        <th class="text-center">دانلود</th>
                                        <th class="text-center">خرید</th>
                                        <th class="text-center">وضعیت</th>
                                        <th class="text-center">عملیات</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($packages as $package)
                                        <tr>
                                            <td>{{ $package->id }}</td>
                                            <td>
                                                <strong>{{ $package->name }}</strong>
                                                <br><small class="text-muted">{{ $package->slug }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">{{ $package->project->name ?? '—' }}</span>
                                            </td>
                                            <td class="text-center">
                                                @php $v = $package->versions->first(); @endphp
                                                @if ($v)
                                                    <span class="badge bg-info">v{{ $v->version }}</span>
                                                    @if ($v->is_mandatory)
                                                        <span class="badge bg-danger" title="اجباری">*</span>
                                                    @endif
                                                @else
                                                    <small class="text-muted">—</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($package->is_free)
                                                    <span class="badge bg-success">رایگان</span>
                                                @else
                                                    {{ number_format($package->default_price) }}
                                                @endif
                                            </td>
                                            <td class="text-center">{{ number_format($package->downloads_count) }}</td>
                                            <td class="text-center">{{ number_format($package->purchases_count) }}</td>
                                            <td class="text-center">
                                                @switch($package->status)
                                                    @case('draft') <span class="badge bg-secondary">پیش‌نویس</span> @break
                                                    @case('active') <span class="badge bg-success">منتشر شده</span> @break
                                                    @case('archived') <span class="badge bg-dark">آرشیو</span> @break
                                                @endswitch
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <div class="d-flex flex-wrap gap-2">
                                                    <a href="{{ route('admin.packages.show', $package) }}"
                                                       class="btn btn-sm btn-outline-info" title="مشاهده">
                                                        مشاهده
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.packages.versions.index', $package) }}"
                                                       class="btn btn-sm btn-outline-secondary" title="نسخه‌ها">
                                                        نسخه‌ها
                                                        <i class="fas fa-git-branch"></i>
                                                    </a>
                                                    <a href="{{ route('admin.packages.plans.index', $package) }}"
                                                       class="btn btn-sm btn-outline-warning" title="قیمت‌گذاری">
                                                        قیمت‌گذاری
                                                        <i class="fas fa-tag"></i>
                                                    </a>
                                                    <a href="{{ route('admin.packages.edit', $package) }}"
                                                       class="btn btn-sm btn-outline-primary" title="ویرایش">
                                                        ویرایش
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.packages.destroy', $package) }}" method="POST"
                                                          class="d-inline" onsubmit="return confirm('حذف پکیج و تمام نسخه‌ها؟')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                                            حذف
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>

                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{ $packages->links() }}
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-3">هیچ پکیجی یافت نشد.</p>
                                <a href="{{ route('admin.packages.create') }}" class="btn btn-primary">ایجاد اولین پکیج</a>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
