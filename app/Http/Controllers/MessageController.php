<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Job;
use App\Models\Account;
use App\Models\BoxChat;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

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
                $q->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($box) {
                if ($box->messages->isNotEmpty()) {
                    $latestMsg = $box->messages->first();
                    try {
                        $latestMsg->content = Crypt::decryptString($latestMsg->content);
                    } catch (\Exception $e) {
                        Log::error('Failed to decrypt latest message content in sidebar', [
                            'message_id' => $latestMsg->id,
                            'error' => $e->getMessage(),
                        ]);
                        $latestMsg->content = '[Không thể giải mã tin nhắn]';
                    }
                    $box->messages = collect([$latestMsg]); // Giữ lại latest sau khi decrypt
                }
                return $box;
            });

        return view('chat.box', [
            'job' => null,
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
                    $message->content = Crypt::decryptString($message->content);
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
                $q->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($box) {
                if ($box->messages->isNotEmpty()) {
                    $latestMsg = $box->messages->first();
                    try {
                        $latestMsg->content = Crypt::decryptString($latestMsg->content);
                    } catch (\Exception $e) {
                        Log::error('Failed to decrypt latest message content in sidebar', [
                            'message_id' => $latestMsg->id,
                            'error' => $e->getMessage(),
                        ]);
                        $latestMsg->content = '[Không thể giải mã tin nhắn]';
                    }
                    $box->messages = collect([$latestMsg]); // Giữ lại latest sau khi decrypt
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
                    $message->content = Crypt::decryptString($message->content);
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
            $validated = $request->validate([
                'job_id' => ['nullable', 'exists:jobs,job_id'],
                'content' => ['required', 'string', 'max:5000'],
                'receiver_id' => ['required', 'exists:accounts,account_id'],
            ]);

            $senderId = Auth::id();
            $receiverId = $validated['receiver_id'];
            $jobId = $validated['job_id'];

            Log::info('Message send request', [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'job_id' => $jobId,
                'content' => $validated['content'],
            ]);

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

            // Mã hóa nội dung tin nhắn trước khi lưu
            $encryptedContent = Crypt::encryptString($validated['content']);

            $message = Message::create([
                'conversation_id' => $receiverId,
                'sender_id' => $senderId,
                'job_id' => $jobId,
                'content' => $encryptedContent, // Lưu nội dung đã mã hóa
                'type' => 1,
                'status' => 1,
                'box_id' => $box->id,
            ]);

            $message->load('sender');

            if (!$message->sender) {
                Log::error('Sender not found for message', ['message_id' => $message->id, 'sender_id' => $senderId]);
                return response()->json(['error' => 'Người gửi không tồn tại'], 500);
            }

            // Giải mã nội dung để trả về client và broadcast
            $message->content = $validated['content']; // Sử dụng nội dung gốc để broadcast

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
                'content' => $message->content, // Nội dung gốc
                'sender_id' => $message->sender_id,
                'sender' => [
                    'name' => $message->sender->name,
                    'avatar_url' => $message->sender->avatar_url ?? asset('assets/img/blog/blog-1.jpg'),
                ],
                'job_id' => $message->job_id,
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
                $message->content = Crypt::decryptString($message->content);
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
                'content' => $message->content, // Nội dung đã giải mã
                'sender_id' => $message->sender_id,
                'sender' => [
                    'name' => $message->sender->name,
                    'avatar_url' => $message->sender->avatar_url ?? asset('assets/img/blog/blog-1.jpg'),
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
                $q->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
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
                    $message->content = Crypt::decryptString($message->content);
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
                'content' => $message->content, // Nội dung đã giải mã
                'sender_id' => $message->sender_id,
                'sender' => [
                    'name' => $message->sender->name,
                    'avatar_url' => $message->sender->avatar_url ?? asset('assets/img/blog/blog-1.jpg'),
                ],
                'job_id' => $message->job_id,
                'conversation_id' => $message->conversation_id,
                'created_at' => $message->created_at->toISOString(),
            ];
        }));
    }

    // Thêm route mới để load chat list via AJAX
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
                $q->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($box) use ($userId) {
                $partnerId = $box->sender_id == $userId ? $box->receiver_id : $box->sender_id;
                $partner = Account::find($partnerId);
                $latestMsg = $box->messages->first();

                if ($latestMsg) {
                    try {
                        $latestMsg->content = Crypt::decryptString($latestMsg->content);
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
                    'partner_name' => $partner ? $partner->name : 'Unknown',
                    'avatar' => $partner ? ($partner->avatar_url ?: asset('assets/img/blog/blog-1.jpg')) : asset('assets/img/blog/blog-1.jpg'),
                    'latest_msg' => $latestMsg ? [
                        'sender_id' => $latestMsg->sender_id,
                        'content' => \Illuminate\Support\Str::limit($latestMsg->content, 25),
                        'created_at' => $latestMsg->created_at->diffForHumans(),
                        'sender_name' => $latestMsg->sender->name,
                    ] : null,
                ];
            });

        return response()->json($conversations);
    }
}