<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::orderByDesc('created_at');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%");
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->paginate(15);
        return view('back.users.index', compact('users'));
    }

    public function create()
    {
        return view('back.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,operator',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.users.index')->with('success', 'کاربر جدید با موفقیت ایجاد شد.');
    }

    public function edit(User $user)
    {
        return view('back.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:admin,operator',
            'status' => 'required|in:active,inactive',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'اطلاعات کاربر با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(User $user)
    {
        // جلوگیری از حذف خودتان
        if ($user->id === auth()->id()) {
            return back()->with('error', 'شما نمی‌توانید حساب کاربری خودتان را حذف کنید.');
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'کاربر با موفقیت حذف شد.');
    }

    // تغییر سریع وضعیت (فعال/غیرفعال)
    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'شما نمی‌توانید وضعیت حساب کاربری خودتان را تغییر دهید.');
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        $message = $newStatus === 'active' ? 'کاربر فعال شد.' : 'کاربر غیرفعال شد.';

        return back()->with('success', $message);
    }
}
