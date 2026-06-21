@extends('back.layouts.master')

@section('title', 'ویرایش پروژه')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">ویرایش پروژه: {{ $project->name }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.projects.update', $project) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">نام پروژه <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $project->name) }}" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">شناسه (Slug)</label>
                            <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $project->slug) }}">
                            @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">توضیحات</label>
                            <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $project->description) }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="repository_url" class="form-label">لینک مخزن</label>
                            <input type="url" name="repository_url" id="repository_url" class="form-control @error('repository_url') is-invalid @enderror" value="{{ old('repository_url', $project->repository_url) }}">
                            @error('repository_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">وضعیت <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', $project->status) == 'active' ? 'selected' : '' }}>فعال</option>
                                <option value="pending" {{ old('status', $project->status) == 'pending' ? 'selected' : '' }}>در انتظار</option>
                                <option value="archived" {{ old('status', $project->status) == 'archived' ? 'selected' : '' }}>بایگانی شده</option>
                            </select>
                            @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.projects.index') }}" class="btn btn-secondary">انصراف</a>
                            <button type="submit" class="btn btn-primary">بروزرسانی</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
