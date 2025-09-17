<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;

class MessageController extends Controller
{
    // Freelancer chat với chủ job
    public function chat($jobId)
    {
        $job = Job::findOrFail($jobId);
        $userId = Auth::id();

        // Nếu user là chủ job -> hiển thị danh sách freelancer đã chat
        if ($userId == $job->account_id) {
            $freelancers = Message::where('job_id', $jobId)
                ->with('sender')
                ->get()
                ->pluck('sender')
                ->unique('id');

            return view('chat.list', compact('job', 'freelancers'));
        }

        // Freelancer -> chat với chủ job
        $receiverId = $job->account_id;

        $messages = Message::with('sender')
            ->where('job_id', $jobId)
            ->where(function($q) use ($userId, $receiverId) {
                $q->where(function($q2) use ($userId, $receiverId) {
                    $q2->where('sender_id', $userId)
                       ->where('conversation_id', $receiverId);
                })
                ->orWhere(function($q2) use ($userId, $receiverId) {
                    $q2->where('sender_id', $receiverId)
                       ->where('conversation_id', $userId);
                });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return view('chat.box', compact('job', 'messages', 'receiverId'));
    }

    // Chủ job chat với freelancer
    public function chatWithFreelancer($jobId, $freelancerId)
    {
        $job = Job::findOrFail($jobId);
        $userId = Auth::id();

        if ($userId != $job->account_id) {
            abort(403, 'Bạn không phải chủ job');
        }

        $receiverId = $freelancerId;

        $messages = Message::with('sender')
            ->where('job_id', $jobId)
            ->where(function($q) use ($userId, $freelancerId) {
                $q->where(function($q2) use ($userId, $freelancerId) {
                    $q2->where('sender_id', $userId)
                       ->where('conversation_id', $freelancerId);
                })
                ->orWhere(function($q2) use ($userId, $freelancerId) {
                    $q2->where('sender_id', $freelancerId)
                       ->where('conversation_id', $userId);
                });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return view('chat.box', compact('job', 'messages', 'receiverId'));
    }

    // Gửi tin nhắn
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
}
