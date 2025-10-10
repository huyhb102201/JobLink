<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        // Load tất cả accounts với dữ liệu thật
        $accounts = Account::with(['profile', 'accountType'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Tối ưu hóa queries với cache
        $totalAccountsCount = \Cache::remember('admin_total_accounts', 300, function() {
            return Account::count();
        });
        $activeAccountsCount = \Cache::remember('admin_active_accounts', 300, function() {
            return Account::where('status', 1)->count();
        });
        $lockedAccountsCount = \Cache::remember('admin_locked_accounts', 300, function() {
            return Account::where('status', 0)->count();
        });
        $unverifiedAccountsCount = \Cache::remember('admin_unverified_accounts', 300, function() {
            return Account::whereNull('email_verified_at')->count();
        });
        $accountTypes = \Cache::remember('admin_account_types', 600, function() {
            return AccountType::orderBy('name')->get(['account_type_id', 'name', 'description']);
        });

        return view('admin.accounts.index', compact(
            'accounts',
            'totalAccountsCount',
            'activeAccountsCount',
            'lockedAccountsCount',
            'unverifiedAccountsCount',
            'accountTypes'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:accounts,email',
            'password' => 'required|string|min:8',
            'account_type_id' => 'required|integer|exists:account_types,account_type_id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('error', $validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $account = Account::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'account_type_id' => $request->account_type_id,
                'email_verified_at' => now(),
                'status' => 1,
            ]);

            Profile::create([
                'account_id' => $account->account_id,
                'fullname' => $request->fullname,
                'username' => strtolower(str_replace(' ', '', $request->fullname)) . rand(100, 999),
            ]);

            // Clear cache
            $this->clearAccountCache();

            DB::commit();
            return back()->with('success', 'Tạo tài khoản thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi tạo tài khoản: ' . $e->getMessage());
            return back()->with('error', 'Đã có lỗi xảy ra. Vui lòng thử lại.');
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:accounts,email,' . $id . ',account_id',
            'password' => 'nullable|string|min:8',
            'account_type_id' => 'required|integer|exists:account_types,account_type_id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('error', $validator->errors()->first());
        }

        $account = Account::find($id);
        if (!$account) {
            return back()->with('error', 'Không tìm thấy tài khoản.');
        }

        DB::beginTransaction();
        try {
            // Cập nhật thông tin account
            $account->email = $request->email;
            $account->account_type_id = $request->account_type_id;
            
            // Cập nhật password nếu có
            if ($request->filled('password')) {
                $account->password = Hash::make($request->password);
            }
            
            $account->save();

            // Cập nhật fullname trong profile
            if ($account->profile) {
                $account->profile->fullname = $request->fullname;
                $account->profile->save();
            } else {
                // Tạo profile mới nếu chưa có
                Profile::create([
                    'account_id' => $account->account_id,
                    'fullname' => $request->fullname,
                    'username' => strtolower(str_replace(' ', '', $request->fullname)) . rand(100, 999),
                ]);
            }

            $this->clearAccountCache();
            
            DB::commit();
            return back()->with('success', 'Cập nhật tài khoản thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi cập nhật tài khoản: ' . $e->getMessage());
            return back()->with('error', 'Đã có lỗi xảy ra. Vui lòng thử lại.');
        }
    }

    public function destroy($id)
    {
        $account = Account::find($id);
        if (!$account) {
            return back()->with('error', 'Không tìm thấy tài khoản.');
        }

        DB::beginTransaction();
        try {
            // Xóa profile tương ứng trước
            if ($account->profile) {
                $account->profile->delete();
            }
            
            // Sau đó xóa account
            $account->delete();
            
            $this->clearAccountCache();
            
            DB::commit();
            return back()->with('success', 'Đã xóa tài khoản và profile thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi xóa tài khoản: ' . $e->getMessage());
            return back()->with('error', 'Không thể xóa tài khoản này. Có thể tài khoản đang có dữ liệu liên quan.');
        }
    }

    public function destroyMultiple(Request $request)
    {
        $ids = explode(',', $request->input('ids'));
        if (empty($ids)) {
            return back()->with('error', 'Vui lòng chọn ít nhất một tài khoản để xóa.');
        }

        DB::beginTransaction();
        try {
            // Xóa tất cả profiles tương ứng trước
            Profile::whereIn('account_id', $ids)->delete();
            
            // Sau đó xóa các accounts
            Account::whereIn('account_id', $ids)->delete();
            
            $this->clearAccountCache();
            
            DB::commit();
            return back()->with('success', 'Đã xóa các tài khoản và profiles đã chọn thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi xóa nhiều tài khoản: ' . $e->getMessage());
            return back()->with('error', 'Không thể xóa các tài khoản này. Có thể có dữ liệu liên quan.');
        }
    }
    
    public function updateStatusMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|string',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return back()->with('error', 'Yêu cầu không hợp lệ.');
        }

        $ids = explode(',', $request->input('ids'));
        $status = (int) $request->input('status');
        $actionText = $status === 1 ? 'mở khóa' : 'tạm khóa';

        if (empty($ids)) {
             return back()->with('error', 'Vui lòng chọn ít nhất một tài khoản.');
        }

        try {
            $currentAdminId = auth()->id();
            
            $filteredIds = array_filter($ids, function($id) use ($currentAdminId) {
                return $id != $currentAdminId;
            });

            if (count($filteredIds) < count($ids)) {
                 Account::whereIn('account_id', $filteredIds)->update(['status' => $status]);
                 return back()->with('success', "Đã $actionText các tài khoản đã chọn thành công. Tài khoản của bạn đã được bỏ qua.");
            }
            
            Account::whereIn('account_id', $filteredIds)->update(['status' => $status]);

            $this->clearAccountCache();

            return back()->with('success', "Đã $actionText các tài khoản đã chọn thành công.");
        } catch (\Exception $e) {
            Log::error("Lỗi khi $actionText nhiều tài khoản: " . $e->getMessage());
            return back()->with('error', 'Đã có lỗi xảy ra trong quá trình xử lý.');
        }
    }

    /**
     * Clear account-related cache
     */
    private function clearAccountCache()
    {
        \Cache::forget('admin_total_accounts');
        \Cache::forget('admin_active_accounts');
        \Cache::forget('admin_locked_accounts');
        \Cache::forget('admin_unverified_accounts');
        \Cache::forget('admin_account_types');
    }

    // Account Type Management Methods
    public function getAccountTypes()
    {
        try {
            $accountTypes = AccountType::orderBy('account_type_id')->get();
            return response()->json([
                'success' => true,
                'accountTypes' => $accountTypes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải danh sách loại tài khoản'
            ], 500);
        }
    }

    public function storeAccountType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_type_id' => 'required|integer|unique:account_types,account_type_id',
            'name' => 'required|string|max:255|unique:account_types,name',
            'description' => 'nullable|string|max:500'
        ], [
            'account_type_id.required' => 'Vui lòng chọn mã loại tài khoản',
            'account_type_id.unique' => 'Mã loại tài khoản này đã tồn tại',
            'name.required' => 'Vui lòng nhập tên loại tài khoản',
            'name.unique' => 'Tên loại tài khoản này đã tồn tại'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // Tự động tạo code từ tên (uppercase, loại bỏ khoảng trắng, giới hạn 20 ký tự)
            $code = strtoupper(str_replace(' ', '_', substr($request->name, 0, 20)));
            
            // Đảm bảo code là unique
            $originalCode = $code;
            $counter = 1;
            while (AccountType::where('code', $code)->exists()) {
                $code = $originalCode . '_' . $counter;
                $counter++;
            }
            
            $accountType = AccountType::create([
                'account_type_id' => $request->account_type_id,
                'name' => $request->name,
                'code' => $code,
                'description' => $request->description,
                'monthly_fee' => 0.00,
                'connects_per_month' => null,
                'job_post_limit' => null,
                'max_active_contracts' => null,
                'status' => 1,
                'auto_approve_job_posts' => 0
            ]);

            $this->clearAccountCache();

            return response()->json([
                'success' => true,
                'message' => 'Đã thêm loại tài khoản thành công',
                'accountType' => $accountType
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi thêm loại tài khoản: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm loại tài khoản: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyAccountType($id)
    {
        try {
            $accountType = AccountType::find($id);
            if (!$accountType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy loại tài khoản'
                ], 404);
            }

            // Kiểm tra xem có tài khoản nào đang sử dụng loại này không
            $accountsCount = Account::where('account_type_id', $id)->count();
            if ($accountsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Không thể xóa vì có {$accountsCount} tài khoản đang sử dụng loại này"
                ], 422);
            }

            $accountType->delete();
            $this->clearAccountCache();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa loại tài khoản thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa loại tài khoản'
            ], 500);
        }
    }
}