<?php

namespace App\Http\Controllers;

use App\Models\ScreenNotification;
use App\Support\ScreenNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tudo parte das notificações de quem está logado: a de outra pessoa não existe
 * para esta rota.
 */
class ScreenNotificationController extends Controller
{
    /**
     * Pagina pelo último id exibido: ocultar um item ou receber outro não desloca
     * o que falta carregar.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) config('template.notifications.per_page');
        $before = $request->integer('before');

        $found = $request->user()->screenNotifications()
            ->when($before > 0, fn ($query) => $query->where('id', '<', $before))
            ->orderByDesc('id')
            ->limit($perPage + 1)
            ->get();

        $page = $found->take($perPage);

        return response()->json([
            'data' => $page->map(fn (ScreenNotification $notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'body' => $notification->body,
                'type' => $notification->type,
                'link' => $notification->link,
                'is_new' => $notification->seen_at === null,
                'created_at' => $notification->created_at->toIso8601String(),
            ]),
            'next_before' => $found->count() > $perPage ? $page->last()->id : null,
            'unread' => ScreenNotifications::unread($request->user()),
        ]);
    }

    public function unread(Request $request): JsonResponse
    {
        return $this->count($request);
    }

    public function seen(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['integer'],
        ]);

        $request->user()->screenNotifications()
            ->whereIn('id', $validated['ids'])
            ->whereNull('seen_at')
            ->update(['seen_at' => now()]);

        return $this->count($request);
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $request->user()->screenNotifications()->findOrFail($notification)->delete();

        return $this->count($request);
    }

    private function count(Request $request): JsonResponse
    {
        return response()->json(['unread' => ScreenNotifications::unread($request->user())]);
    }
}
