<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FontChoice;
use App\Http\Controllers\Controller;
use App\Models\BrandKit;
use App\Support\Activity;
use App\Support\Typography\TypeScale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * The kit's fonts and modular type scale (story 22).
 */
class TypeController extends Controller
{
    public function update(Request $request, BrandKit $kit): RedirectResponse
    {
        Gate::authorize('update', $kit);

        $validated = $request->validateWithBag('type', [
            'heading_font' => ['required', Rule::enum(FontChoice::class)],
            'body_font' => ['required', Rule::enum(FontChoice::class)],
            'base_size_px' => ['required', 'integer', 'between:'.TypeScale::MIN_BASE.','.TypeScale::MAX_BASE],
            'ratio_preset' => ['required', Rule::in([...array_map('strval', array_keys(TypeScale::RATIOS)), 'custom'])],
            'ratio' => ['required_if:ratio_preset,custom', 'nullable', 'string', 'max:6'],
            'steps_up' => ['required', 'integer', 'between:1,'.TypeScale::MAX_UP],
            'steps_down' => ['required', 'integer', 'between:0,'.TypeScale::MAX_DOWN],
        ]);

        $ratio = $validated['ratio_preset'] === 'custom'
            ? TypeScale::parseRatio((string) $validated['ratio'])
            : (int) $validated['ratio_preset'];

        if ($ratio === null) {
            throw ValidationException::withMessages(['ratio' => 'Type the ratio as a number like 1.25.'])->errorBag('type');
        }

        $problem = TypeScale::problem(
            (int) $validated['base_size_px'],
            $ratio,
            (int) $validated['steps_up'],
            (int) $validated['steps_down'],
        );

        if ($problem !== null) {
            throw ValidationException::withMessages([$validated['ratio_preset'] === 'custom' ? 'ratio' : 'steps_up' => $problem])->errorBag('type');
        }

        $kit->heading_font = (string) $validated['heading_font'];
        $kit->body_font = (string) $validated['body_font'];
        $kit->base_size_px = (int) $validated['base_size_px'];
        $kit->scale_ratio = $ratio;
        $kit->steps_up = (int) $validated['steps_up'];
        $kit->steps_down = (int) $validated['steps_down'];

        if ($kit->isDirty()) {
            $kit->save();

            Activity::record('palette.type_changed', $kit, [
                'name' => $kit->project->name,
                'heading' => FontChoice::from($kit->heading_font)->label(),
                'body' => FontChoice::from($kit->body_font)->label(),
                'ratio' => TypeScale::formatRatio($ratio),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Type saved.']);

        return to_route('admin.palette.show', $kit->project_id);
    }
}
