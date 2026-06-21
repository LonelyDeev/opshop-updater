<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * نمایش لیست کاربران ادمین
     */
    public function index()
    {
        // دریافت کاربران از دیتابیس
        $users = []; // بعداً از مدل User گرفته می‌شود

        return view('back.users', compact('users'));
    }

    /**
     * فرم ایجاد کاربر جدید
     */
    public function create()
    {
        return view('back.users-create');
    }

    /**
     * ذخیره کاربر جدید
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:super_admin,admin,operator',
        ]);

        // کد ذخیره‌سازی در دیتابیس اینجا قرار می‌گیرد
        // $user = User::create([...]);

        return redirect()->route('back.users.index')
            ->with('success', 'کاربر با موفقیت ایجاد شد.');
    }

    /**
     * نمایش جزئیات یک کاربر
     */
    public function show($id)
    {
        // دریافت کاربر از دیتابیس
        return view('back.users-show', compact('id'));
    }

    /**
     * فرم ویرایش کاربر
     */
    public function edit($id)
    {
        // دریافت کاربر از دیتابیس
        return view('back.users-edit', compact('id'));
    }

    /**
     * به‌روزرسانی کاربر
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:super_admin,admin,operator',
            'status' => 'required|in:active,inactive',
        ]);

        // اگر پسورد وارد شده بود، هش شود
        // اگر نه، فقط فیلدهای دیگر آپدیت شوند

        // کد به‌روزرسانی در دیتابیس اینجا قرار می‌گیرد

        return redirect()->route('back.users.index')
            ->with('success', 'کاربر با موفقیت به‌روزرسانی شد.');
    }

    /**
     * حذف کاربر
     */
    public function destroy($id)
    {
        // جلوگیری از حذف خودتان
        if ($id == auth()->id()) {
            return redirect()->route('back.users.index')
                ->with('error', 'نمی‌توانید حساب خودتان را حذف کنید.');
        }

        // حذف از دیتابیس

        return redirect()->route('back.users.index')
            ->with('success', 'کاربر با موفقیت حذف شد.');
    }

    /**
     * تغییر وضعیت کاربر
     */
    public function toggleStatus($id)
    {
        // دریافت کاربر و تغییر وضعیت active/inactive

        return redirect()->route('back.users.index')
            ->with('success', 'وضعیت کاربر تغییر کرد.');
    }
}
