<?php

namespace App\Support\Launch;

use App\Enums\ChecklistOwner;
use App\Enums\ProjectPhase;
use App\Models\Launch;
use App\Models\Signoff;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Validation\ValidationException;

/**
 * Signing off the launch, voiding signatures when the board stops being
 * clear, and the final "launched". Callers authorize first.
 */
final class LaunchSignoffs
{
    /**
     * @throws ValidationException when the board isn't clear, this side already
     *                             signed, or the typed name isn't the signer's
     */
    public static function sign(Launch $launch, User $user, ChecklistOwner $role, string $typedName, ?string $ip): Signoff
    {
        if (! GoNoGo::forLaunch($launch)->clear) {
            throw ValidationException::withMessages(['name' => 'The board isn\'t clear yet, so it can\'t be signed.']);
        }

        if ($launch->signoffs()->active()->where('role', $role)->exists()) {
            throw ValidationException::withMessages(['name' => "The {$role->label()} side has already signed."]);
        }

        if (self::normalize($typedName) !== self::normalize($user->name)) {
            throw ValidationException::withMessages(['name' => "That doesn't match the name on your account."]);
        }

        $signoff = new Signoff;
        $signoff->forceFill([
            'workspace_id' => $launch->workspace_id,
            'launch_id' => $launch->id,
            'user_id' => $user->id,
            'role' => $role,
            'name_typed' => trim($typedName),
            'ip' => $ip,
            'signed_at' => now(),
        ])->save();

        Activity::record('launch.signed', $launch, [
            'name' => $launch->project->name,
            'role' => $role->label(),
        ], visibleToClient: true, actor: $user);

        return $signoff;
    }

    /**
     * Call after anything that can make the board less ready: a new check
     * run, an unticked or added item, a withdrawn waiver, a new URL.
     */
    public static function voidIfNotClear(Launch $launch, string $reason, ?User $actor = null): void
    {
        $active = $launch->signoffs()->active()->get();

        if ($active->isEmpty() || GoNoGo::forLaunch($launch)->clear) {
            return;
        }

        foreach ($active as $signoff) {
            $signoff->forceFill(['voided_at' => now(), 'void_reason' => $reason])->save();
        }

        Activity::record('launch.signoffs_voided', $launch, [
            'name' => $launch->project->name,
            'reason' => $reason,
        ], visibleToClient: true, actor: $actor);
    }

    /**
     * @throws ValidationException unless the board says GO
     */
    public static function markLaunched(Launch $launch, User $user): void
    {
        if (! GoNoGo::forLaunch($launch)->go) {
            throw ValidationException::withMessages(['launch' => 'The board must say GO before the project can be marked launched.']);
        }

        $project = $launch->project;
        $from = $project->phase;
        $project->update(['phase' => ProjectPhase::Launched]);

        Activity::record('project.phase_changed', $project, [
            'name' => $project->name,
            'from' => $from->label(),
            'to' => ProjectPhase::Launched->label(),
        ], visibleToClient: true, actor: $user);
    }

    private static function normalize(string $name): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $name)));
    }
}
