<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\ReviewRound;
use App\Models\User;
use App\Support\Activity;
use App\Support\Proofmark\DesignFiles;
use App\Support\Proofmark\ImageLimits;
use App\Support\Proofmark\ImageProcessor;
use App\Support\Proofmark\RejectedImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Designs in a draft round: upload, rename, reorder, remove.
 */
class DesignController extends Controller
{
    public function store(Request $request, ReviewRound $round, ImageProcessor $processor): RedirectResponse
    {
        Gate::authorize('update', $round);

        // Only "is it a real upload" here; what the file really is comes
        // from its content, in ImageProcessor (ADR 0009).
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'image' => ['required', 'file', 'max:'.intdiv(ImageLimits::MAX_BYTES, 1024)],
        ], [
            'image.max' => 'The file is larger than 8 MB.',
        ]);

        /** @var UploadedFile $file */
        $file = $validated['image'];

        try {
            $image = $processor->process($file->getRealPath() ?: '');
        } catch (RejectedImage $e) {
            throw ValidationException::withMessages(['image' => $e->getMessage()]);
        }

        /** @var User $user */
        $user = $request->user();

        if (($problem = DesignFiles::quotaProblem($user->workspace, $image->bytes())) !== null) {
            throw ValidationException::withMessages(['image' => $problem]);
        }

        $path = DesignFiles::store($round->workspace_id, $image);

        $design = new Design;
        $design->review_round_id = $round->id;
        $design->title = $validated['title'];
        $design->position = (int) $round->designs()->max('position') + 1;
        $design->source = 'upload';
        $design->path = $path;
        $design->original_name = mb_substr($file->getClientOriginalName(), 0, 255);
        $design->mime = $image->mime;
        $design->width = $image->width;
        $design->height = $image->height;
        $design->bytes = $image->bytes();
        $design->save();

        Activity::record('proofmark.design_added', $round, $this->snapshot($round, $design));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Added {$design->title}."]);

        return $this->backToRound($round);
    }

    public function update(Request $request, Design $design): RedirectResponse
    {
        Gate::authorize('update', $design->round);

        $validated = $request->validateWithBag("design-{$design->id}", [
            'title' => ['required', 'string', 'max:120'],
        ]);

        $before = $design->title;
        $design->title = $validated['title'];
        $design->save();

        if ($design->wasChanged('title')) {
            Activity::record('proofmark.design_renamed', $design->round, [
                ...$this->snapshot($design->round, $design),
                'from' => $before,
            ]);
        }

        return $this->backToRound($design->round);
    }

    /**
     * Moves a design one place up or down in its round.
     */
    public function move(Request $request, Design $design): RedirectResponse
    {
        Gate::authorize('update', $design->round);

        $direction = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]])['direction'];

        DB::transaction(function () use ($design, $direction): void {
            $ids = $design->round->designs()->pluck('id')->all();
            $from = (int) array_search($design->id, $ids, true);
            $to = $direction === 'up' ? $from - 1 : $from + 1;

            if (! isset($ids[$to])) {
                return;
            }

            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

            foreach ($ids as $position => $id) {
                Design::whereKey($id)->update(['position' => $position]);
            }
        });

        return $this->backToRound($design->round);
    }

    public function destroy(Design $design): RedirectResponse
    {
        Gate::authorize('update', $design->round);

        $round = $design->round;
        $design->delete();
        DesignFiles::delete($design);

        Activity::record('proofmark.design_removed', $round, $this->snapshot($round, $design));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Removed {$design->title}."]);

        return $this->backToRound($round);
    }

    /**
     * @return array<string, string>
     */
    private function snapshot(ReviewRound $round, Design $design): array
    {
        return ['name' => $round->project->name, 'round' => $round->label(), 'design' => $design->title];
    }

    private function backToRound(ReviewRound $round): RedirectResponse
    {
        return to_route('admin.proofmark.show', [$round->project_id, 'round' => $round->number]);
    }
}
