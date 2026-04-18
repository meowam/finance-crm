<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class NotificationRedirectController extends Controller
{
    public function openPolicy(UserNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->notifiable_id === (int) Auth::id(), 403);

        if (is_null($notification->read_at)) {
            $notification->forceFill([
                'read_at' => now(),
            ])->save();
        }

        $url = $notification->data['policy_url'] ?? null;

        abort_unless(filled($url), 404);

        return redirect($url);
    }

    public function process(UserNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->notifiable_id === (int) Auth::id(), 403);

        $notification->delete();

        return redirect('/admin');
    }
}