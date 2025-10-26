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
use App\Services\AdminLogService;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        // Load tất cả accounts với dữ liệu thật
        $accounts = Account::with(['profile', 'accountType'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Lấy thống kê real-time (không cache để cập nhật ngay lập tức)
        $totalAccountsCount = Account::count();
        $activeAccountsCount = Account::where('status', 1)->count();
        $lockedAccountsCount = Account::where('status', 0)->count();
        $unverifiedAccountsCount = Account::whereNull('email_verified_at')->count();
        $accountTypes = AccountType::orderBy('name')->get(['account_type_id', 'name', 'description']);

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

            // Log admin action
            AdminLogService::logCreate(
                'Account',
                $account->account_id,
                "Tạo tài khoản mới: {$request->email}",
                ['email' => $request->email, 'fullname' => $request->fullname]
            );

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

            // Log admin action
            AdminLogService::logUpdate(
                'Account',
                $account->account_id,
                "Cập nhật tài khoản: {$account->email}"
            );
            
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
            // Log before delete
            AdminLogService::logDelete(
                'Account',
                $account->account_id,
                "Xóa tài khoản: {$account->email}",
                ['email' => $account->email]
            );

            // Xóa profile tương ứng trước
            if ($account->profile) {
                $account->profile->delete();
            }
            
            // Sau đó xóa account
            $account->delete();
            
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
        $idsInput = $request->input('ids');
        $ids = [];

        if (is_array($idsInput)) {
            $ids = $idsInput;
        } elseif (is_string($idsInput)) {
            $decoded = json_decode($idsInput, true);
            if (is_array($decoded)) {
                $ids = $decoded;
            } else {
                $ids = explode(',', $idsInput);
            }
        }

        $ids = array_values(array_unique(array_filter(array_map(function ($id) {
            if (is_numeric($id)) {
                return (int) $id;
            }
            return null;
        }, $ids))));

        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn ít nhất một tài khoản để xóa.'
                ], 422);
            }
            return back()->with('error', 'Vui lòng chọn ít nhất một tài khoản để xóa.');
        }

        DB::beginTransaction();
        try {
            $accounts = Account::whereIn('account_id', $ids)->get(['account_id', 'status']);

            if ($accounts->isEmpty()) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy tài khoản hợp lệ để xóa.'
                    ], 404);
                }
                return back()->with('error', 'Không tìm thấy tài khoản hợp lệ để xóa.');
            }

            $activeDeleted = $accounts->where('status', 1)->count();
            $lockedDeleted = $accounts->where('status', 0)->count();

            AdminLogService::logBulk('delete', 'Account', $ids, 'Xóa hàng loạt ' . count($ids) . ' tài khoản');

            Profile::whereIn('account_id', $ids)->delete();
            Account::whereIn('account_id', $ids)->delete();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã xóa các tài khoản và profiles đã chọn thành công.',
                    'removed_ids' => $ids,
                    'stats' => [
                        'removed_total' => count($ids),
                        'removed_active' => $activeDeleted,
                        'removed_locked' => $lockedDeleted,
                    ],
                ]);
            }

            return back()->with('success', 'Đã xóa các tài khoản và profiles đã chọn thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi xóa nhiều tài khoản: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa các tài khoản này. Có thể có dữ liệu liên quan.'
                ], 500);
            }

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
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Yêu cầu không hợp lệ.'
                ], 422);
            }
            return back()->with('error', 'Yêu cầu không hợp lệ.');
        }

        $ids = explode(',', $request->input('ids'));
        $status = (int) $request->input('status');
        $actionText = $status === 1 ? 'mở khóa' : 'tạm khóa';

        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn ít nhất một tài khoản.'
                ], 422);
            }
            return back()->with('error', 'Vui lòng chọn ít nhất một tài khoản.');
        }

        try {
            $currentAdminId = auth()->id();
            
            $filteredIds = array_filter($ids, function($id) use ($currentAdminId) {
                return $id != $currentAdminId;
            });

            $skippedSelf = count($filteredIds) < count($ids);
            
            Account::whereIn('account_id', $filteredIds)->update(['status' => $status]);

            // Log bulk status update
            AdminLogService::logBulk(
                $status === 1 ? 'unlock' : 'lock',
                'Account',
                $filteredIds,
                "Cập nhật trạng thái hàng loạt: $actionText " . count($filteredIds) . " tài khoản"
            );

            $message = "Đã $actionText các tài khoản đã chọn thành công.";
            if ($skippedSelf) {
                $message .= " Tài khoản của bạn đã được bỏ qua.";
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'updated_ids' => $filteredIds
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error("Lỗi khi $actionText nhiều tài khoản: " . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đã có lỗi xảy ra trong quá trình xử lý.'
                ], 500);
            }
            
            return back()->with('error', 'Đã có lỗi xảy ra trong quá trình xử lý.');
        }
    }

    public function updateStatusAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Yêu cầu không hợp lệ.'
            ], 422);
        }

        $status = (int) $request->input('status');
        $actionText = $status === 1 ? 'mở khóa' : 'khóa';

        try {
            $currentAdminId = auth()->id();
            
            // Lấy tất cả tài khoản có trạng thái ngược lại (để cập nhật)
            $targetStatus = $status === 1 ? 0 : 1; // Nếu mở khóa thì lấy tài khoản bị khóa (status=0)
            
            // Cập nhật tất cả tài khoản trừ tài khoản admin hiện tại
            $updatedCount = Account::where('status', $targetStatus)
                ->where('account_id', '!=', $currentAdminId)
                ->update(['status' => $status]);

            if ($updatedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => $status === 1 
                        ? 'Không có tài khoản nào bị khóa để mở khóa.' 
                        : 'Không có tài khoản nào đang hoạt động để khóa.'
                ], 422);
            }

            // Log bulk status update
            AdminLogService::logAction(
                $status === 1 ? 'unlock_all' : 'lock_all',
                'Account',
                null,
                "Cập nhật trạng thái tất cả: $actionText $updatedCount tài khoản"
            );

            return response()->json([
                'success' => true,
                'message' => "Đã $actionText thành công $updatedCount tài khoản.",
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            Log::error("Lỗi khi $actionText tất cả tài khoản: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra trong quá trình xử lý.'
            ], 500);
        }
    }


    private function getNextAvailableAccountTypeId(): int
    {
        $existingIds = AccountType::orderBy('account_type_id')->pluck('account_type_id');
        $nextId = 1;

        foreach ($existingIds as $id) {
            if ($id == $nextId) {
                $nextId++;
            } elseif ($id > $nextId) {
                break;
            }
        }

        return $nextId;
    }

    // Account Type Management Methods
    public function getAccountTypes()
    {
        try {
            $accountTypes = AccountType::orderBy('account_type_id')->get();
            return response()->json([
                'success' => true,
                'accountTypes' => $accountTypes,
                'next_available_id' => $this->getNextAvailableAccountTypeId()
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
            'name' => 'required|string|max:255|unique:account_types,name',
            'description' => 'nullable|string|max:500'
        ], [
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
            // Tự động tạo account_type_id duy nhất
            $accountTypeId = $this->getNextAvailableAccountTypeId();

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
                'account_type_id' => $accountTypeId,
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

            // Log admin action
            AdminLogService::logCreate(
                'AccountType',
                $accountType->account_type_id,
                "Tạo loại tài khoản mới: {$accountType->name}",
                ['name' => $accountType->name, 'code' => $accountType->code]
            );

            return response()->json([
                'success' => true,
                'message' => 'Đã thêm loại tài khoản thành công',
                'accountType' => $accountType,
                'next_available_id' => $this->getNextAvailableAccountTypeId()
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

            // Log before delete
            AdminLogService::logDelete(
                'AccountType',
                $accountType->account_type_id,
                "Xóa loại tài khoản: {$accountType->name}",
                ['name' => $accountType->name, 'code' => $accountType->code]
            );

            $accountType->delete();

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

    public function updateAccountType(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:account_types,name,' . $id . ',account_type_id',
            'description' => 'nullable|string|max:500'
        ], [
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
            $accountType = AccountType::where('account_type_id', $id)->firstOrFail();
            
            $accountType->update([
                'name' => $request->name,
                'description' => $request->description
            ]);

            // Log admin action
            AdminLogService::logUpdate(
                'AccountType',
                $accountType->account_type_id,
                "Cập nhật loại tài khoản: {$accountType->name}",
                ['name' => $accountType->name, 'description' => $accountType->description]
            );

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật loại tài khoản thành công',
                'accountType' => $accountType
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật loại tài khoản: ' . $e->getMessage()
            ], 500);
        }
    }
}