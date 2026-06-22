@extends('back.layouts.master')

@section('title', 'گزارش مشتریان')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>گزارش مشتریان</h2>
            <button class="btn btn-outline-secondary" onclick="window.print()">چاپ گزارش</button>
        </div>

        <!-- فیلترها -->
        <form method="GET" action="{{ route('reports.customers') }}" class="card p-3 mb-4 bg-light">
            <div class="row g-2">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}" placeholder="از تاریخ">
                </div>
                <div class="col-md-3">
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}" placeholder="تا تاریخ">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">اعمال فیلتر</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>نام</th>
                    <th>ایمیل</th>
                    <th>سایت</th>
                    <th>کد آپدیت</th>
                    <th>تاریخ ثبت‌نام</th>
                    <th>وضعیت</th>
                </tr>
                </thead>
                <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>{{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}</td>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->email }}</td>
                        <td><a href="{{ $customer->website_url }}" target="_blank" class="text-truncate d-inline-block" style="max-width: 150px;">{{ $customer->website_url }}</a></td>
                        <td><code>{{ $customer->update_code }}</code></td>
                        <td>{{ $customer->created_at->toJalali() }}</td>
                        <td>
                            <span class="badge bg-{{ $customer->status === 'active' ? 'success' : 'secondary' }}">
                                {{ $customer->status === 'active' ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">داده‌ای یافت نشد.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $customers->links() }}
    </div>
@endsection
