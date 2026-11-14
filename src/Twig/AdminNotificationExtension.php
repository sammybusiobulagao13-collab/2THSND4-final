<?php

namespace App\Twig;

use App\Repository\MessageRepository;
use App\Repository\OrderRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AdminNotificationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private MessageRepository $messageRepository,
        private OrderRepository $orderRepository
    ) {
    }

    public function getGlobals(): array
    {
        try {
            $unreadMessages = $this->messageRepository->countUnread();
            $pendingOrders = $this->orderRepository->countByStatus('Processing');
        } catch (\Throwable $e) {
            $unreadMessages = 0;
            $pendingOrders = 0;
        }

        return [
            'adminNotifications' => [
                'unreadMessages' => $unreadMessages,
                'pendingOrders' => $pendingOrders,
                'total' => $unreadMessages + $pendingOrders,
            ],
        ];
    }
}
