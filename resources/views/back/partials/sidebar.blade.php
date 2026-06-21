<!-- Sidebar Component -->
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>پنل مدیریت</h2>
        <p>سیستم مدیریت آپدیت‌ها</p>
    </div>

    <nav class="sidebar-menu">
        <ul>
            <li class="menu-title">اصلی</li>
            <li>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i>📊</i>
                    <span>داشبورد</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.updates.index') }}" class="{{ request()->routeIs('admin.updates.*') ? 'active' : '' }}">
                    <i>📦</i>
                    <span>مدیریت آپدیت‌ها</span>
                </a>
            </li>

            <li class="menu-title">مشتریان</li>
            <li>
                <a href="{{ route('admin.customers.index') }}" class="{{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                    <i>👥</i>
                    <span>لیست مشتریان</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.subscriptions.index') }}" class="{{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">
                    <i>💳</i>
                    <span>اشتراک‌ها</span>
                </a>
            </li>

            <li class="menu-title">گزارشات</li>
            <li>
                <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i>📈</i>
                    <span>گزارش‌گیری</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.logs.index') }}" class="{{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                    <i>📋</i>
                    <span>لاگ‌های سیستم</span>
                </a>
            </li>

            <li class="menu-title">تنظیمات</li>
            <li>
                <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i>⚙️</i>
                    <span>تنظیمات</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i>🔐</i>
                    <span>کاربران</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
