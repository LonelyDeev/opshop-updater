@extends('back.layouts.master')

@section('title', 'طرح‌های قیمت‌گذاری - ' . $package->name)

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="feather icon-tag"></i> طرح‌های قیمت‌گذاری: {{ $package->name }}
                        </h5>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.packages.plans.create', $package) }}" class="btn btn-sm btn-success">
                                <i class="feather icon-plus"></i> طرح جدید
                            </a>
                            <a href="{{ route('admin.packages.show', $package) }}" class="btn btn-sm btn-secondary">
                                <i class="feather icon-arrow-right"></i> بازگشت
                            </a>
                        </div>
                    </div>
                    <div class="card-body">

                        <div class="alert alert-info">
                            <i class="feather icon-info"></i>
                            برای هر پکیج می‌توانید چندین طرح قیمت‌گذاری تعریف کنید.
                            مثلاً: 6 ماهه با 100,000 تومان، 12 ماهه با 180,000 تومان، نامحدود با 500,000 تومان.
                            هر طرح می‌تواند تخفیف جداگانه داشته باشد.
                        </div>

                        @if ($plans->count())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>نام طرح</th>
                                        <th class="text-center">مدت</th>
                                        <th class="text-center">قیمت اصلی</th>
                                        <th class="text-center">قیمت با تخفیف</th>
                                        <th class="text-center">درصد تخفیف</th>
                                        <th class="text-center">قیمت نهایی</th>
                                        <th class="text-center">وضعیت</th>
                                        <th class="text-center">عملیات</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($plans as $plan)
                                        <tr>
                                            <td>{{ $plan->id }}</td>
                                            <td>
                                                <strong>{{ $plan->name }}</strong>
                                                @if ($plan->is_one_time)
                                                    <span class="badge bg-warning text-dark ms-1" title="طرح یک‌بار مصرف">
                                                <i class="feather icon-gift"></i> یک‌بار مصرف
                                            </span>
                                                @endif
                                                @if ($plan->description)
                                                    <br><small class="text-muted">{{ $plan->description }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center"><span class="badge bg-info">{{ $plan->duration_label }}</span></td>
                                            <td class="text-center">{{ number_format($plan->price) }} تومان</td>
                                            <td class="text-center">
                                                @if ($plan->has_discount)
                                                    <span class="text-success">{{ number_format($plan->discount_price) }}</span>
                                                @else — @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($plan->has_discount)
                                                    <span class="badge bg-danger">{{ $plan->discount_percent }}%</span>
                                                @else — @endif
                                            </td>
                                            <td class="text-center"><strong>{{ number_format($plan->final_price) }}</strong></td>
                                            <td class="text-center">
                                                @if ($plan->is_active)
                                                    <span class="badge bg-success">فعال</span>
                                                @else
                                                    <span class="badge bg-secondary">غیرفعال</span>
                                                @endif
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <a href="{{ route('admin.packages.plans.edit', [$package, $plan]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="ویرایش">
                                                    <i class="feather icon-edit-2"></i>
                                                </a>
                                                <form action="{{ route('admin.packages.plans.destroy', [$package, $plan]) }}"
                                                      method="POST" class="d-inline" onsubmit="return confirm('حذف طرح؟')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                                        <i class="feather icon-trash-2"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="feather icon-tag" style="font-size: 3rem;"></i>
                                <p class="mt-3">هنوز طرحی تعریف نشده.</p>
                                <a href="{{ route('admin.packages.plans.create', $package) }}" class="btn btn-success">
                                    <i class="feather icon-plus"></i> ایجاد اولین طرح
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
