<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str; // <-- THÊM DÒNG NÀY

class AccountController extends Controller
{
    /**
     * Display a listing of the accounts.
     */
    public function index(Request $request)
    {
        $accountTypes = AccountType::all();
        $query = Account::with(['profile', 'accountType']);

        // Lọc theo loại tài khoản nếu có
        $accountTypeId = $request->query('account_type_id');
        if ($accountTypeId) {
            $query->where('account_type_id', $accountTypeId);
        }

        // Lấy tất cả tài khoản phù hợp để DataTables xử lý
        $accounts = $query->get();

        // Tính toán các số liệu thống kê động
        $totalAccountsCount = Account::count();
        $activeAccountsCount = Account::where('status', 1)->count();
        $lockedAccountsCount = $totalAccountsCount - $activeAccountsCount;
        $unverifiedAccountsCount = Account::whereNull('email_verified_at')->count();

        return view('admin.accounts.index', compact(
            'accounts',
            'accountTypes',
            'totalAccountsCount',
            'activeAccountsCount',
            'lockedAccountsCount',
            'unverifiedAccountsCount'
        ));
    }
    
    /**
     * Store a newly created account in storage.
     */
    public function store(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:accounts,email',
            'password' => 'required|string|min:8',
            'account_type_id' => 'required|exists:account_types,account_type_id',
        ]);

        // Tạo tài khoản mới
        $account = Account::create([
            'name' => $request->fullname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'account_type_id' => $request->account_type_id,
            'email_verified_at' => now(), // Mặc định là đã xác thực khi admin tạo
            'status' => 1, // Mặc định là hoạt động
        ]);

        // Tạo profile liên kết với tài khoản
        Profile::create([
            'account_id' => $account->account_id,
            'username'   => Str::slug($request->fullname) . '-' . $account->account_id, // Tạo username duy nhất
            'fullname'   => $request->fullname,
            'email'      => $request->email,
        ]);

        return redirect()->route('admin.accounts.index')->with('success', 'Tạo tài khoản thành công!');
    }

    /**
     * Update the specified account in storage.
     */
    public function update(Request $request, string $id)
    {
        // Xác thực dữ liệu
        $request->validate([
            'account_type_id' => 'required|exists:account_types,account_type_id',
        ]);

        // Tìm tài khoản
        $account = Account::findOrFail($id);

        // Cập nhật loại tài khoản
        $account->account_type_id = $request->account_type_id;
        $account->save();

        return redirect()->route('admin.accounts.index')->with('success', 'Cập nhật tài khoản thành công!');
    }
    
    // Các hàm create(), destroy() có thể được bổ sung sau nếu cần
}