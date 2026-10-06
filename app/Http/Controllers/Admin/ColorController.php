<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ColorRole;
use App\Http\Controllers\Controller;
use App\Models\BrandKit;
use App\Models\Color;
use App\Support\Activity;
use App\Support\Color\ColorInput;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Colors in a brand kit: add, edit, reorder, remove (story 20).
 */
class ColorController extends Controller
{
    public function store(Request $request, BrandKit $kit): RedirectResponse
    {
        Gate::authorize('update', $kit);

        $validated = $request->validate($this->rules());

        $color = new Color;
        $color->brand_kit_id = $kit->id;
        $color->position = (int) $kit->colors()->max('position') + 1;
        $this->fill($color, $validated);
        $color->save();

        Activity::record('palette.color_added', $kit, $this->snapshot($kit, $color));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Added {$color->name}."]);

        return to_route('admin.palette.show', $kit->project_id);
    }

    public function update(Request $request, Color $color): RedirectResponse
    {
        Gate::authorize('update', $color->kit);

        $validated = $request->validateWithBag("color-{$color->id}", $this->rules());

        $this->fill($color, $validated);

        if ($color->isDirty()) {
            $color->save();
            Activity::record('palette.color_changed', $color->kit, $this->snapshot($color->kit, $color));
        }

        return to_route('admin.palette.show', $color->kit->project_id);
    }

    /**
     * Moves a color one place up or down in the kit.
     */
    public function move(Request $request, Color $color): RedirectResponse
    {
        Gate::authorize('update', $color->kit);

        $direction = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]])['direction'];

        DB::transaction(function () use ($color, $direction): void {
            $ids = $color->kit->colors()->pluck('id')->all();
            $from = (int) array_search($color->id, $ids, true);
            $to = $direction === 'up' ? $from - 1 : $from + 1;

            if (! isset($ids[$to])) {
                return;
            }

            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

            foreach ($ids as $position => $id) {
                Color::whereKey($id)->update(['position' => $position]);
            }
        });

        return to_route('admin.palette.show', $color->kit->project_id);
    }

    public function destroy(Color $color): RedirectResponse
    {
        Gate::authorize('update', $color->kit);

        $kit = $color->kit;
        $color->delete();

        Activity::record('palette.color_removed', $kit, $this->snapshot($kit, $color));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Removed {$color->name}."]);

        return to_route('admin.palette.show', $kit->project_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'role' => ['required', Rule::enum(ColorRole::class)],
            'value' => ['required', 'string', 'max:60', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || ColorInput::parse($value) === null) {
                    $fail('Use a hex color like #10233a, or oklch(0.27 0.05 255).');
                }
            }],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fill(Color $color, array $validated): void
    {
        $input = ColorInput::parse((string) $validated['value']);

        // Validated above; parse can't fail here.
        if ($input === null) {
            return;
        }

        $color->name = (string) $validated['name'];
        $color->role = ColorRole::from((string) $validated['role']);
        $color->setValue($input);
    }

    /**
     * @return array<string, string>
     */
    private function snapshot(BrandKit $kit, Color $color): array
    {
        return ['name' => $kit->project->name, 'color' => $color->name, 'hex' => $color->hex];
    }
}
