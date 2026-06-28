@extends('back.layouts.master')

@section('title', 'ویرایش اشتراک')

@section('content')
    <div class="container-fluid">
        <div class="card shadow w-75 mx-auto">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">ویرایش اشتراک</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.subscriptions.update', $subscription) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="customer_id">مشتری <span class="text-danger">*</span></label>
                                <select name="customer_id" id="customer_id" class="form-control" required="">
                                    <option value="">انتخاب کنید...</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ $customer->id == $subscription->customer_id ? 'selected' : '' }}>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="invalid-feedback d-block error-type"></span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="project_id">پروژه <span class="text-danger">*</span></label>
                                <select name="project_id" id="project_id" class="form-control" required="">
                                    <option value="">انتخاب کنید...</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" {{ $project->id == $subscription->project_id ? 'selected' : '' }}>
                                            {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="invalid-feedback d-block error-type"></span>
                            </div>
                        </div>



                        <div class="col-md-6 mb-3">
                            <label class="form-label">تاریخ شروع *</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $subscription->start_date->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">مدت اشتراک (ماه) *</label>
                            <input type="number" name="duration_months" class="form-control" value="{{ $duration }}" min="1" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">قیمت کل (تومان) *</label>
                            <input type="number" name="price" id="price" class="form-control" value="{{ $subscription->price }}" required oninput="calculateFinal()">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">تخفیف (تومان)</label>
                            <input type="number" name="discount" id="discount" class="form-control" value="{{ $subscription->discount }}" oninput="calculateFinal()">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">مبلغ نهایی (تومان)</label>
                            <input type="text" id="final_amount_display" class="form-control fw-bold" value="{{ number_format($subscription->final_amount) }}" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="payment_status">وضعیت پرداخت <span class="text-danger">*</span></label>
                                <select name="payment_status" id="payment_status" class="form-control" required="">
                                    <option value="pending" {{ $subscription->payment_status == 'pending' ? 'selected' : '' }}>در انتظار</option>
                                    <option value="paid" {{ $subscription->payment_status == 'paid' ? 'selected' : '' }}>پرداخت شده</option>
                                    <option value="failed" {{ $subscription->payment_status == 'failed' ? 'selected' : '' }}>ناموفق</option>

                                </select>
                                <span class="invalid-feedback d-block error-type"></span>
                            </div>
                        </div>


                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="status">وضعیت اشتراک <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control" required="">
                                    <option value="active" {{ $subscription->status == 'active' ? 'selected' : '' }}>فعال</option>
                                    <option value="inactive" {{ $subscription->status == 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                                    <option value="expired" {{ $subscription->status == 'expired' ? 'selected' : '' }}>منقضی</option>

                                </select>
                                <span class="invalid-feedback d-block error-type"></span>
                            </div>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">توضیحات</label>
                            <textarea name="description" class="form-control" rows="3">{{ $subscription->description }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">انصراف</a>
                        <button type="submit" class="btn btn-warning">به‌روزرسانی</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function calculateFinal() {
            const price = parseFloat(document.getElementById('price').value) || 0;
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            let final = price - discount;
            if (final < 0) final = 0;
            document.getElementById('final_amount_display').value = final.toLocaleString('fa-IR');
        }
    </script>
@endsection
