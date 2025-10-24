<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MessageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;
use App\Models\Notification;
use App\Events\MessageNotificationBroadcasted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class MessageController extends Controller
{
    protected $messageService;
    protected $notificationService;

    public function __construct(MessageService $messageService, NotificationService $notificationService)
    {
        $this->messageService = $messageService;
        $this->notificationService = $notificationService;
    }


    public function chatAll()
    {
        $userId = Auth::id();
        $conversations = $this->messageService->getConversations($userId);

        return view('chat.box', [
            'job' => null,
            'org' => null,
            'messages' => collect([]),
            'receiverId' => null,
            'box' => null,
            'conversations' => $conversations,
        ]);
    }

    public function chat($jobId)
    {
        $userId = Auth::id();
        $data = $this->messageService->getChatForJob($jobId, $userId);
        $conversations = $this->messageService->getConversations($userId);

        return view('chat.box', array_merge($data, [
            'conversations' => $conversations,
        ]));
    }

    public function chatWithUser($username)
    {
        $userId = Auth::id();
        try {
            $data = $this->messageService->getChatWithUser($username, $userId);
            $conversations = $this->messageService->getConversations($userId);

            return view('chat.box', array_merge($data, [
                'conversations' => $conversations,
            ]));
        } catch (\Exception $e) {
            abort($e->getCode(), $e->getMessage());
        }
    }

    public function chatGroup($jobId)
    {
        $userId = Auth::id();
        try {
            $data = $this->messageService->getGroupChat($jobId, $userId);
            $conversations = $this->messageService->getConversations($userId);

            return view('chat.box', array_merge($data, [
                'conversations' => $conversations,
            ]));
        } catch (\Exception $e) {
            abort($e->getCode(), $e->getMessage());
        }
    }

    public function chatOrg($orgId)
    {
        $userId = Auth::id();
        try {
            $data = $this->messageService->getOrgChat($orgId, $userId);
            $conversations = $this->messageService->getConversations($userId);

            return view('chat.box', array_merge($data, [
                'conversations' => $conversations,
            ]));
        } catch (\Exception $e) {
            abort($e->getCode(), $e->getMessage());
        }
    }

    public function chatWithFreelancer($jobId, $freelancerId)
    {
        $job = \App\Models\Job::findOrFail($jobId);
        $userId = Auth::id();

        if ($userId != $job->account_id) {
            abort(403, 'Bạn không phải chủ job');
        }

        $messages = $this->messageService->getMessagesForPartner($freelancerId, $jobId, $userId);

        return view('chat.box', [
            'job' => $job,
            'messages' => $messages,
            'receiverId' => $freelancerId,
        ]);
    }

    public function send(Request $request)
    {
        try {
            $senderId = Auth::id();
            $message = $this->messageService->sendMessage($request, $senderId);
            $receiverId = $message->receiver_id ?? $request->input('receiver_id');

            if ($receiverId && $receiverId != $senderId) {
                $notification = $this->notificationService->create(
                    userId: $receiverId,
                    type: Notification::TYPE_MESSAGE,
                    title: 'Bạn có tin nhắn mới',
                    body: $message->sender->name . ' vừa gửi cho bạn một tin nhắn.',
                    meta: [
                        'message_id' => $message->id,
                        'conversation_id' => $message->conversation_id,
                        'job_id' => $message->job_id,
                    ],
                    actorId: $senderId,
                    severity: 'low'
                );

                try {
                    broadcast(new MessageNotificationBroadcasted($notification))->toOthers();
                    
                    Cache::forget("header_json_{$receiverId}");
                } catch (\Exception $e) {
                    Log::error('Broadcast message notification thất bại', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'id' => $message->id,
                'content' => $request->input('content'),
                'img' => $message->img ?: null,
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
            return response()->json(['error' => 'Lỗi khi gửi tin nhắn: ' . $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function getMessages($partnerId, $jobId = null)
    {
        $userId = Auth::id();
        $messages = $this->messageService->getMessagesForPartner($partnerId, $jobId, $userId);

        return response()->json($messages->map(function ($message) {
            return [
                'id' => $message->id,
                'content' => $message->content,
                'img' => $message->img ?: null,
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

    public function getBoxMessages(Request $req, $boxId)
    {
        try {
            $userId = Auth::id();

            // Incremental: nếu có since_id -> chỉ trả phần mới
            if ($req->filled('since_id')) {
                $msgs = $this->messageService->getMessagesForBoxSince($boxId, $userId, $req->input('since_id'));
                $payload = $msgs->map(fn($m)=> $this->presentMessage($m));
                // ETag tạm theo last id mới
                $etag = '"' . sha1($boxId.'-since-'.$req->input('since_id').'-'.optional($msgs->last())->id) . '"';
                return Response::json($payload, 200, [
                    'ETag' => $etag,
                    'Cache-Control' => 'private, max-age=0, must-revalidate',
                ]);
            }

            // Full: lấy từ cache theo version
            $messages = $this->messageService->getMessagesForBoxCached($boxId, $userId);
            $last = $messages->last();
            $ver = app(\App\Services\MessageService::class)->getBoxLastId($boxId) ?? optional($last)->id ?? 0;

            // Tạo ETag & Last-Modified
            $etag = '"' . sha1($boxId.'-'.$ver.'-'.$messages->count()) . '"';
            $lastModified = optional($last)->created_at ? $last->created_at->toRfc7231String() : now()->toRfc7231String();

            // Conditional headers: If-None-Match / If-Modified-Since
            $ifNoneMatch = $req->headers->get('If-None-Match');
            $ifModifiedSince = $req->headers->get('If-Modified-Since');

            if ($ifNoneMatch === $etag || ($ifModifiedSince && strtotime($ifModifiedSince) >= strtotime($lastModified))) {
                return response('', 304, [
                    'ETag' => $etag,
                    'Last-Modified' => $lastModified,
                    'Cache-Control' => 'private, max-age=0, must-revalidate',
                ]);
            }

            $payload = $messages->map(fn($m)=> $this->presentMessage($m));

            return Response::json($payload, 200, [
                'ETag' => $etag,
                'Last-Modified' => $lastModified,
                'Cache-Control' => 'private, max-age=0, must-revalidate',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()], $e->getCode() ?: 404);
        }
    }

    private function presentMessage($message): array
    {
        return [
            'id' => $message->id,
            'content' => $message->content,
            'img' => $message->img ?: null, // đã là Cloudinary URL
            'sender_id' => $message->sender_id,
            'sender' => [
                'name' => $message->sender->name ?? 'Unknown',
                'avatar_url' => $message->sender->avatar_url ?? asset('assets/img/defaultavatar.jpg'),
            ],
            'job_id' => $message->job_id,
            'org_id' => $message->org_id,
            'conversation_id' => $message->conversation_id,
            'created_at' => $message->created_at->toISOString(),
        ];
    }

   public function getChatList()
{
    $userId = Auth::id();
    $cacheKey = "chat_list_{$userId}";
    
    // Lấy từ cache, hết hạn sau 5 phút
    $conversations = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId) {
        return $this->messageService->getChatList($userId);
    });

    return response()->json($conversations);
}
}