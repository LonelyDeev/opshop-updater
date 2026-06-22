@extends('back.layouts.master')

@section('title', 'ویرایش کاربر')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">ویرایش کاربر: {{ $user->name }}</h5>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-secondary">بازگشت</a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.users.update', $user) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="name" class="form-label">نام کامل *</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">آدرس ایمیل *</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="alert alert-info">
                                <small>برای تغییر رمز عبور، فیلدهای زیر را پر کنید. در غیر این صورت خالی بگذارید.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">رمز عبور جدید</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                                    @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="password_confirmation" class="form-label">تکرار رمز عبور جدید</label>
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">نقش کاربری *</label>
                                    <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                        <option value="operator" {{ old('role', $user->role) == 'operator' ? 'selected' : '' }}>اپراتور</option>
                                        <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>ادمین</option>
                                    </select>
                                    @if($user->id === auth()->id())
                                        <input type="hidden" name="role" value="{{ $user->role }}">
                                        <small class="text-muted">شما نمی‌توانید نقش خودتان را تغییر دهید.</small>
                                    @endif
                                    @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">وضعیت *</label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                        <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>فعال</option>
                                        <option value="inactive" {{ old('status', $user->status) == 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                                    </select>
                                    @if($user->id === auth()->id())
                                        <input type="hidden" name="status" value="{{ $user->status }}">
                                        <small class="text-muted">شما نمی‌توانید وضعیت خودتان را تغییر دهید.</small>
                                    @endif
                                    @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-warning">به‌روزرسانی کاربر</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
