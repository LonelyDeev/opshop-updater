@extends('back.layouts.master')

@section('title', 'اشتراک‌ها')

@section('content')
    <div class="page-header d-flex justify-content-between">
        <div>
            <h1>اشتراک‌ها</h1>
            <div class="breadcrumb">
                <a href="{{ route('admin.dashboard') }}">خانه</a>
                <span>/</span>
                <span>اشتراک‌ها</span>
            </div>
        </div>
        <a href="{{ route('admin.subscriptions.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> اشتراک جدید
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table  mb-0">
                    <thead class="bg-light">
                    <tr>
                        <th class="ps-4">#</th>
                        <th>مشتری</th>
                        <th>پروژه</th>
                        <th>تاریخ شروع</th>
                        <th>تاریخ پایان</th>
                        <th>روزهای باقی‌مانده</th>
                        <th>وضعیت</th>
                        <th class="text-end pe-4">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($subscriptions as $sub)
                        <tr>
                            <td class="ps-4">{{ $loop->iteration + ($subscriptions->currentPage() - 1) * $subscriptions->perPage() }}</td>
                            <td>
                                <div class="fw-bold">{{ $sub->customer->name }}</div>
                                <small class="text-muted">{{ $sub->customer->email }}</small>
                            </td>
                            <td>{{ $sub->project->name }}</td>
                            <td dir="ltr">{{ $sub->start_date->format('Y/m/d') }}</td>
                            <td dir="ltr">{{ $sub->end_date->format('Y/m/d') }}</td>
                            <td>
                                @php $days = $sub->daysRemaining(); @endphp
                                @if($days > 0)
                                    <span class="badge bg-info text-dark">{{ $days }} روز</span>
                                @elseif($days == 0)
                                    <span class="badge bg-warning text-dark">امروز</span>
                                @else
                                    <span class="badge bg-danger">منقضی</span>
                                @endif
                            </td>
                            <td>
                                @if($sub->status == 'active')
                                    <span class="badge bg-success">فعال</span>
                                @elseif($sub->status == 'expired')
                                    <span class="badge bg-danger">منقضی شده</span>
                                @else
                                    <span class="badge bg-secondary">تعلیق شده</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('subscriptions.edit', $sub) }}" class="btn btn-outline-warning" title="ویرایش">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('subscriptions.destroy', $sub) }}" method="POST" class="d-inline" onsubmit="return confirm('آیا مطمئن هستید؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">هیچ اشتراکی یافت نشد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $subscriptions->links() }}
    </div>
@endsection
