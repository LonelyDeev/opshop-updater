@extends('back.layouts.master')

@section('title', 'ویرایش مشتری')

@section('content')
    <div class="container">
        <h2>ویرایش مشتری: {{ $customer->name }}</h2>

        <form action="{{ route('admin.customers.update', $customer) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">نام *</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $customer->name) }}" required>
                @error('name')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">ایمیل *</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $customer->email) }}" required>
                @error('email')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">تلفن</label>
                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}">
                @error('phone')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="website_url" class="form-label">آدرس سایت *</label>
                <input type="url" class="form-control" id="website_url" name="website_url" value="{{ old('website_url', $customer->website_url) }}" required>
                @error('website_url')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="update_code" class="form-label">کد آپدیت *</label>
                <input type="text" class="form-control" id="update_code" name="update_code" value="{{ old('update_code', $customer->update_code) }}" required>
                @error('update_code')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">وضعیت *</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="active" {{ old('status', $customer->status) == 'active' ? 'selected' : '' }}>فعال</option>
                    <option value="inactive" {{ old('status', $customer->status) == 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                </select>
                @error('status')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-warning">به‌روزرسانی مشتری</button>
            <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">بازگشت</a>
        </form>
    </div>
@endsection
