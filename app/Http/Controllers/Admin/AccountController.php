<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountType;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Display a listing of the accounts.
     */
    public function index(Request $request)
    {
        // Lấy tất cả các loại tài khoản để hiển thị trong dropdown lọc
        $accountTypes = AccountType::all();

        // Bắt đầu truy vấn vào bảng accounts với các quan hệ cần thiết
        $query = Account::with(['profile', 'accountType']);

        // --- Bắt đầu phần code được thêm vào ---
        // Lấy giá trị tìm kiếm từ request
        $search = $request->query('search');

        if ($search) {
            $query->where(function ($q) use ($search) {
                // Kiểm tra nếu giá trị tìm kiếm là số, tìm kiếm theo ID
                if (is_numeric($search)) {
                    $q->where('account_id', $search);
                }
                
                // Tìm kiếm theo email
                $q->orWhere('email', 'like', '%' . $search . '%');
                
                // Tìm kiếm theo họ tên trong bảng profiles liên quan
                $q->orWhereHas('profile', function ($profileQuery) use ($search) {
                    $profileQuery->where('fullname', 'like', '%' . $search . '%');
                });
            });
        }
        // --- Kết thúc phần code được thêm vào ---

        // Lấy tham số 'account_type_id' từ URL. Nếu có, thêm điều kiện lọc vào truy vấn.
        $accountTypeId = $request->query('account_type_id');
        if ($accountTypeId) {
            $query->where('account_type_id', $accountTypeId);
        }

        // Thực thi truy vấn, phân trang kết quả và thêm các tham số hiện tại vào URL phân trang
        $accounts = $query->paginate(10)->appends($request->query());

        // Đếm số tài khoản bị khóa để hiển thị trong thống kê
        $lockedAccountsCount = Account::where('status', 0)->count();

        // Truyền dữ liệu đã lọc (hoặc tất cả dữ liệu) vào view
        return view('admin.accounts.index', compact('accounts', 'lockedAccountsCount', 'accountTypes'));
    }

    /**
     * Show the form for creating a new account.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created account in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Update the specified account in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified account from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}