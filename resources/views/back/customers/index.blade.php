@extends('back.layouts.master')

@section('title', 'لیست مشتریان')

@section('content')
    <div class="page-header">
        <h1>لیست مشتریان</h1>
        <div class="breadcrumb">
            <a href="{{ route('admin.dashboard') }}">خانه</a>
            <span>/</span>
            <span>مشتریان</span>
        </div>
    </div>


    <div class="card">
        <div class="card-header">
            <h3 class="card-title">مشتریان فعال</h3>
            <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                مشتری جدید
            </a>
        </div>

        <div class="table-responsive">
            <table class="table ">
                <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>نام</th>
                    <th>ایمیل</th>
                    <th>تلفن</th>
                    <th>آدرس سایت</th>
                    <th>کد آپدیت</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>{{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}</td>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->phone ?? '-' }}</td>
                        <td><a href="{{ $customer->website_url }}" target="_blank">{{ Str::limit($customer->website_url, 30) }}</a></td>
                        <td>
                            {{ $customer->update_code }}
                            <button class="btn btn-sm btn-outline-secondary copy-btn" data-code="{{ $customer->update_code }}">کپی</button>
                        </td>
                        <td>
                            <span class="badge bg-{{ $customer->status === 'active' ? 'success' : 'secondary' }}">
                                {{ $customer->status === 'active' ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-sm btn-warning">ویرایش</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">هیچ مشتری یافت نشد.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $customers->links() }}
    </div>
@endsection
@push('scripts')
    <script>
        document.querySelectorAll('.copy-btn').forEach(button => {
            button.addEventListener('click', () => {
                const code = button.getAttribute('data-code');
                navigator.clipboard.writeText(code).then(() => {
                    button.textContent = 'کپی شد!';
                    setTimeout(() => button.textContent = 'کپی', 2000);
                });
            });
        });
    </script>
@endpush
