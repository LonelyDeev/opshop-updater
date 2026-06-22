@extends('layouts.app')

@section('title', 'ویرایش اشتراک')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">ویرایش اشتراک</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('subscriptions.update', $subscription) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="customer_id" class="form-label">مشتری *</label>
                                <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id', $subscription->customer_id) == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="project_id" class="form-label">پروژه *</label>
                                <select name="project_id" id="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" {{ old('project_id', $subscription->project_id) == $project->id ? 'selected' : '' }}>
                                            {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('project_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">تاریخ شروع *</label>
                                    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $subscription->start_date->format('Y-m-d')) }}" required>
                                    @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">تاریخ پایان *</label>
                                    <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $subscription->end_date->format('Y-m-d')) }}" required>
                                    @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">وضعیت *</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="active" {{ old('status', $subscription->status) == 'active' ? 'selected' : '' }}>فعال</option>
                                    <option value="suspended" {{ old('status', $subscription->status) == 'suspended' ? 'selected' : '' }}>تعلیق شده</option>
                                    <option value="expired" {{ old('status', $subscription->status) == 'expired' ? 'selected' : '' }}>منقضی شده</option>
                                </select>
                                @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">توضیحات</label>
                                <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $subscription->description) }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('subscriptions.index') }}" class="btn btn-secondary">بازگشت</a>
                                <button type="submit" class="btn btn-warning">به‌روزرسانی</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
