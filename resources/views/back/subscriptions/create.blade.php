@extends('back.layouts.master')

@section('title', 'ایجاد اشتراک جدید')

@section('content')
    <div class="container-fluid">
        <div class="card shadow w-75 mx-auto">
            <div class="card-header bg-primary text-white p-1">
                <h5 class="mb-0">ثبت اشتراک جدید</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.subscriptions.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <!-- انتخاب مشتری و پروژه -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="customer_id">مشتری <span class="text-danger">*</span></label>
                                <select name="customer_id" id="customer_id" class="form-control" required="">
                                    <option value="">انتخاب کنید...</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email }})</option>
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
                                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                                    @endforeach
                                </select>
                                <span class="invalid-feedback d-block error-type"></span>
                            </div>
                        </div>

                        <!-- تاریخ‌ها -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">تاریخ شروع *</label>
                            <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">مدت اشتراک (ماه) *</label>
                            <input type="number" name="duration_months" class="form-control" value="12" min="1" required>
                            <small class="text-muted">تاریخ انقضا به صورت خودکار محاسبه می‌شود.</small>
                        </div>

                        <!-- بخش مالی -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">قیمت کل (تومان) *</label>
                            <input type="number" name="price" id="price" class="form-control" value="0" min="0" required oninput="calculateFinal()">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">تخفیف (تومان)</label>
                            <input type="number" name="discount" id="discount" class="form-control" value="0" min="0" oninput="calculateFinal()">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">مبلغ نهایی (تومان)</label>
                            <input type="text" id="final_amount_display" class="form-control fw-bold" value="0" readonly>
                        </div>

                        <!-- وضعیت‌ها -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="payment_status">وضعیت پرداخت <span class="text-danger">*</span></label>
                                <select name="payment_status" id="payment_status" class="form-control" required="">
                                    <option value="pending">در انتظار پرداخت</option>
                                    <option value="paid">پرداخت شده</option>
                                    <option value="failed">ناموفق</option>
                                </select>
                                <span class="invalid-feedback d-block error-type"></span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="status">وضعیت اشتراک <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control" required="">
                                    <option value="active">فعال</option>
                                    <option value="inactive">غیرفعال</option>
                                    <option value="expired">منقضی شده</option>
                                </select>
                                <span class="invalid-feedback d-block error-type"></span>
                            </div>
                        </div>


                        <div class="col-12 mb-3">
                            <label class="form-label">توضیحات</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">انصراف</a>
                        <button type="submit" class="btn btn-success">ذخیره اشتراک</button>
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

            // فرمت کردن عدد با کاما
            document.getElementById('final_amount_display').value = final.toLocaleString('fa-IR');
        }
        // اجرای اولیه
        calculateFinal();
    </script>
@endsection
