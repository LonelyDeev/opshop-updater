<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به پنل مدیریت</title>
    <!-- فونت وزیرمتن -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <!-- آیکون‌ها -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- استایل اختصاصی -->
    <link rel="stylesheet" href="{{ asset('back/assets/css/auth-style.css') }}">
</head>
<body>
<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <div class="logo-circle">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h2>پنل مدیریت آپدیت</h2>
            <p>برای ادامه وارد حساب خود شوید</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="login-form">
            @csrf

            <div class="input-group">
                <label for="email">ایمیل</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="example@mail.com" required autofocus>
                </div>
            </div>

            <div class="input-group">
                <label for="password">رمز عبور</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="********" required>
                    <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
                </div>
            </div>

            <div class="options">
                <label class="checkbox-container">
                    <input type="checkbox" name="remember">
                    <span class="checkmark"></span>
                    مرا به خاطر بسپار
                </label>
                <a href="#" class="forgot-link">رمز عبور را فراموش کرده‌اید؟</a>
            </div>

            <button type="submit" class="btn-login">
                ورود به پنل
                <i class="fa-solid fa-arrow-left"></i>
            </button>
        </form>

        <div class="login-footer">
            <p>&copy; {{ date('Y') }} تمامی حقوق محفوظ است.</p>
        </div>
    </div>

    <!-- المان‌های تزئینی پس‌زمینه -->
    <div class="background-shape shape-1"></div>
    <div class="background-shape shape-2"></div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.classList.toggle('fa-eye-slash');
    });
</script>
</body>
</html>
