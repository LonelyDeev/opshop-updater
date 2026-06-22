<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::orderByDesc('created_at')->paginate(10);
        return view('back.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('back.customers.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'website_url' => 'required|url',
            'update_code' => 'nullable|string|max:255|unique:customers,update_code',
            'status' => 'required|in:active,inactive',
        ]);

        // اگر کد آپدیت وارد نشده بود، خودکار تولید شود
        if (empty($validatedData['update_code'])) {
            $validatedData['update_code'] = Customer::generateUpdateCode();
        }

        Customer::create($validatedData);

        return redirect()->route('admin.customers.index')
            ->with('success', 'مشتری با موفقیت ایجاد شد.');
    }

    public function edit(Customer $customer)
    {
        return view('back.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('customers', 'email')->ignore($customer)],
            'phone' => 'nullable|string|max:20',
            'website_url' => 'required|url',
            'update_code' => ['nullable', 'string', 'max:255', Rule::unique('customers', 'update_code')->ignore($customer)],
            'status' => 'required|in:active,inactive',
        ]);

        $customer->update($validatedData);

        return redirect()->route('admin.customers.index')
            ->with('success', 'اطلاعات مشتری با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', 'مشتری با موفقیت حذف شد.');
    }
}
