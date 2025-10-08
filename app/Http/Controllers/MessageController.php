<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Job;
use App\Models\Account;
use App\Models\BoxChat;
use App\Models\Org;
use App\Models\OrgMember;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function chatAll()
    {
        $userId = Auth::id();

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
                        Log::error('Failed to decrypt latest message content in sidebar', [
                            'message_id' => $latestMsg->id,
                            'error' => $e->getMessage(),
                        ]);
                        $latestMsg->content = '[Không thể giải mã tin nhắn]';
                    }
                    $box->messages = collect([$latestMsg]);
                }
                return $box;
            });

        return view('chat.box', [
            'job' => null,
            'org' => null,
            'messages' => collect([]),
            'receiverId' => null,
            'box' => null,
            'conversations' => $conversations,
        ]);
    }

    private function getConversations($userId, $jobId = null)
    {
        $query = Message::with('sender');

        if ($jobId !== null) {
            $jobOwnerId = Job::find($jobId)->account_id ?? null;
            if (!$jobOwnerId) {
                return collect();
            }

            $query->where(function ($q) use ($userId, $jobOwnerId) {
                $q->where(function ($q2) use ($userId, $jobOwnerId) {
                    $q2->where('sender_id', $userId)
                        ->where('conversation_id', $jobOwnerId);
                })->orWhere(function ($q2) use ($userId, $jobOwnerId) {
                    $q2->where('sender_id', $jobOwnerId)
                        ->where('conversation_id', $userId);
                });
            });
        } else {
            $query->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                    ->orWhere('conversation_id', $userId);
            });
        }

        $messages = $query->orderBy('created_at', 'desc')->get();

        $conversations = $messages->groupBy(function ($msg) use ($userId) {
            return $msg->sender_id == $userId ? $msg->conversation_id : $msg->sender_id;
        });

        return $conversations;
    }

    public function chat($jobId)
    {
        $userId = Auth::id();

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
                    Log::error('Failed to decrypt message content', [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                    $message->content = '[Không thể giải mã tin nhắn]';
                }
                return $message;
            });

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
                        Log::error('Failed to decrypt latest message content in sidebar', [
                            'message_id' => $latestMsg->id,
                            'error' => $e->getMessage(),
                        ]);
                        $latestMsg->content = '[Không thể giải mã tin nhắn]';
                    }
                    $box->messages = collect([$latestMsg]);
                }
                return $box;
            });

        return view('chat.box', [
            'job' => $job,
            'messages' => $messages,
            'receiverId' => $partnerId,
            'box' => $box,
            'conversations' => $conversations,
        ]);
    }

    public function chatWithUser($username)
    {
        $userId = Auth::id();

        // Tìm account_id từ username trong bảng profile
        $profile = Profile::where('username', $username)->first();
        if (!$profile) {
            abort(404, 'Người dùng không tồn tại');
        }

        $partnerId = $profile->account_id;
        if ($partnerId == $userId) {
            abort(403, 'Bạn không thể chat với chính mình');
        }

        // Kiểm tra hoặc tạo BoxChat
        $box = BoxChat::firstOrCreate(
            [
                'sender_id' => min($userId, $partnerId),
                'receiver_id' => max($userId, $partnerId),
                'type' => 1, // Chat 1-1
            ],
            [
                'name' => "Chat: {$userId}-{$partnerId}",
            ]
        );

        // Lấy danh sách tin nhắn
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
                    Log::error('Failed to decrypt message content', [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                    $message->content = '[Không thể giải mã tin nhắn]';
                }
                return $message;
            });

        // Lấy danh sách các cuộc trò chuyện
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
                        Log::error('Failed to decrypt latest message content in sidebar', [
                            'message_id' => $latestMsg->id,
                            'error' => $e->getMessage(),
                        ]);
                        $latestMsg->content = '[Không thể giải mã tin nhắn]';
                    }
                    $box->messages = collect([$latestMsg]);
                }
                return $box;
            });

        // Lấy thông tin partner
        $partner = Account::findOrFail($partnerId);

        return view('chat.box', [
            'job' => null,
            'org' => null,
            'messages' => $messages,
            'receiverId' => $partnerId,
            'box' => $box,
            'conversations' => $conversations,
            'employer' => $partner, // Để hiển thị thông tin đối tác trong giao diện
        ]);
    }
    public function chatGroup($jobId)
    {
        $userId = Auth::id();

        $job = Job::findOrFail($jobId);

        // Kiểm tra user có trong nhóm
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
                    Log::error('Failed to decrypt message content', [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                    $message->content = '[Không thể giải mã tin nhắn]';
                }
                return $message;
            });

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
                        Log::error('Failed to decrypt latest message content in sidebar', [
                            'message_id' => $latestMsg->id,
                            'error' => $e->getMessage(),
                        ]);
                        $latestMsg->content = '[Không thể giải mã tin nhắn]';
                    }
                    $box->messages = collect([$latestMsg]);
                }
                return $box;
            });

        return view('chat.box', [
            'job' => $job,
            'messages' => $messages,
            'receiverId' => null,
            'box' => $box,
            'conversations' => $conversations,
        ]);
    }

    public function chatOrg($orgId)
    {
        $userId = Auth::id();

        $org = Org::findOrFail($orgId);

        // Kiểm tra user có trong tổ chức
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
                'type' => 3, // Type 3 cho chat nhóm tổ chức
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
                    Log::error('Failed to decrypt message content', [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                    $message->content = '[Không thể giải mã tin nhắn]';
                }
                return $message;
            });

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
                        Log::error('Failed to decrypt latest message content in sidebar', [
                            'message_id' => $latestMsg->id,
                            'error' => $e->getMessage(),
                        ]);
                        $latestMsg->content = '[Không thể giải mã tin nhắn]';
                    }
                    $box->messages = collect([$latestMsg]);
                }
                return $box;
            });

        return view('chat.box', [
            'job' => null,
            'org' => $org,
            'messages' => $messages,
            'receiverId' => null,
            'box' => $box,
            'conversations' => $conversations,
        ]);
    }

    public function chatWithFreelancer($jobId, $freelancerId)
    {
        $job = Job::findOrFail($jobId);
        $userId = Auth::id();

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
                    Log::error('Failed to decrypt message content', [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                    $message->content = '[Không thể giải mã tin nhắn]';
                }
                return $message;
            });

        return view('chat.box', [
            'job' => $job,
            'messages' => $messages,
            'receiverId' => $freelancerId,
        ]);
    }

    public function send(Request $request)
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
            Log::info('Message send request', [
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
                        Log::info('Created directory for messages images', ['directory' => $directory]);
                    }

                    $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                    $fullPath = $directory . '/' . $filename;

                    try {
                        $file->move($directory, $filename);
                        $imgPath = 'img/messages/' . $filename;

                        if (File::exists($fullPath)) {
                            Log::info('Image saved and verified', [
                                'path' => $imgPath,
                                'full_path' => $fullPath,
                                'size' => File::size($fullPath),
                            ]);
                        } else {
                            Log::error('Image saved but not found on disk', ['full_path' => $fullPath]);
                            return response()->json(['error' => 'Lưu ảnh thất bại: File không tồn tại sau khi lưu.'], 500);
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to save image', [
                            'error' => $e->getMessage(),
                            'filename' => $filename,
                            'tmp_path' => $file->getPathname(),
                        ]);
                        return response()->json(['error' => 'Lỗi khi lưu hình ảnh: ' . $e->getMessage()], 500);
                    }
                } else {
                    Log::error('Invalid image file uploaded', ['tmp_path' => $file->getPathname()]);
                    return response()->json(['error' => 'File ảnh không hợp lệ.'], 422);
                }
            }

            if ($receiverId) {
                // Chat 1-1
                $receiver = Account::find($receiverId);
                if (!$receiver) {
                    Log::warning('Receiver not found', ['receiver_id' => $receiverId]);
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
                        Log::warning('Job not found', ['job_id' => $jobId]);
                        return response()->json(['error' => 'Công việc không tồn tại'], 404);
                    }
                    if ($job->account_id != $senderId && $job->account_id != $receiverId) {
                        Log::warning('Unauthorized job access', [
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
                    Log::warning('Job not found', ['job_id' => $jobId]);
                    return response()->json(['error' => 'Công việc không tồn tại'], 404);
                }
                if ($job->status !== 'in_progress') {
                    Log::warning('Job not in progress', ['job_id' => $jobId]);
                    return response()->json(['error' => 'Công việc không ở trạng thái đang tiến hành'], 403);
                }

                $members = array_filter(explode(',', $job->apply_id) ?? []);
                $members[] = $job->account_id;
                if (!in_array($senderId, $members)) {
                    Log::warning('Unauthorized group access', ['sender_id' => $senderId, 'job_id' => $jobId]);
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
                    Log::warning('Org not found', ['org_id' => $orgId]);
                    return response()->json(['error' => 'Tổ chức không tồn tại'], 404);
                }

                $member = OrgMember::where('org_id', $orgId)
                    ->where('account_id', $senderId)
                    ->where('status', 'ACTIVE')
                    ->first();
                if (!$member) {
                    Log::warning('Unauthorized org access', ['sender_id' => $senderId, 'org_id' => $orgId]);
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

            // CẬP NHẬT UPDATED_AT CỦA BOX_CHAT SAU KHI TẠO TIN NHẮN
            if ($message->box_id) {
                $boxToUpdate = BoxChat::find($message->box_id);
                if ($boxToUpdate) {
                    $boxToUpdate->touch(); // Cập nhật updated_at (và created_at nếu cần, nhưng chủ yếu updated_at)
                    Log::info('Updated box_chat updated_at after new message', ['box_id' => $boxToUpdate->id, 'new_updated_at' => $boxToUpdate->updated_at]);
                }
            }

            $message->load('sender');

            if (!$message->sender) {
                Log::error('Sender not found for message', ['message_id' => $message->id, 'sender_id' => $senderId]);
                return response()->json(['error' => 'Người gửi không tồn tại'], 500);
            }

            // Broadcast
            $plainContent = $content;
            try {
                broadcast(new MessageSent($message))->toOthers();
                Log::info('Message broadcasted successfully', ['message_id' => $message->id]);
            } catch (\Exception $e) {
                Log::error('Broadcasting failed', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'id' => $message->id,
                'content' => $plainContent,
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
            Log::warning('Validation error in send message', [
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);
            return response()->json(['error' => 'Dữ liệu không hợp lệ', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to send message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return response()->json(['error' => 'Lỗi khi gửi tin nhắn: ' . $e->getMessage()], 500);
        }
    }

    public function getMessages($partnerId, $jobId = null)
    {
        $userId = Auth::id();

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
                Log::error('Failed to decrypt message content', [
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

    public function getBoxMessages($boxId)
    {
        $userId = Auth::id();

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
            return response()->json(['error' => 'Box chat not found or access denied'], 404);
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
                    Log::error('Failed to decrypt message content', [
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

    public function getChatList()
    {
        $userId = Auth::id();

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
            ->map(function ($box) use ($userId) {
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
                            Log::error('Failed to decrypt latest message content in chat list', [
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
                            Log::error('Failed to decrypt latest message content in chat list', [
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
                    // Type 3: Org group
                    $latestMsg = $box->messages->first();

                    if ($latestMsg) {
                        try {
                            if ($latestMsg->content) {
                                $latestMsg->content = Crypt::decryptString($latestMsg->content);
                            }
                        } catch (\Exception $e) {
                            Log::error('Failed to decrypt latest message content in chat list', [
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
                        'avatar' => asset('assets/img/org-icon.png'), // Icon cho tổ chức
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
}