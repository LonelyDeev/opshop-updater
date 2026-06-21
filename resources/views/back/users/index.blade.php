@extends('back.layouts.master')

@section('title', 'کاربران')

@section('content')
    <div class="page-header">
        <h1>کاربران</h1>
        <div class="breadcrumb">
            <a href="{{ route('admin.dashboard') }}">خانه</a>
            <span>/</span>
            <span>کاربران</span>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">مدیریت کاربران</h3>
        </div>
        <div class="card-body">
            <p>بخش مدیریت کاربران در حال توسعه است.</p>
        </div>
    </div>
@endsection
