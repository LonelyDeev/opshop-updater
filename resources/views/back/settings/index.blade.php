@extends('back.layouts.master')

@section('title', 'تنظیمات')

@section('content')
    <div class="page-header">
        <h1>تنظیمات</h1>
        <div class="breadcrumb">
            <a href="{{ route('admin.dashboard') }}">خانه</a>
            <span>/</span>
            <span>تنظیمات</span>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">تنظیمات سیستم</h3>
        </div>
        <div class="card-body">
            <p>بخش تنظیمات در حال توسعه است.</p>
        </div>
    </div>
@endsection
