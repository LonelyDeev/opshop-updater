<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'پنل مدیریت') - سیستم مدیریت آپدیت‌ها</title>

    <!-- Styles -->
    <link rel="stylesheet" type="text/css" href="{{ asset('back/assets/css/bootstrap/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/assets/css/bootstrap/bootstrap-extended.css') }}">
    <link rel="stylesheet" href="{{ asset('back/assets/fonts/vazir/style.css') }}">
    <link rel="stylesheet" href="{{ asset('back/assets/css/admin.css') }}">

    <!-- Font Awesome (Optional - for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @stack('styles')
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    @include('back.partials.sidebar')

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar">
            <div class="navbar-left">
                <button class="navbar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="navbar-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="جستجو...">
                </div>
            </div>

            <div class="navbar-right">
                <div class="navbar-notification">
                    <i class="fas fa-bell"></i>
                    <span class="badge">3</span>
                </div>

                <div class="navbar-notification">
                    <i class="fas fa-envelope"></i>
                    <span class="badge">5</span>
                </div>

                <div class="user-profile">
                    <div class="user-avatar">
                        {{ substr(Auth::user()->name ?? 'Admin', 0, 1) }}
                    </div>
                    <div class="user-info">
                        <span class="user-name">{{ Auth::user()->name ?? 'مدیر سیستم' }}</span>
                        <span class="user-role">مدیر کل</span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="page-content">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
<script src="{{ asset('back/assets/js/vendors/vendors.min.js') }}"></script>
<!-- Scripts -->
<script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('active');
    }
</script>

@stack('scripts')
</body>
</html>
