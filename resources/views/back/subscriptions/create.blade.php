@extends('back.layouts.master')

@section('title', 'ایجاد اشتراک جدید')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">ثبت اشتراک جدید</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.subscriptions.store') }}" method="POST">
                            @csrf

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="project_id" class="form-label">انتخاب پروژه *</label>
                                        <select name="project_id" id="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                                            <option value="">پروژه را انتخاب کنید...</option>
                                            @foreach($projects as $project)
                                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                                    {{ $project->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('project_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="customer_id" class="form-label">انتخاب مشتری *</label>
                                        <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                                            <option value="">مشتری را انتخاب کنید...</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }} ({{ $customer->email }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('customer_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>




                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">تاریخ شروع *</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', date('Y-m-d')) }}" required>
                                    @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">تاریخ پایان *</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
                                    @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">وضعیت اولیه *</label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>فعال</option>
                                    <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>تعلیق شده</option>
                                    <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>منقضی شده</option>
                                </select>
                                @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">توضیحات</label>
                                <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">انصراف</a>
                                <button type="submit" class="btn btn-success">ذخیره اشتراک</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // اسکریپت ساده برای اطمینان از اینکه تاریخ پایان بعد از شروع است (اختیاری)
        document.getElementById('start_date').addEventListener('change', function() {
            const endDateInput = document.getElementById('end_date');
            if (!endDateInput.value || endDateInput.value <= this.value) {
                // تنظیم تاریخ پیش‌فرض پایان به ۳۰ روز بعد از شروع
                const startDate = new Date(this.value);
                startDate.setDate(startDate.getDate() + 30);
                endDateInput.valueAsDate = startDate;
            }
        });
    </script>
@endsection
