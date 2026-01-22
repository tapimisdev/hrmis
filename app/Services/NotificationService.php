<?php

namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
class NotificationService {

    public function getNotifications($request, array $receivers)  
    {

        $filter = $request->filter;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;  

        $query = DB::table('notifications')
            ->select('id', 'sender', 'receiver', 'type', 'data', 'created_at', 'is_read')
            ->whereIn('receiver', $receivers);  

        if ($filter === 'unread') {
            $query->where('is_read', 0);
        } elseif ($filter === 'read') {
            $query->where('is_read', 1);
        }

        $query->offset($offset) 
            ->limit($limit)
            ->orderBy('created_at', 'desc');

        $data = $query->get()->map(function ($item) {
            if (isset($item->data)) {
                $item->data = json_decode($item->data, true);
            }
            return $item;
        });

        return $data;
    }

    public function saveReadNotification($request)
    {
        DB::table('notifications')
            ->where('id', $request->notification_id)
            ->update([
                'is_read' => true,
            ]);

        return [
            'status' => 'success',
            'notification_id' => $request->notification_id,
            'is_read_at' => now(),
        ];
    } 

}