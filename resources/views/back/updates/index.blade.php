@extends('back.layouts.master')

@section('title', 'مدیریت آپدیت‌ها')

@section('content')
    <div class="content-wrapper">
        <!-- Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">مدیریت آپدیت‌ها</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">داشبورد</a></li>
                            <li class="breadcrumb-item active">آپدیت‌ها</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">


                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">لیست آپدیت‌ها</h3>
                                <div class="card-tools">
                                    <a href="{{ route('admin.updates.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus ml-1"></i>
                                        آپدیت جدید
                                    </a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover table-bordered mb-0">
                                        <thead class="bg-light">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>عنوان</th>
                                            <th>نسخه</th>
                                            <th>نوع</th>
                                            <th>وضعیت</th>
                                            <th>اجباری</th>
                                            <th>تاریخ انتشار</th>
                                            <th style="width: 200px;">عملیات</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($updates as $update)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <strong>{{ $update->title }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info">{{ $update->version }}</span>
                                                </td>
                                                <td>
                                                    @if($update->type == 'major')
                                                        <span class="badge badge-danger">اصلی</span>
                                                    @elseif($update->type == 'minor')
                                                        <span class="badge badge-warning">فرعی</span>
                                                    @else
                                                        <span class="badge badge-secondary">اصلاحی</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($update->status == 'active')
                                                        <span class="badge badge-success">فعال</span>
                                                    @elseif($update->status == 'draft')
                                                        <span class="badge badge-warning">پیش‌نویس</span>
                                                    @else
                                                        <span class="badge badge-secondary">بایگانی</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($update->is_mandatory)
                                                        <i class="fas fa-check-circle text-success" title="اجباری"></i>
                                                    @else
                                                        <i class="fas fa-times-circle text-muted" title="اختیاری"></i>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $update->release_date ? \Morilog\Jalali\Jalalian::forge($update->release_date)->format('%Y/%m/%d') : '-' }}
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="{{ route('admin.updates.show', $update->id) }}"
                                                           class="btn btn-info"
                                                           title="مشاهده">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('admin.updates.edit', $update->id) }}"
                                                           class="btn btn-warning"
                                                           title="ویرایش">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('admin.updates.destroy', $update->id) }}"
                                                              method="POST"
                                                              class="d-inline"
                                                              onsubmit="return confirm('آیا از حذف این آپدیت مطمئن هستید؟');">
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
                                                <td colspan="8" class="text-center py-5">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">هیچ آپدیتی ثبت نشده است</p>
                                                    <a href="{{ route('admin.updates.create') }}" class="btn btn-primary btn-sm mt-2">
                                                        <i class="fas fa-plus ml-1"></i>
                                                        ایجاد اولین آپدیت
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            @if($updates->hasPages())
                                <div class="card-footer clearfix">
                                    {{ $updates->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
