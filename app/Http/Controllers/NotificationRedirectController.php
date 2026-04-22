<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Policies\PolicyResource;
use App\Models\Policy;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NotificationRedirectController extends Controller
{
    protected function getAuthenticatedUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    protected function ensureNotificationOwner(UserNotification $notification, User $user): void
    {
        abort_unless((int) $notification->notifiable_id === (int) $user->id, 403);
    }

    protected function markAsRead(UserNotification $notification): void
    {
        if (is_null($notification->read_at)) {
            $notification->forceFill([
                'read_at' => now(),
            ])->save();
        }
    }

    protected function isSafeInternalAdminUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            $appUrl = rtrim((string) config('app.url'), '/');

            if ($appUrl === '') {
                return false;
            }

            return Str::startsWith($url, $appUrl . '/admin');
        }

        $normalizedPath = '/' . ltrim($url, '/');

        return Str::startsWith($normalizedPath, '/admin');
    }

    protected function resolvePolicyRedirectUrl(UserNotification $notification, User $user): ?string
    {
        $data = is_array($notification->data) ? $notification->data : [];

        $policyId = isset($data['policy_id']) ? (int) $data['policy_id'] : 0;

        if ($policyId > 0) {
            $policy = Policy::query()->findOrFail($policyId);

            abort_unless($policy->isVisibleTo($user), 403);

            return PolicyResource::getUrl('edit', ['record' => $policy->getKey()]);
        }

        $legacyUrl = $data['policy_url'] ?? null;

        if (! is_string($legacyUrl) || ! $this->isSafeInternalAdminUrl($legacyUrl)) {
            return null;
        }

        return $legacyUrl;
    }

    public function openPolicy(UserNotification $notification): RedirectResponse
    {
        $user = $this->getAuthenticatedUser();

        $this->ensureNotificationOwner($notification, $user);
        $this->markAsRead($notification);

        $url = $this->resolvePolicyRedirectUrl($notification, $user);

        abort_unless(filled($url), 404);

        return redirect($url);
    }

    public function process(UserNotification $notification): RedirectResponse
    {
        $user = $this->getAuthenticatedUser();

        $this->ensureNotificationOwner($notification, $user);

        $notification->delete();

        return redirect('/admin');
    }
}