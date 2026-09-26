<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->appNotifications()->with('actor.stats');

        if ($request->boolean('unread')) {
            $query->unread();
        }

        return NotificationResource::collection($query->latest()->paginate(30));
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'unread_total' => $request->user()->appNotifications()->unread()->count(),
        ]);
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['read_at' => now()]);

        return new NotificationResource($notification);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->appNotifications()->unread()->update(['read_at' => now()]);

        return response()->json(['message' => 'Hamısı oxundu kimi işarələndi.']);
    }
}
