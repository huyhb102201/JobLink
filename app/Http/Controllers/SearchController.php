<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profile;
use App\Models\Account;
use App\Models\Job; // giả sử có model Job

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim($request->get('q'));
        $type = $request->get('type', 'job');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        if ($type === 'job') {
            $data = Job::query()
                ->where('title', 'like', "%$q%")
                ->whereNotIn('status', ['pending', 'cancelled'])
                ->limit(8)
                ->get(['job_id', 'title'])
                ->map(function ($item) {
                    return [
                        'type' => 'job',
                        'id' => $item->job_id,
                        'title' => $item->title,
                    ];
                });

        } else { // account
            $data = Profile::query()
                ->join('accounts', 'profiles.account_id', '=', 'accounts.account_id')
                ->where(function ($query) use ($q) {
                    $query->where('profiles.username', 'like', "%$q%")
                        ->orWhere('profiles.fullname', 'like', "%$q%");
                })
                ->limit(8)
                ->get([
                    'profiles.username',
                    'profiles.fullname',
                    'accounts.avatar_url'
                ])
                ->map(function ($item) {
                    return [
                        'type' => 'account',
                        'username' => $item->username,
                        'fullname' => $item->fullname,
                        'avatar_url' => $item->avatar_url,
                    ];
                });
        }

        return response()->json($data);
    }
}

