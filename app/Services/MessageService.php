<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Job;
use App\Models\Account;
use App\Models\BoxChat;
use App\Models\Org;
use App\Models\OrgMember;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Events\MessageSent;
use Illuminate\Http\Request;

class MessageService
{
    /**
     * Lấy danh sách tất cả các cuộc trò chuyện của người dùng.
     *
     * @param int $userId
     * @return \Illuminate\Support\Collection
     */
    public function getAllConversations($userId)
    {
        $conversations = BoxChat::with([
            'messages' => function ($q) {
                $q->latest();
            },
            'messages.sender'
        ])
            ->where(function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    $q2->where('type', 1)
                        ->where(function ($q3) use ($userId) {
                            $q3->where('sender_id', $userId)
                                ->orWhere('receiver_id', $userId);
                        });
                })->orWhere(function ($q2) use ($userId) {
                    $q2->where('type', 2)
                        ->whereExists(function ($q3) use ($userId) {
                            $q3->select(DB::raw(1))
                                ->from('jobs')
                                ->whereColumn('jobs.job_id', 'box_chat.job_id')
                                ->where('jobs.status', 'in_progress')
                                ->where(function ($q4) use ($userId) {
                                    $q4->where('jobs.account_id', $userId)
                                        ->orWhereRaw('find_in_set(?, jobs.apply_id)', [$userId]);
                                });
                        });
                })->orWhere(function ($q2) use ($userId) {
                    $q2->where('type', 3)
                        ->whereExists(function ($q3) use ($userId) {
                            $q3->select(DB::raw(1))
                                ->from('org_members')
                                ->whereColumn('org_members.org_id', 'box_chat.org_id')
                                ->where('org_members.account_id', $userId)
                                ->where('org_members.status', 'ACTIVE');
                        });
                });
            })
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($box) {
                if ($box->messages->isNotEmpty()) {
                    $latestMsg = $box->messages->first();
                    try {
                        if ($latestMsg->content) {
                            $latestMsg->content = Crypt::decryptString($latestMsg->content);
                        }
                    } catch (\Exception $e) {
                        Log::error('Không thể giải mã nội dung tin nhắn mới nhất trong sidebar', [
                            'message_id' => $latestMsg->id,
                            'error' => $e->getMessage(),
                        ]);
                        $latestMsg->content = '[Không thể giải mã tin nhắn]';
                    }
                    $box->messages = collect([$latestMsg]);
                }
                return $box;
            });

        return $conversations;
    }

    /**
     * Lấy danh sách tin nhắn trong một box chat.
     *
     * @param int $boxId
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBoxMessages($boxId, $userId)
    {
        $box = BoxChat::where('id', $boxId)
            ->where(function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    $q2->where('type', 1)
                        ->where(function ($q3) use ($userId) {
                            $q3->where('sender_id', $userId)
                                ->orWhere('receiver_id', $userId);
                        });
                })->orWhere(function ($q2) use ($userId) {
                    $q2->where('type', 2)
                        ->whereExists(function ($q3) use ($userId) {
                            $q3->select(DB::raw(1))
                                ->from('jobs')
                                ->whereColumn('jobs.job_id', 'box_chat.job_id')
                                ->where('jobs.status', 'in_progress')
                                ->where(function ($q4) use ($userId) {
                                    $q4->where('jobs.account_id', $userId)
                                        ->orWhereRaw('find_in_set(?, jobs.apply_id)', [$userId]);
                                });
                        });
                })->orWhere(function ($q2) use ($userId) {
                    $q2->where('type', 3)
                        ->whereExists(function ($q3) use ($userId) {
                            $q3->select(DB::raw(1))
                                ->from('org_members')
                                ->whereColumn('org_members.org_id', 'box_chat.org_id')
                                ->where('org_members.account_id', $userId)
                                ->where('org_members.status', 'ACTIVE');
                        });
                });
            })
            ->first();

        if (!$box) {
            return response()->json(['error' => 'Không tìm thấy hoặc không có quyền truy cập box chat'], 404);
        }

        $messages = Message::with('sender')
            ->where('box_id', $boxId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                try {
                    if ($message->content) {
                        $message->content = Crypt::decryptString($message->content);
                    }
                } catch (\Exception $e) {
                    Log::error('Không thể giải mã nội dung tin nhắn', [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                    $message->content = '[Không thể giải mã tin nhắn]';
                }
                return $message;
            });

        return response()->json($messages->map(function ($message) {
            return [
                'id' => $message->id,
                'content' => $message->content,
                'img' => $message->img ? asset($message->img) : null,
                'sender_id' => $message->sender_id,
                'sender' => [
                    'name' => $message->sender->name,
                    'avatar_url' => $message->sender->avatar_url ?? asset('assets/img/defaultavatar.jpg'),
                ],
                'job_id' => $message->job_id,
                'org_id' => $message->org_id,
                'conversation_id' => $message->conversation_id,
                'created_at' => $message->created_at->toISOString(),
            ];
        }));
    }

    /**
     * Gửi tin nhắn.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'job_id' => ['nullable', 'exists:jobs,job_id'],
                'org_id' => ['nullable', 'exists:orgs,org_id'],
                'content' => ['nullable', 'string', 'max:5000'],
                'receiver_id' => ['nullable', 'exists:accounts,account_id'],
                'img' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            ]);

            // Kiểm tra ít nhất content hoặc img
            if (!$request->filled('content') && !$request->hasFile('img')) {
                return response()->json(['error' => 'Phải cung cấp ít nhất nội dung tin nhắn hoặc hình ảnh.'], 422);
            }

            $senderId = Auth::id();
            $receiverId = $request->input('receiver_id');
            $jobId = $request->input('job_id');
            $orgId = $request->input('org_id');
            $content = $request->input('content');

            // Log request
            Log::info('Yêu cầu gửi tin nhắn', [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'job_id' => $jobId,
                'org_id' => $orgId,
                'content_preview' => substr($content ?? '', 0, 50),
                'has_img' => $request->hasFile('img'),
                'img_name' => $request->hasFile('img') ? $request->file('img')->getClientOriginalName() : null,
            ]);

            $encryptedContent = $content ? Crypt::encryptString($content) : null;

            // Xử lý ảnh
            $imgPath = null;
            if ($request->hasFile('img')) {
                $file = $request->file('img');
                if ($file->isValid()) {
                    $directory = public_path('img/messages');
                    if (!File::exists($directory)) {
                        File::makeDirectory($directory, 0755, true);
                        Log::info('Tạo thư mục cho ảnh tin nhắn', ['directory' => $directory]);
                    }

                    $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                    $fullPath = $directory . '/' . $filename;

                    try {
                        $file->move($directory, $filename);
                        $imgPath = 'img/messages/' . $filename;

                        if (File::exists($fullPath)) {
                            Log::info('Ảnh đã được lưu và xác minh', [
                                'path' => $imgPath,
                                'full_path' => $fullPath,
                                'size' => File::size($fullPath),
                            ]);
                        } else {
                            Log::error('Ảnh đã lưu nhưng không tìm thấy trên đĩa', ['full_path' => $fullPath]);
                            return response()->json(['error' => 'Lưu ảnh thất bại: File không tồn tại sau khi lưu.'], 500);
                        }
                    } catch (\Exception $e) {
                        Log::error('Lưu ảnh thất bại', [
                            'error' => $e->getMessage(),
                            'filename' => $filename,
                            'tmp_path' => $file->getPathname(),
                        ]);
                        return response()->json(['error' => 'Lỗi khi lưu hình ảnh: ' . $e->getMessage()], 500);
                    }
                } else {
                    Log::error('File ảnh không hợp lệ', ['tmp_path' => $file->getPathname()]);
                    return response()->json(['error' => 'File ảnh không hợp lệ.'], 422);
                }
            }

            if ($receiverId) {
                // Chat 1-1
                $receiver = Account::find($receiverId);
                if (!$receiver) {
                    Log::warning('Không tìm thấy người nhận', ['receiver_id' => $receiverId]);
                    return response()->json(['error' => 'Người nhận không tồn tại'], 404);
                }

                $box = BoxChat::firstOrCreate(
                    [
                        'sender_id' => min($senderId, $receiverId),
                        'receiver_id' => max($senderId, $receiverId),
                    ],
                    [
                        'name' => "Chat: {$senderId}-{$receiverId}",
                        'type' => 1,
                    ]
                );

                if ($jobId) {
                    $job = Job::find($jobId);
                    if (!$job) {
                        Log::warning('Không tìm thấy công việc', ['job_id' => $jobId]);
                        return response()->json(['error' => 'Công việc không tồn tại'], 404);
                    }
                    if ($job->account_id != $senderId && $job->account_id != $receiverId) {
                        Log::warning('Truy cập công việc không được phép', [
                            'job_id' => $jobId,
                            'sender_id' => $senderId,
                            'receiver_id' => $receiverId,
                        ]);
                        return response()->json(['error' => 'Bạn không có quyền gửi tin nhắn cho công việc này'], 403);
                    }
                }

                $message = Message::create([
                    'conversation_id' => $receiverId,
                    'sender_id' => $senderId,
                    'job_id' => $jobId,
                    'content' => $encryptedContent,
                    'img' => $imgPath,
                    'type' => 1,
                    'status' => 1,
                    'box_id' => $box->id,
                ]);
            } elseif ($jobId) {
                // Chat nhóm công việc
                $job = Job::find($jobId);
                if (!$job) {
                    Log::warning('Không tìm thấy công việc', ['job_id' => $jobId]);
                    return response()->json(['error' => 'Công việc không tồn tại'], 404);
                }
                if ($job->status !== 'in_progress') {
                    Log::warning('Công việc không ở trạng thái đang tiến hành', ['job_id' => $jobId]);
                    return response()->json(['error' => 'Công việc không ở trạng thái đang tiến hành'], 403);
                }

                $members = array_filter(explode(',', $job->apply_id) ?? []);
                $members[] = $job->account_id;
                if (!in_array($senderId, $members)) {
                    Log::warning('Truy cập nhóm không được phép', ['sender_id' => $senderId, 'job_id' => $jobId]);
                    return response()->json(['error' => 'Bạn không có quyền gửi tin nhắn trong nhóm này'], 403);
                }

                $box = BoxChat::firstOrCreate(
                    [
                        'job_id' => $jobId,
                        'type' => 2,
                    ],
                    [
                        'name' => "Chat nhóm {$jobId}",
                    ]
                );

                $message = Message::create([
                    'conversation_id' => 0,
                    'sender_id' => $senderId,
                    'job_id' => $jobId,
                    'content' => $encryptedContent,
                    'img' => $imgPath,
                    'type' => 1,
                    'status' => 1,
                    'box_id' => $box->id,
                ]);
            } elseif ($orgId) {
                // Chat nhóm tổ chức
                $org = Org::find($orgId);
                if (!$org) {
                    Log::warning('Không tìm thấy tổ chức', ['org_id' => $orgId]);
                    return response()->json(['error' => 'Tổ chức không tồn tại'], 404);
                }

                $member = OrgMember::where('org_id', $orgId)
                    ->where('account_id', $senderId)
                    ->where('status', 'ACTIVE')
                    ->first();
                if (!$member) {
                    Log::warning('Truy cập tổ chức không được phép', ['sender_id' => $senderId, 'org_id' => $orgId]);
                    return response()->json(['error' => 'Bạn không có quyền gửi tin nhắn trong nhóm tổ chức này'], 403);
                }

                $box = BoxChat::firstOrCreate(
                    [
                        'org_id' => $orgId,
                        'type' => 3,
                    ],
                    [
                        'name' => "Chat nhóm tổ chức {$orgId}",
                    ]
                );

                $message = Message::create([
                    'conversation_id' => 0,
                    'sender_id' => $senderId,
                    'org_id' => $orgId,
                    'content' => $encryptedContent,
                    'img' => $imgPath,
                    'type' => 1,
                    'status' => 1,
                    'box_id' => $box->id,
                ]);
            } else {
                return response()->json(['error' => 'Phải cung cấp receiver_id, job_id hoặc org_id'], 422);
            }

            // Cập nhật updated_at của BoxChat
            if ($message->box_id) {
                $boxToUpdate = BoxChat::find($message->box_id);
                if ($boxToUpdate) {
                    $boxToUpdate->touch();
                    Log::info('Cập nhật updated_at của box_chat sau khi gửi tin nhắn', [
                        'box_id' => $boxToUpdate->id,
                        'new_updated_at' => $boxToUpdate->updated_at,
                    ]);
                }
            }

            $message->load('sender');

            if (!$message->sender) {
                Log::error('Không tìm thấy người gửi cho tin nhắn', [
                    'message_id' => $message->id,
                    'sender_id' => $senderId,
                ]);
                return response()->json(['error' => 'Người gửi không tồn tại'], 500);
            }

            // Broadcast tin nhắn
            try {
                broadcast(new MessageSent($message))->toOthers();
                Log::info('Tin nhắn được broadcast thành công', ['message_id' => $message->id]);
            } catch (\Exception $e) {
                Log::error('Broadcast thất bại', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'id' => $message->id,
                'content' => $content,
                'img' => $message->img ? asset($message->img) : null,
                'sender_id' => $message->sender_id,
                'sender' => [
                    'name' => $message->sender->name,
                    'avatar_url' => $message->sender->avatar_url ?? asset('assets/img/defaultavatar.jpg'),
                ],
                'job_id' => $message->job_id,
                'org_id' => $message->org_id,
                'conversation_id' => $message->conversation_id,
                'created_at' => $message->created_at->toISOString(),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Lỗi validate khi gửi tin nhắn', [
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);
            return response()->json(['error' => 'Dữ liệu không hợp lệ', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Gửi tin nhắn thất bại', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return response()->json(['error' => 'Lỗi khi gửi tin nhắn: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Lấy danh sách tin nhắn giữa hai người dùng.
     *
     * @param int $partnerId
     * @param int|null $jobId
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages($partnerId, $jobId, $userId)
    {
        $query = Message::with('sender')
            ->where(function ($q) use ($userId, $partnerId) {
                $q->where(function ($q2) use ($userId, $partnerId) {
                    $q2->where('sender_id', $userId)
                        ->where('conversation_id', $partnerId);
                })->orWhere(function ($q2) use ($userId, $partnerId) {
                    $q2->where('sender_id', $partnerId)
                        ->where('conversation_id', $userId);
                });
            });

        if ($jobId) {
            $query->where('job_id', $jobId);
        } else {
            $query->whereNull('job_id');
        }

        $messages = $query->orderBy('created_at', 'asc')->get()->map(function ($message) {
            try {
                if ($message->content) {
                    $message->content = Crypt::decryptString($message->content);
                }
            } catch (\Exception $e) {
                Log::error('Không thể giải mã nội dung tin nhắn', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
                $message->content = '[Không thể giải mã tin nhắn]';
            }
            return $message;
        });

        return response()->json($messages->map(function ($message) {
            return [
                'id' => $message->id,
                'content' => $message->content,
                'img' => $message->img ? asset($message->img) : null,
                'sender_id' => $message->sender_id,
                'sender' => [
                    'name' => $message->sender->name,
                    'avatar_url' => $message->sender->avatar_url ?? asset('assets/img/defaultavatar.jpg'),
                ],
                'job_id' => $message->job_id,
                'conversation_id' => $message->conversation_id,
                'created_at' => $message->created_at->toISOString(),
            ];
        }));
    }

    /**
     * Lấy danh sách cuộc trò chuyện cho danh sách chat.
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatList($userId)
    {
        $conversations = $this->getAllConversations($userId)->map(function ($box) use ($userId) {
            if ($box->type == 1) {
                $partnerId = $box->sender_id == $userId ? $box->receiver_id : $box->sender_id;
                $partner = Account::find($partnerId);
                $latestMsg = $box->messages->first();

                if ($latestMsg) {
                    try {
                        if ($latestMsg->content) {
                            $latestMsg->content = Crypt::decryptString($latestMsg->content);
                        }
                    } catch (\Exception $e) {
                        Log::error('Không thể giải mã nội dung tin nhắn mới nhất trong danh sách chat', [
                            'message_id' => $latestMsg->id,
                            'error' => $e->getMessage(),
                        ]);
                        $latestMsg->content = '[Không thể giải mã tin nhắn]';
                    }
                }

                return [
                    'box_id' => $box->id,
                    'partner_id' => $partnerId,
                    'job_id' => null,
                    'org_id' => null,
                    'partner_name' => $partner ? $partner->name : 'Unknown',
                    'avatar' => $partner ? ($partner->avatar_url ?: asset('assets/img/defaultavatar.jpg')) : asset('assets/img/defaultavatar.jpg'),
                    'latest_msg' => $latestMsg ? [
                        'sender_id' => $latestMsg->sender_id,
                        'content' => \Illuminate\Support\Str::limit($latestMsg->content ?? '', 25),
                        'img' => $latestMsg->img ? asset($latestMsg->img) : null,
                        'created_at' => $latestMsg->created_at->diffForHumans(),
                        'sender_name' => $latestMsg->sender->name,
                    ] : null,
                ];
            } elseif ($box->type == 2) {
                $latestMsg = $box->messages->first();

                if ($latestMsg) {
                    try {
                        if ($latestMsg->content) {
                            $latestMsg->content = Crypt::decryptString($latestMsg->content);
                        }
                    } catch (\Exception $e) {
                        Log::error('Không thể giải mã nội dung tin nhắn mới nhất trong danh sách chat', [
                            'message_id' => $latestMsg->id,
                            'error' => $e->getMessage(),
                        ]);
                        $latestMsg->content = '[Không thể giải mã tin nhắn]';
                    }
                }

                return [
                    'box_id' => $box->id,
                    'partner_id' => null,
                    'job_id' => $box->job_id,
                    'org_id' => null,
                    'partner_name' => $box->name,
                    'avatar' => asset('assets/img/group-icon.png'),
                    'latest_msg' => $latestMsg ? [
                        'sender_id' => $latestMsg->sender_id,
                        'content' => \Illuminate\Support\Str::limit($latestMsg->content ?? '', 25),
                        'img' => $latestMsg->img ? asset($latestMsg->img) : null,
                        'created_at' => $latestMsg->created_at->diffForHumans(),
                        'sender_name' => $latestMsg->sender->name,
                    ] : null,
                ];
            } else {
                // Type 3: Nhóm tổ chức
                $latestMsg = $box->messages->first();

                if ($latestMsg) {
                    try {
                        if ($latestMsg->content) {
                            $latestMsg->content = Crypt::decryptString($latestMsg->content);
                        }
                    } catch (\Exception $e) {
                        Log::error('Không thể giải mã nội dung tin nhắn mới nhất trong danh sách chat', [
                            'message_id' => $latestMsg->id,
                            'error' => $e->getMessage(),
                        ]);
                        $latestMsg->content = '[Không thể giải mã tin nhắn]';
                    }
                }

                return [
                    'box_id' => $box->id,
                    'partner_id' => null,
                    'job_id' => null,
                    'org_id' => $box->org_id,
                    'partner_name' => $box->name,
                    'avatar' => asset('assets/img/org-icon.png'),
                    'latest_msg' => $latestMsg ? [
                        'sender_id' => $latestMsg->sender_id,
                        'content' => \Illuminate\Support\Str::limit($latestMsg->content ?? '', 25),
                        'img' => $latestMsg->img ? asset($latestMsg->img) : null,
                        'created_at' => $latestMsg->created_at->diffForHumans(),
                        'sender_name' => $latestMsg->sender->name,
                    ] : null,
                ];
            }
        });

        return response()->json($conversations);
    }

    /**
     * Lấy username từ account_id.
     *
     * @param int $accountId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsername($accountId)
    {
        $profile = Profile::where('account_id', $accountId)->first();
        if (!$profile) {
            return response()->json(['error' => 'Người dùng không tồn tại'], 404);
        }
        return response()->json(['username' => $profile->username]);
    }

    /**
     * Lấy số lượng tin nhắn chưa đọc.
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnreadCount($userId)
    {
        $unreadCount = Message::where('status', 1)
            ->where('sender_id', '!=', $userId)
            ->whereDoesntHave('readBy', function ($query) use ($userId) {
                $query->where('account_id', $userId);
            })
            ->count();
        return response()->json(['unread_count' => $unreadCount]);
    }

    /**
     * Lấy thông tin box chat cho một công việc.
     *
     * @param int $jobId
     * @param int $userId
     * @return array
     */
    public function getChatForJob($jobId, $userId)
    {
        $job = Job::findOrFail($jobId);
        $partnerId = $job->account_id;

        $box = BoxChat::firstOrCreate(
            [
                'sender_id' => min($userId, $partnerId),
                'receiver_id' => max($userId, $partnerId),
            ],
            [
                'name' => "Chat: {$userId}-{$partnerId}",
                'type' => 1,
            ]
        );

        $messages = $box->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                try {
                    if ($message->content) {
                        $message->content = Crypt::decryptString($message->content);
                    }
                } catch (\Exception $e) {
                    Log::error('Không thể giải mã nội dung tin nhắn', [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                    $message->content = '[Không thể giải mã tin nhắn]';
                }
                return $message;
            });

        return [
            'job' => $job,
            'messages' => $messages,
            'receiverId' => $partnerId,
            'box' => $box,
            'conversations' => $this->getAllConversations($userId),
        ];
    }

    /**
     * Lấy thông tin box chat cho một người dùng.
     *
     * @param string $username
     * @param int $userId
     * @return array
     */
    public function getChatWithUser($username, $userId)
    {
        $profile = Profile::where('username', $username)->first();
        if (!$profile) {
            abort(404, 'Người dùng không tồn tại');
        }

        $partnerId = $profile->account_id;
        if ($partnerId == $userId) {
            abort(403, 'Bạn không thể chat với chính mình');
        }

        $box = BoxChat::firstOrCreate(
            [
                'sender_id' => min($userId, $partnerId),
                'receiver_id' => max($userId, $partnerId),
                'type' => 1,
            ],
            [
                'name' => "Chat: {$userId}-{$partnerId}",
            ]
        );

        $messages = $box->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                try {
                    if ($message->content) {
                        $message->content = Crypt::decryptString($message->content);
                    }
                } catch (\Exception $e) {
                    Log::error('Không thể giải mã nội dung tin nhắn', [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                    $message->content = '[Không thể giải mã tin nhắn]';
                }
                return $message;
            });

        $partner = Account::findOrFail($partnerId);

        return [
            'job' => null,
            'org' => null,
            'messages' => $messages,
            'receiverId' => $partnerId,
            'box' => $box,
            'conversations' => $this->getAllConversations($userId),
            'employer' => $partner,
        ];
    }

    /**
     * Lấy thông tin box chat nhóm cho một công việc.
     *
     * @param int $jobId
     * @param int $userId
     * @return array
     */
    public function getChatGroup($jobId, $userId)
    {
        $job = Job::findOrFail($jobId);

        $members = explode(',', $job->apply_id) ?? [];
        $members[] = $job->account_id;
        $members = array_filter($members);
        if (!in_array($userId, $members)) {
            abort(403, 'Bạn không có quyền truy cập chat nhóm này');
        }

        $box = BoxChat::firstOrCreate(
            [
                'job_id' => $jobId,
                'type' => 2,
            ],
            [
                'name' => "Chat nhóm {$jobId}",
            ]
        );

        $messages = $box->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                try {
                    if ($message->content) {
                        $message->content = Crypt::decryptString($message->content);
                    }
                } catch (\Exception $e) {
                    Log::error('Không thể giải mã nội dung tin nhắn', [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                    $message->content = '[Không thể giải mã tin nhắn]';
                }
                return $message;
            });

        return [
            'job' => $job,
            'messages' => $messages,
            'receiverId' => null,
            'box' => $box,
            'conversations' => $this->getAllConversations($userId),
        ];
    }

    /**
     * Lấy thông tin box chat nhóm cho một tổ chức.
     *
     * @param int $orgId
     * @param int $userId
     * @return array
     */
    public function getChatOrg($orgId, $userId)
    {
        $org = Org::findOrFail($orgId);

        $member = OrgMember::where('org_id', $orgId)
            ->where('account_id', $userId)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$member) {
            abort(403, 'Bạn không có quyền truy cập chat nhóm này');
        }

        $box = BoxChat::firstOrCreate(
            [
                'org_id' => $orgId,
                'type' => 3,
            ],
            [
                'name' => "Chat nhóm tổ chức {$orgId}",
            ]
        );

        $messages = $box->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                try {
                    if ($message->content) {
                        $message->content = Crypt::decryptString($message->content);
                    }
                } catch (\Exception $e) {
                    Log::error('Không thể giải mã nội dung tin nhắn', [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                    $message->content = '[Không thể giải mã tin nhắn]';
                }
                return $message;
            });

        return [
            'job' => null,
            'org' => $org,
            'messages' => $messages,
            'receiverId' => null,
            'box' => $box,
            'conversations' => $this->getAllConversations($userId),
        ];
    }

    /**
     * Lấy thông tin box chat giữa chủ job và freelancer.
     *
     * @param int $jobId
     * @param int $freelancerId
     * @param int $userId
     * @return array
     */
    public function getChatWithFreelancer($jobId, $freelancerId, $userId)
    {
        $job = Job::findOrFail($jobId);

        if ($userId != $job->account_id) {
            abort(403, 'Bạn không phải chủ job');
        }

        $messages = Message::with('sender')
            ->where('job_id', $jobId)
            ->where(function ($q) use ($userId, $freelancerId) {
                $q->where(function ($q2) use ($userId, $freelancerId) {
                    $q2->where('sender_id', $userId)
                        ->where('conversation_id', $freelancerId);
                })->orWhere(function ($q2) use ($userId, $freelancerId) {
                    $q2->where('sender_id', $freelancerId)
                        ->where('conversation_id', $userId);
                });
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                try {
                    if ($message->content) {
                        $message->content = Crypt::decryptString($message->content);
                    }
                } catch (\Exception $e) {
                    Log::error('Không thể giải mã nội dung tin nhắn', [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                    $message->content = '[Không thể giải mã tin nhắn]';
                }
                return $message;
            });

        return [
            'job' => $job,
            'messages' => $messages,
            'receiverId' => $freelancerId,
        ];
    }
}