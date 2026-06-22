@extends('back.layouts.master')

@section('title', 'مدیریت کاربران')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users me-2"></i>کاربران سیستم</h2>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> کاربر جدید
            </a>
        </div>


        <!-- فیلترها -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('admin.users.index') }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="جستجو (نام یا ایمیل)" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="role" class="form-select">
                            <option value="">همه نقش‌ها</option>
                            <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>ادمین</option>
                            <option value="operator" {{ request('role') == 'operator' ? 'selected' : '' }}>اپراتور</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>فعال</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-info w-100">فیلتر</button>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary w-100">پاکسازی</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>نام</th>
                        <th>ایمیل</th>
                        <th>نقش</th>
                        <th>وضعیت</th>
                        <th>تاریخ ایجاد</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $loop->iteration + ($users->currentPage() - 1) * $users->perPage() }}</td>
                            <td>
                                <div class="fw-bold">{{ $user->name }}</div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->role === 'admin')
                                    <span class="badge bg-danger">ادمین</span>
                                @else
                                    <span class="badge bg-info text-dark">اپراتور</span>
                                @endif
                            </td>
                            <td>
                                @if($user->status === 'active')
                                    <span class="badge bg-success">فعال</span>
                                @else
                                    <span class="badge bg-secondary">غیرفعال</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at->format('Y/m/d') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-warning" title="تغییر وضعیت">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                    </form>

                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary" title="ویرایش">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('آیا از حذف این کاربر مطمئن هستید؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="حذف" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">هیچ کاربری یافت نشد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection
