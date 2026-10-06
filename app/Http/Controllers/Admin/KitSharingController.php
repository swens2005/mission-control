<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandKit;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Sharing a brand kit with the client, its public link, and revisions
 * after approval (story 24).
 */
class KitSharingController extends Controller
{
    public function share(BrandKit $kit): RedirectResponse
    {
        Gate::authorize('share', $kit);

        if ($kit->shared_at === null) {
            if (! $kit->colors()->exists()) {
                throw ValidationException::withMessages(['share' => 'Add at least one color before sharing the kit.']);
            }

            $kit->shared_at = now();
            $kit->save();

            Activity::record('palette.kit_shared', $kit, ['name' => $kit->project->name], visibleToClient: true);

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Shared with the client.']);
        }

        return $this->back($kit);
    }

    /**
     * A new random token each time, so a link that was turned off never
     * comes back to life.
     */
    public function enablePublicLink(BrandKit $kit): RedirectResponse
    {
        Gate::authorize('share', $kit);

        if ($kit->shared_at === null) {
            throw ValidationException::withMessages(['public_link' => 'Share the kit with the client first.']);
        }

        $kit->public_token = Str::random(40);
        $kit->save();

        Activity::record('palette.public_link_on', $kit, ['name' => $kit->project->name]);

        return $this->back($kit);
    }

    public function disablePublicLink(BrandKit $kit): RedirectResponse
    {
        Gate::authorize('share', $kit);

        if ($kit->public_token !== null) {
            $kit->public_token = null;
            $kit->save();

            Activity::record('palette.public_link_off', $kit, ['name' => $kit->project->name]);
        }

        return $this->back($kit);
    }

    /**
     * Unlocks an approved kit for changes; the approval is cleared and the
     * client is asked again.
     */
    public function revise(BrandKit $kit): RedirectResponse
    {
        Gate::authorize('share', $kit);

        if ($kit->isApproved()) {
            $kit->approved_at = null;
            $kit->approved_by_id = null;
            $kit->approved_by_name = null;
            $kit->approved_ip = null;
            $kit->save();

            Activity::record('palette.kit_revised', $kit, ['name' => $kit->project->name], visibleToClient: true);

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Revision started. The client will be asked to approve again.']);
        }

        return $this->back($kit);
    }

    private function back(BrandKit $kit): RedirectResponse
    {
        return to_route('admin.palette.show', $kit->project_id);
    }
}
