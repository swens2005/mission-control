<?php

namespace App\Support\Launch;

use App\Models\User;
use Closure;

/**
 * Validation for a launch's site URL. Demo sandboxes may only point at the
 * hosts in config('demo.check_hosts'), so anonymous visitors can't aim the
 * server at arbitrary sites (ADR 0007).
 */
final class LaunchUrl
{
    public static function restrictedFor(?User $user): bool
    {
        return $user?->workspace->is_sandbox ?? false;
    }

    /**
     * @return list<mixed>
     */
    public static function rules(?User $user): array
    {
        $rules = ['required', 'string', 'max:2048', 'url:http,https'];

        if (self::restrictedFor($user)) {
            $rules[] = function (string $attribute, mixed $value, Closure $fail): void {
                if (! self::allowedInDemo((string) $value)) {
                    $fail('In the demo, Launch Control can only check '.implode(' or ', self::demoHosts()).'.');
                }
            };
        }

        return $rules;
    }

    public static function allowedInDemo(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array(rtrim($host, '.'), self::demoHosts(), true);
    }

    /**
     * @return list<string>
     */
    public static function demoHosts(): array
    {
        /** @var list<string> $hosts */
        $hosts = config('demo.check_hosts');

        return $hosts;
    }
}
