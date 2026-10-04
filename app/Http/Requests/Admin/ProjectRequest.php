<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProjectPhase;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    /**
     * Authorization happens in the controller via ProjectPolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            // Only active organizations in the admin's own workspace.
            'organization_id' => ['required', 'integer', Rule::exists('organizations', 'id')
                ->where('workspace_id', $user->workspace_id)
                ->whereNull('archived_at')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'phase' => ['required', Rule::enum(ProjectPhase::class)],
            'target_launch_on' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'organization_id' => 'client',
            'target_launch_on' => 'target launch date',
        ];
    }
}
