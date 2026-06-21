@extends('back.layouts.master')

@section('title', 'ایجاد پروژه جدید')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">اطلاعات پروژه</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.projects.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">نام پروژه <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">شناسه (Slug)</label>
                            <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}">
                            <small class="text-muted">اگر خالی بگذارید، автоматически از روی نام ساخته می‌شود.</small>
                            @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">توضیحات</label>
                            <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="repository_url" class="form-label">لینک مخزن (Git Repository)</label>
                            <input type="url" name="repository_url" id="repository_url" class="form-control @error('repository_url') is-invalid @enderror" value="{{ old('repository_url') }}" placeholder="https://github.com/...">
                            @error('repository_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">وضعیت <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>فعال</option>
                                <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                                <option value="archived" {{ old('status') == 'archived' ? 'selected' : '' }}>بایگانی شده</option>
                            </select>
                            @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.projects.index') }}" class="btn btn-secondary">انصراف</a>
                            <button type="submit" class="btn btn-primary">ذخیره پروژه</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
