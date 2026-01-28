<?php

namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Events\NotificationEvents;

class EventService {

    public function getNotifications($request, array $receivers)  
    {
        $filter = $request->filter ?? null;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        // Base query
        $query = DB::table('notifications')
            ->select(
                'notifications.id',
                'notifications.sender',
                'notifications.receiver',
                'notifications.type',
                'notifications.data',
                'notifications.created_at',
                DB::raw('COALESCE(notification_reads.is_read, 0) as is_read'),
                'notification_reads.read_at'
            )
            ->leftJoin('notification_reads', function ($join) use ($receivers) {
                $join->on('notifications.id', '=', 'notification_reads.notification_id')
                    ->whereIn('notification_reads.user_id', $receivers);
            })
            ->whereIn('notifications.receiver', $receivers)
            ->orderBy('notifications.created_at', 'desc');

        // Apply filter
        if ($filter === 'unread') {
            $query->where(function ($q) {
                $q->where('notification_reads.is_read', 0)
                ->orWhereNull('notification_reads.is_read');
            });
        } elseif ($filter === 'read') {
            $query->where('notification_reads.is_read', 1);
        }

        // Clone query to get total count for this filter
        $totalCount = (clone $query)->count();

        // Fetch paginated data
        $notifications = $query->offset($offset)
                            ->limit($limit)
                            ->get()
                            ->map(function ($item) {
                                $item->data = $item->data ? json_decode($item->data, true) : null;
                                return $item;
                            });

        return [
            'notifications' => $notifications,
            'total' => $totalCount,
            'isUnreadMoreThanLimit' => $totalCount > ($offset + $limit),
        ];
    }

    public function saveReadNotification($request)
    {
        $notificationId = $request->notification_id;
        $userId = $request->user_id;
        $now = now();

        DB::table('notification_reads')->updateOrInsert(
            [
                'notification_id' => $notificationId,
                'user_id' => $userId,
            ],
            [
                'is_read' => 1,
                'read_at' => $now,
                'updated_at' => $now,
            ]
        );

        return [
            'status' => 'success',
            'notification_id' => $notificationId,
            'read_at' => $now,
        ];
    }


    public function pushNotification(array $payload) {

        $type = $payload['type'];
        $sender = $payload['sender'];
        $receiver = $payload['receiver'];
        $message = $payload['message'];
        $link = $payload['link'];

        try {
            event(new NotificationEvents($type, $sender, $receiver, [
                'message' => $message,
                'link'    => $link
            ]));
        } catch (\Exception $e) {
            throw "Error: " . $e->getMessage();
        }
    }

}