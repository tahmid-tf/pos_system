<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        return view('admin.notifications.index');
    }

    public function feed(): JsonResponse
    {
        $notifications = Notification::query()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Notification $notification) => $this->transformNotification($notification))
            ->values();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => Notification::query()->whereNull('read_at')->count(),
            'counts' => [
                'low_stock' => Notification::query()->where('type', 'low_stock')->whereNull('read_at')->count(),
                'new_order' => Notification::query()->where('type', 'new_order')->whereNull('read_at')->count(),
                'payment_reminder' => Notification::query()->where('type', 'payment_reminder')->whereNull('read_at')->count(),
            ],
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $query = Notification::query()->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate(15)->withQueryString();

        return response()->json([
            'data' => $notifications->getCollection()->map(fn (Notification $notification) => $this->transformNotification($notification))->values(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem(),
            ],
        ]);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        $notification->update([
            'read_at' => $notification->read_at ?: now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    public function markAllRead(): JsonResponse
    {
        Notification::query()->whereNull('read_at')->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }

    protected function transformNotification(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'level' => $notification->level,
            'title' => $notification->title,
            'message' => $notification->message,
            'data' => $notification->data ?? [],
            'is_read' => !is_null($notification->read_at),
            'created_at' => optional($notification->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
