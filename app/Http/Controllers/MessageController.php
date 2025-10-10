<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MessageService;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    protected $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * Hiển thị danh sách tất cả các cuộc trò chuyện.
     *
     * @return \Illuminate\View\View
     */
    public function chatAll()
    {
        $userId = Auth::id();
        $conversations = $this->messageService->getAllConversations($userId);

        return view('chat.box', [
            'job' => null,
            'org' => null,
            'messages' => collect([]),
            'receiverId' => null,
            'box' => null,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Hiển thị cuộc trò chuyện liên quan đến một công việc.
     *
     * @param int $jobId
     * @return \Illuminate\View\View
     */
    public function chat($jobId)
    {
        $userId = Auth::id();
        $data = $this->messageService->getChatForJob($jobId, $userId);

        return view('chat.box', $data);
    }

    /**
     * Hiển thị cuộc trò chuyện với một người dùng cụ thể.
     *
     * @param string $username
     * @return \Illuminate\View\View
     */
    public function chatWithUser($username)
    {
        $userId = Auth::id();
        $data = $this->messageService->getChatWithUser($username, $userId);

        return view('chat.box', $data);
    }

    /**
     * Hiển thị cuộc trò chuyện nhóm cho một công việc.
     *
     * @param int $jobId
     * @return \Illuminate\View\View
     */
    public function chatGroup($jobId)
    {
        $userId = Auth::id();
        $data = $this->messageService->getChatGroup($jobId, $userId);

        return view('chat.box', $data);
    }

    /**
     * Hiển thị cuộc trò chuyện nhóm cho một tổ chức.
     *
     * @param int $orgId
     * @return \Illuminate\View\View
     */
    public function chatOrg($orgId)
    {
        $userId = Auth::id();
        $data = $this->messageService->getChatOrg($orgId, $userId);

        return view('chat.box', $data);
    }

    /**
     * Hiển thị cuộc trò chuyện giữa chủ job và freelancer.
     *
     * @param int $jobId
     * @param int $freelancerId
     * @return \Illuminate\View\View
     */
    public function chatWithFreelancer($jobId, $freelancerId)
    {
        $userId = Auth::id();
        $data = $this->messageService->getChatWithFreelancer($jobId, $freelancerId, $userId);

        return view('chat.box', $data);
    }

    /**
     * Gửi tin nhắn.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request)
    {
        return $this->messageService->sendMessage($request);
    }

    /**
     * Lấy tin nhắn giữa hai người dùng.
     *
     * @param int $partnerId
     * @param int|null $jobId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages($partnerId, $jobId = null)
    {
        $userId = Auth::id();
        return $this->messageService->getMessages($partnerId, $jobId, $userId);
    }

    /**
     * Lấy tin nhắn trong một box chat.
     *
     * @param int $boxId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBoxMessages($boxId)
    {
        $userId = Auth::id();
        return $this->messageService->getBoxMessages($boxId, $userId);
    }

    /**
     * Lấy danh sách cuộc trò chuyện.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatList()
    {
        $userId = Auth::id();
        return $this->messageService->getChatList($userId);
    }

    /**
     * Lấy username từ account_id.
     *
     * @param int $accountId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsername($accountId)
    {
        return $this->messageService->getUsername($accountId);
    }

    /**
     * Lấy số lượng tin nhắn chưa đọc.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnreadCount()
    {
        $userId = Auth::id();
        return $this->messageService->getUnreadCount($userId);
    }
}