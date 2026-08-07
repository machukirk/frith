<?php

namespace App\Services;

use App\Models\WaitlistSignup;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Pushes the waiting list to MailerLite, which is where the launch email will
 * be written and sent from.
 *
 * Our database stays the source of truth. MailerLite is a mirror, kept in step
 * so that whoever writes the launch email has an audience to send it to. If
 * this is unconfigured or MailerLite is down, signups carry on working — that
 * is the whole reason the list lives here first.
 *
 * Only confirmed addresses are ever sent. An unconfirmed signup has not proved
 * the address belongs to them, and pushing those would put unverified addresses
 * into the thing that does the actual sending.
 */
class MailerLite
{
    private const BASE = 'https://connect.mailerlite.com/api';

    public function enabled(): bool
    {
        return filled(config('services.mailerlite.key'));
    }

    public function subscribe(WaitlistSignup $signup): void
    {
        if (! $signup->isConfirmed() || $signup->hasUnsubscribed()) {
            return;
        }

        $this->upsert($signup->email, 'active');
    }

    public function unsubscribe(WaitlistSignup $signup): void
    {
        // Marked rather than deleted. A deleted subscriber can be re-added by a
        // later import; an unsubscribed one is a standing instruction not to.
        $this->upsert($signup->email, 'unsubscribed');
    }

    private function upsert(string $email, string $status): void
    {
        if (! $this->enabled()) {
            return;
        }

        $payload = ['email' => $email, 'status' => $status];

        if ($group = config('services.mailerlite.group_id')) {
            $payload['groups'] = [(string) $group];
        }

        // POST to /subscribers upserts by email, so this is safe to repeat —
        // which matters, because the queue may retry it.
        $this->request()->post(self::BASE.'/subscribers', $payload)->throw();
    }

    private function request(): PendingRequest
    {
        return Http::withToken(config('services.mailerlite.key'))
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->retry(2, 500, throw: false);
    }
}
