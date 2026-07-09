@extends('back.layouts.master')

@section('title', 'مدیریت لایسنس‌ها')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-key"></i> مدیریت لایسنس‌ها
                        </h5>
                        <a href="{{ route('admin.packages.licenses.expire-old') }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-refresh-cw"></i> به‌روزرسانی منقضی‌شده‌ها
                        </a>
                    </div>
                    <div class="card-body">

                        <form method="GET" class="mb-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small">جستجو (کلید لایسنس / مشتری)</label>
                                    <input type="text" name="search" value="{{ request('search') }}"
                                           class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">پکیج</label>
                                    <select name="package_id" class="form-select form-select-sm">
                                        <option value="">همه</option>
                                        @foreach ($packages as $p)
                                            <option value="{{ $p->id }}" @if(request('package_id')==$p->id) selected @endif>{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">وضعیت</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">همه</option>
                                        <option value="active" @if(request('status')=='active') selected @endif>فعال</option>
                                        <option value="expired" @if(request('status')=='expired') selected @endif>منقضی</option>
                                        <option value="revoked" @if(request('status')=='revoked') selected @endif>باطل شده</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        <i class="fas fa-filter"></i>
                                    </button>
                                </div>
                            </div>
                        </form>

                        @if ($licenses->count())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>کلید لایسنس</th>
                                        <th>پکیج</th>
                                        <th>مشتری</th>
                                        <th class="text-center">شروع</th>
                                        <th class="text-center">انقضا</th>
                                        <th class="text-center">روزهای باقی‌مانده</th>
                                        <th class="text-center">وضعیت</th>
                                        <th class="text-center">عملیات</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($licenses as $license)
                                        <tr>
                                            <td>{{ $license->id }}</td>
                                            <td><code class="text-primary">{{ $license->license_key }}</code></td>
                                            <td>{{ $license->package->name ?? '—' }}</td>
                                            <td>
                                                {{ $license->customer->fullname ?? '—' }}
                                                <br><small class="text-muted">{{ $license->customer->mobile ?? '' }}</small>
                                            </td>
                                            <td class="text-center">
                                                {{ $license->starts_at ? jdate($license->starts_at)->format('Y/m/d') : '—' }}
                                            </td>
                                            <td class="text-center">
                                                @if ($license->expires_at)
                                                    {{ jdate($license->expires_at)->format('Y/m/d') }}
                                                @else
                                                    <span class="badge bg-info">نامحدود</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($license->isUnlimited())
                                                    <span class="badge bg-info">∞</span>
                                                @elseif ($license->isExpired())
                                                    <span class="badge bg-danger">منقضی</span>
                                                @else
                                                    <span class="badge bg-success">{{ $license->days_remaining }} روز</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @switch($license->status)
                                                    @case('active')
                                                        <span class="badge bg-success">فعال</span>
                                                        @break
                                                    @case('expired')
                                                        <span class="badge bg-warning text-dark">منقضی</span>
                                                        @break
                                                    @case('revoked')
                                                        <span class="badge bg-danger">باطل شده</span>
                                                        @break
                                                @endswitch
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <a href="{{ route('admin.packages.licenses.show', $license) }}"
                                                   class="btn btn-sm btn-outline-info" title="مشاهده">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if ($license->status === 'active')
                                                    <form action="{{ route('admin.packages.licenses.revoke', $license) }}" method="POST"
                                                          class="d-inline" onsubmit="return confirm('ابطال لایسنس؟')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="ابطال">
                                                            <i class="fas fa-x-circle"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('admin.packages.licenses.activate', $license) }}" method="POST"
                                                          class="d-inline" onsubmit="return confirm('فعال‌سازی مجدد لایسنس؟')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success" title="فعال‌سازی">
                                                            <i class="fas fa-check-circle"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            {{ $licenses->links() }}
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-key" style="font-size: 3rem;"></i>
                                <p class="mt-3">هیچ لایسنسی صادر نشده.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
