<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $usuario = Auth::user();

        $query = Notification::where('user_id', $usuario->id);

        if ($request->boolean('no_leidas')) {
            $query->noLeidas();
        }

        $notificaciones = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($notificaciones);
    }

    public function marcarLeida($id)
    {
        $usuario = Auth::user();
        $notificacion = Notification::where('user_id', $usuario->id)
            ->findOrFail($id);

        $notificacion->update(['read_at' => now()]);

        Cache::forget("user.{$usuario->id}.notificaciones_no_leidas");

        return response()->json([
            'message' => 'Notificación marcada como leída',
        ]);
    }

    public function marcarTodasLeidas()
    {
        $usuario = Auth::user();

        $actualizadas = Notification::where('user_id', $usuario->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Cache::forget("user.{$usuario->id}.notificaciones_no_leidas");

        return response()->json([
            'message' => 'Todas las notificaciones marcadas como leídas',
        ]);
    }

    public function contador()
    {
        $usuario = Auth::user();

        $noLeidas = Cache::remember("user.{$usuario->id}.notificaciones_no_leidas", 60, function () use ($usuario) {
            return Notification::where('user_id', $usuario->id)
                ->whereNull('read_at')
                ->count();
        });

        return response()->json([
            'contador' => $noLeidas,
        ]);
    }
}
