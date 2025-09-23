<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;

class MessageController extends Controller
{
    /**
     * Hiển thị tất cả cuộc trò chuyện (không lọc job)
     */
    public function chatAll()
    {
        $userId = Auth::id();
        $conversations = $this->getConversations($userId);

        return view('chat.box', [
            'job' => null,
            'messages' => collect([]),
            'receiverId' => null,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Lấy danh sách conversation cho sidebar
     */
    private function getConversations($userId, $jobId = null)
    {
        $query = Message::with('sender')
            ->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                    ->orWhere('conversation_id', $userId);
            });

        if ($jobId !== null) {
            $query->where('job_id', $jobId);
        }

        // Lấy tất cả tin nhắn mới nhất theo created_at DESC
        $messages = $query->orderBy('created_at', 'desc')->get();

        // Group by partner
        $conversations = $messages->groupBy(function ($msg) use ($userId) {
            return $msg->sender_id == $userId ? $msg->conversation_id : $msg->sender_id;
        });

        return $conversations;
    }


    /**
     * Freelancer chat với chủ job
     */
    public function chat($jobId)
    {
        $job = Job::findOrFail($jobId);
        $userId = Auth::id();
        $employerId = $job->account_id;

        // Nếu không phải chủ job thì check xem có apply chưa
        if ($userId != $employerId) {
            $appliedUsers = $job->apply_id ? explode(',', $job->apply_id) : [];
            if (!in_array($userId, $appliedUsers)) {
                abort(403, 'Bạn không có quyền vào phòng chat này');
            }
        }

        // receiver luôn là chủ job (employer)
        $receiverId = $employerId;

        // Lấy tất cả tin nhắn giữa user ↔ employer cho job này
        $messages = Message::with('sender')
            ->where('job_id', $jobId)
            ->where(function ($q) use ($userId, $employerId) {
                $q->where(function ($q2) use ($userId, $employerId) {
                    $q2->where('sender_id', $userId)
                        ->where('conversation_id', $employerId);
                })->orWhere(function ($q2) use ($userId, $employerId) {
                    $q2->where('sender_id', $employerId)
                        ->where('conversation_id', $userId);
                });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $conversations = $this->getConversations($userId, $jobId);

        return view('chat.box', [
            'job' => $job,
            'messages' => $messages,
            'receiverId' => $receiverId,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Chủ job chat trực tiếp với freelancer
     */
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
            ->get();

        return view('chat.box', [
            'job' => $job,
            'messages' => $messages,
            'receiverId' => $freelancerId,
        ]);
    }

    /**
     * Gửi tin nhắn
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'job_id' => ['required', 'exists:jobs,job_id'],
            'content' => ['required', 'string'],
        ]);

        $job = Job::findOrFail($validated['job_id']);
        $senderId = Auth::id();

        // Xác định người nhận
        $receiverId = $job->account_id == $senderId
            ? Message::where('job_id', $job->job_id)
                ->where('sender_id', '!=', $senderId)
                ->latest()
                ->first()?->sender_id
            : $job->account_id;

        if (!$receiverId) {
            return response()->json(['error' => 'Không xác định được người nhận'], 422);
        }

        $message = Message::create([
            'conversation_id' => $receiverId,
            'sender_id' => $senderId,
            'job_id' => $validated['job_id'],
            'content' => $validated['content'],
            'type' => 1,
            'status' => 1,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'id' => $message->id,
            'content' => $message->content,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
            'job_id' => $message->job_id,
            'conversation_id' => $message->conversation_id,
        ]);
    }


    /**
     * API: lấy lịch sử tin nhắn
     */
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

        $messages = $query->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }

}
