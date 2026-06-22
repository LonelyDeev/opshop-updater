@extends('back.layouts.master')

@section('title', 'گزارش آپدیت‌ها')

@section('content')
    <div class="container">
        <h2 class="mb-4">گزارش آپدیت‌ها</h2>

        <form method="GET" action="{{ route('reports.updates') }}" class="card p-3 mb-4 bg-light">
            <div class="row g-2">
                <div class="col-md-4">
                    <select name="project_id" class="form-select">
                        <option value="">همه پروژه‌ها</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>منتشر شده</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>پیش‌نویس</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">اعمال فیلتر</button>
                </div>
            </div>
        </form>

        <table class="table table-hover">
            <thead class="table-dark">
            <tr>
                <th>عنوان</th>
                <th>پروژه</th>
                <th>نسخه</th>
                <th>وضعیت</th>
                <th>تاریخ انتشار</th>
                <th>دانلودها</th>
            </tr>
            </thead>
            <tbody>
            @forelse($updates as $update)
                <tr>
                    <td>{{ $update->title }}</td>
                    <td>{{ $update->project->name ?? '-' }}</td>
                    <td><span class="badge bg-secondary">{{ $update->version }}</span></td>
                    <td>
                        <span class="badge bg-{{ $update->status === 'published' ? 'success' : 'warning' }}">
                            {{ $update->status === 'published' ? 'منتشر شده' : 'پیش‌نویس' }}
                        </span>
                    </td>
                    <td>{{ $update->published_at ? $update->published_at->toJalali() : '-' }}</td>
                    <td>{{ $update->download_count ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">آپدیتی یافت نشد.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        {{ $updates->links() }}
    </div>
@endsection
