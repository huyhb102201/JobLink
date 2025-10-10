<?php

namespace App\Services;

use App\Models\BoxChat;
use App\Models\Job;
use App\Models\Org;
use App\Models\OrgMember;
use App\Models\Message;
use App\Models\Account;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Events\MessageSent;

class MessageService
{
    public function getConversations($userId)
    {
        return BoxChat::with([
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
    }

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

        $messages = $this->getMessagesForBox($box->id, $userId);

        return [
            'job' => $job,
            'messages' => $messages,
            'receiverId' => $partnerId,
            'box' => $box,
        ];
    }

    public function getChatWithUser($username, $userId)
    {
        $profile = Profile::where('username', $username)->first();
        if (!$profile) {
            throw new \Exception('Người dùng không tồn tại', 404);
        }

        $partnerId = $profile->account_id;
        if ($partnerId == $userId) {
            throw new \Exception('Bạn không thể chat với chính mình', 403);
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

        $messages = $this->getMessagesForBox($box->id, $userId);
        $partner = Account::findOrFail($partnerId);

        return [
            'job' => null,
            'org' => null,
            'messages' => $messages,
            'receiverId' => $partnerId,
            'box' => $box,
            'employer' => $partner,
        ];
    }

    public function getGroupChat($jobId, $userId)
    {
        $job = Job::findOrFail($jobId);
        $members = array_filter(explode(',', $job->apply_id) ?? []);
        $members[] = $job->account_id;
        if (!in_array($userId, $members)) {
            throw new \Exception('Bạn không có quyền truy cập chat nhóm này', 403);
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

        $messages = $this->getMessagesForBox($box->id, $userId);

        return [
            'job' => $job,
            'messages' => $messages,
            'receiverId' => null,
            'box' => $box,
        ];
    }

    public function getOrgChat($orgId, $userId)
    {
        $org = Org::findOrFail($orgId);
        $member = OrgMember::where('org_id', $orgId)
            ->where('account_id', $userId)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$member) {
            throw new \Exception('Bạn không có quyền truy cập chat nhóm này', 403);
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

        $messages = $this->getMessagesForBox($box->id, $userId);

        return [
            'job' => null,
            'org' => $org,
            'messages' => $messages,
            'receiverId' => null,
            'box' => $box,
        ];
    }

    public function getMessagesForBox($boxId, $userId)
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
            throw new \Exception('Box chat not found or access denied', 404);
        }

        return Message::with('sender')
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
    }

    public function sendMessage($request, $senderId)
    {
        $request->validate([
            'job_id' => ['nullable', 'exists:jobs,job_id'],
            'org_id' => ['nullable', 'exists:orgs,org_id'],
            'content' => ['nullable', 'string', 'max:5000'],
            'receiver_id' => ['nullable', 'exists:accounts,account_id'],
            'img' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        if (!$request->filled('content') && !$request->hasFile('img')) {
            throw new \Exception('Phải cung cấp ít nhất nội dung tin nhắn hoặc hình ảnh.', 422);
        }

        $receiverId = $request->input('receiver_id');
        $jobId = $request->input('job_id');
        $orgId = $request->input('org_id');
        $content = $request->input('content');

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
        $imgPath = $this->handleImageUpload($request);

        if ($receiverId) {
            return $this->sendOneToOneMessage($senderId, $receiverId, $jobId, $encryptedContent, $imgPath);
        } elseif ($jobId) {
            return $this->sendGroupMessage($senderId, $jobId, $encryptedContent, $imgPath);
        } elseif ($orgId) {
            return $this->sendOrgMessage($senderId, $orgId, $encryptedContent, $imgPath);
        } else {
            throw new \Exception('Phải cung cấp receiver_id, job_id hoặc org_id', 422);
        }
    }

    private function handleImageUpload($request)
    {
        if (!$request->hasFile('img')) {
            return null;
        }

        $file = $request->file('img');
        if (!$file->isValid()) {
            Log::error('Invalid image file uploaded', ['tmp_path' => $file->getPathname()]);
            throw new \Exception('File ảnh không hợp lệ.', 422);
        }

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
                return $imgPath;
            } else {
                Log::error('Image saved but not found on disk', ['full_path' => $fullPath]);
                throw new \Exception('Lưu ảnh thất bại: File không tồn tại sau khi lưu.', 500);
            }
        } catch (\Exception $e) {
            Log::error('Failed to save image', [
                'error' => $e->getMessage(),
                'filename' => $filename,
                'tmp_path' => $file->getPathname(),
            ]);
            throw new \Exception('Lỗi khi lưu hình ảnh: ' . $e->getMessage(), 500);
        }
    }

    private function sendOneToOneMessage($senderId, $receiverId, $jobId, $encryptedContent, $imgPath)
    {
        $receiver = Account::find($receiverId);
        if (!$receiver) {
            Log::warning('Receiver not found', ['receiver_id' => $receiverId]);
            throw new \Exception('Người nhận không tồn tại', 404);
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
                throw new \Exception('Công việc không tồn tại', 404);
            }
            if ($job->account_id != $senderId && $job->account_id != $receiverId) {
                Log::warning('Unauthorized job access', [
                    'job_id' => $jobId,
                    'sender_id' => $senderId,
                    'receiver_id' => $receiverId,
                ]);
                throw new \Exception('Bạn không có quyền gửi tin nhắn cho công việc này', 403);
            }
        }

        $message = $this->createMessage([
            'conversation_id' => $receiverId,
            'sender_id' => $senderId,
            'job_id' => $jobId,
            'content' => $encryptedContent,
            'img' => $imgPath,
            'type' => 1,
            'status' => 1,
            'box_id' => $box->id,
        ]);

        return $message;
    }

    private function sendGroupMessage($senderId, $jobId, $encryptedContent, $imgPath)
    {
        $job = Job::find($jobId);
        if (!$job) {
            Log::warning('Job not found', ['job_id' => $jobId]);
            throw new \Exception('Công việc không tồn tại', 404);
        }
        if ($job->status !== 'in_progress') {
            Log::warning('Job not in progress', ['job_id' => $jobId]);
            throw new \Exception('Công việc không ở trạng thái đang tiến hành', 403);
        }

        $members = array_filter(explode(',', $job->apply_id) ?? []);
        $members[] = $job->account_id;
        if (!in_array($senderId, $members)) {
            Log::warning('Unauthorized group access', ['sender_id' => $senderId, 'job_id' => $jobId]);
            throw new \Exception('Bạn không có quyền gửi tin nhắn trong nhóm này', 403);
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

        $message = $this->createMessage([
            'conversation_id' => 0,
            'sender_id' => $senderId,
            'job_id' => $jobId,
            'content' => $encryptedContent,
            'img' => $imgPath,
            'type' => 1,
            'status' => 1,
            'box_id' => $box->id,
        ]);

        return $message;
    }

    private function sendOrgMessage($senderId, $orgId, $encryptedContent, $imgPath)
    {
        $org = Org::find($orgId);
        if (!$org) {
            Log::warning('Org not found', ['org_id' => $orgId]);
            throw new \Exception('Tổ chức không tồn tại', 404);
        }

        $member = OrgMember::where('org_id', $orgId)
            ->where('account_id', $senderId)
            ->where('status', 'ACTIVE')
            ->first();
        if (!$member) {
            Log::warning('Unauthorized org access', ['sender_id' => $senderId, 'org_id' => $orgId]);
            throw new \Exception('Bạn không có quyền gửi tin nhắn trong nhóm tổ chức này', 403);
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

        $message = $this->createMessage([
            'conversation_id' => 0,
            'sender_id' => $senderId,
            'org_id' => $orgId,
            'content' => $encryptedContent,
            'img' => $imgPath,
            'type' => 1,
            'status' => 1,
            'box_id' => $box->id,
        ]);

        return $message;
    }

    private function createMessage($data)
    {
        $message = Message::create($data);

        if ($message->box_id) {
            $boxToUpdate = BoxChat::find($message->box_id);
            if ($boxToUpdate) {
                $boxToUpdate->touch();
                Log::info('Updated box_chat updated_at after new message', [
                    'box_id' => $boxToUpdate->id,
                    'new_updated_at' => $boxToUpdate->updated_at,
                ]);
            }
        }

        $message->load('sender');
        if (!$message->sender) {
            Log::error('Sender not found for message', ['message_id' => $message->id, 'sender_id' => $data['sender_id']]);
            throw new \Exception('Người gửi không tồn tại', 500);
        }

        try {
            broadcast(new MessageSent($message))->toOthers();
            Log::info('Message broadcasted successfully', ['message_id' => $message->id]);
        } catch (\Exception $e) {
            Log::error('Broadcasting failed', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $message;
    }

    public function getMessagesForPartner($partnerId, $jobId, $userId)
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

        return $query->orderBy('created_at', 'asc')->get()->map(function ($message) {
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
    }

    public function getChatList($userId)
    {
        return $this->getConversations($userId)->map(function ($box) use ($userId) {
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
    }
}