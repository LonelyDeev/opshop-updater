@extends('back.layouts.master')

@section('title', 'ایجاد مشتری جدید')

@section('content')
    <div class="content-wrapper">
        <!-- Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">ایجاد مشتری جدید</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">داشبورد</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.updates.index') }}">مشتری‌ها</a></li>
                            <li class="breadcrumb-item active">ایجاد جدید</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-10 offset-lg-1">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">اطلاعات مشتری </h3>
                            </div>
                            <form action="{{ route('admin.customers.store') }}" method="POST">
                                @csrf
                                <div class="row">


                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">نام *</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                           value="{{ old('name') }}" required>
                                    @error('name')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">ایمیل *</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="{{ old('email') }}" required>
                                    @error('email')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">تلفن</label>
                                    <input type="text" class="form-control" id="phone" name="phone"
                                           value="{{ old('phone') }}">
                                    @error('phone')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="website_url" class="form-label">آدرس سایت *</label>
                                    <input type="url" class="form-control" id="website_url" name="website_url"
                                           value="{{ old('website_url') }}" placeholder="https://example.com" required>
                                    @error('website_url')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="project">پروژه <span class="text-danger">*</span></label>
                                            <select name="project_id" id="project" class="form-control" required>
                                                <option value="">انتخاب کنید...</option>
                                                @foreach($projects as $project)
                                                    <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                                        {{ $project->name}}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="invalid-feedback d-block error-project_id"></span>
                                        </div>
                                    </div>

                                <div class="col-md-6 mb-3">
                                    <label for="update_code" class="form-label">کد آپدیت</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="update_code" name="update_code"
                                               value="{{ old('update_code') }}"
                                               placeholder="Leave empty to auto-generate">
                                        <button class="btn btn-outline-secondary" type="button" id="generateCodeBtn">
                                            تولید خودکار
                                        </button>
                                    </div>
                                    <div class="form-text">اگر خالی بگذارید، کد به صورت خودکار تولید می‌شود.</div>
                                    @error('update_code')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="status" class="form-label">وضعیت *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>فعال
                                        </option>
                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>
                                            غیرفعال
                                        </option>
                                    </select>
                                    @error('status')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                </div>
                                <button type="submit" class="btn btn-success">ایجاد مشتری</button>
                                <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">بازگشت</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>


                @endsection

                @push('scripts')
                    <script>
                        document.getElementById('generateCodeBtn').addEventListener('click', function () {
                            fetch('/api/generate-update-code') // یا هر endpoint مناسب دیگر
                                .then(response => response.json())
                                .then(data => document.getElementById('update_code').value = data.code)
                                .catch(err => console.error('Error generating code:', err));
                        });

                        // تولید کد تصادفی در صورت نیاز - نمونه ساده جاوااسکریپت
                        function generateRandomCode(length = 12) {
                            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                            let result = '';
                            for (let i = 0; i < length; i++) {
                                result += chars.charAt(Math.floor(Math.random() * chars.length));
                            }
                            return result;
                        }
                    </script>
    @endpush
