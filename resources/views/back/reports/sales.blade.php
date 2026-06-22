@extends('back.layouts.master')

@section('title', 'گزارش فروش و اشتراک‌ها')

@section('content')
    <div class="container">
        <h2 class="mb-4">گزارش فروش و اشتراک‌ها</h2>

        <div class="card mb-4 bg-light">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.sales') }}" class="row g-2">
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>فعال</option>
                            <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>منقضی شده</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">جستجو</button>
                    </div>
                </form>
            </div>
        </div>

        <table class="table table-striped">
            <thead class="table-dark">
            <tr>
                <th>مشتری</th>
                <th>پروژه</th>
                <th>تاریخ شروع</th>
                <th>تاریخ پایان</th>
                <th>وضعیت</th>
                <th>قیمت (تومان)</th>
            </tr>
            </thead>
            <tbody>
            @forelse($subscriptions as $sub)
                <tr>
                    <td>{{ $sub->customer->name }}</td>
                    <td>{{ $sub->project->name ?? '-' }}</td>
                    <td>{{ $sub->start_date->toJalali() }}</td>
                    <td>{{ $sub->expires_at->toJalali() }}</td>
                    <td>
                        @if($sub->expires_at > now())
                            <span class="badge bg-success">فعال</span>
                        @else
                            <span class="badge bg-danger">منقضی شده</span>
                        @endif
                    </td>
                    <td>{{ number_format($sub->price ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">اشتراکی یافت نشد.</td>
                </tr>
            @endforelse
            </tbody>
            <tfoot>
            <tr class="table-active">
                <td colspan="5" class="text-end fw-bold">جمع کل:</td>
                <td class="fw-bold">{{ number_format($subscriptions->sum('price') ?? 0) }} تومان</td>
            </tr>
            </tfoot>
        </table>
        {{ $subscriptions->links() }}
    </div>
@endsection
