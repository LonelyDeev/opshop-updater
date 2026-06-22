@extends('back.layouts.master')

@section('title', 'مدیریت اشتراک‌ها')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>اشتراک‌ها و فروش</h2>
            <a href="{{ route('admin.subscriptions.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> اشتراک جدید
            </a>
        </div>


        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>مشتری</th>
                            <th>پروژه</th>
                            <th>تاریخ شروع</th>
                            <th>تاریخ انقضا</th>
                            <th>قیمت (تومان)</th>
                            <th>تخفیف</th>
                            <th>مبلغ نهایی</th>
                            <th>وضعیت پرداخت</th>
                            <th>وضعیت اشتراک</th>
                            <th>عملیات</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($subscriptions as $sub)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $sub->customer->name }}</td>
                                <td>{{ $sub->project->name ?? '-' }}</td>
                                <td>{{ $sub->start_date->format('Y/m/d') }}</td>
                                <td>
                                    {{ $sub->expires_at ? $sub->expires_at->format('Y/m/d') : 'نامحدود' }}
                                    @if($sub->isExpired())
                                        <span class="badge bg-danger ms-1">منقضی</span>
                                    @endif
                                </td>
                                <td>{{ number_format($sub->price) }}</td>
                                <td class="text-success">{{ number_format($sub->discount) }}</td>
                                <td class="fw-bold">{{ number_format($sub->final_amount) }}</td>
                                <td>
                                    @if($sub->payment_status == 'paid')
                                        <span class="badge bg-success">پرداخت شده</span>
                                    @elseif($sub->payment_status == 'pending')
                                        <span class="badge bg-warning text-dark">در انتظار</span>
                                    @else
                                        <span class="badge bg-danger">ناموفق</span>
                                    @endif
                                </td>
                                <td>
                                <span class="badge bg-{{ $sub->status == 'active' ? 'info' : 'secondary' }}">
                                    {{ $sub->status == 'active' ? 'فعال' : 'غیرفعال' }}
                                </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.subscriptions.edit', $sub) }}" class="btn btn-outline-primary">ویرایش</a>
                                        <form action="{{ route('admin.subscriptions.destroy', $sub) }}" method="POST" class="d-inline" onsubmit="return confirm('آیا مطمئن هستید؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4">هیچ اشتراکی یافت نشد.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $subscriptions->links() }}
            </div>
        </div>
    </div>
@endsection
