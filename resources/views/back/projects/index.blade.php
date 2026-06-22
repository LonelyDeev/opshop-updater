@extends('back.layouts.master')

@section('title', 'مدیریت پروژه‌ها')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-folder-open me-2"></i>پروژه‌ها</h2>
        <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> پروژه جدید
        </a>
    </div>



    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                <tr>
                    <th>#</th>
                    <th>نام پروژه</th>
                    <th>وضعیت</th>
                    <th>تعداد آپدیت‌ها</th>
                    <th>مخزن</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($projects as $project)
                    <tr>
                        <td>{{ $project->id }}</td>
                        <td>
                            <strong>{{ $project->name }}</strong>
                            <br>
                            <small class="text-muted">{{ $project->slug }}</small>
                        </td>
                        <td>
                            @if($project->status == 'active')
                                <span class="badge bg-success">فعال</span>
                            @elseif($project->status == 'archived')
                                <span class="badge bg-secondary">بایگانی</span>
                            @else
                                <span class="badge bg-warning text-dark">در انتظار</span>
                            @endif
                        </td>
                        <td>{{ $project->updates->count() }}</td>
                        <td>
                            @if($project->repository_url)
                                <a href="{{ $project->repository_url }}" target="_blank" class="text-primary">
                                    <i class="fab fa-github"></i> لینک
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-info text-white" title="جزئیات">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-warning text-white" title="ویرایش">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.projects.destroy', $project) }}" method="POST" class="d-inline" onsubmit="return confirm('آیا مطمئن هستید؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">هیچ پروژه‌ای یافت نشد.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $projects->links() }}
        </div>
    </div>
@endsection
