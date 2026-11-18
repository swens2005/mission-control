<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * One call to the Claude API by the proposal assistant (story 42).
 *
 * A ledger, not workspace data: it has no workspace scope, and a row stays
 * (with workspace_id null) when a sandbox is pruned, so the month's spend
 * and the daily demo count stay right (ADR 0014).
 *
 * @property int $id
 * @property int|null $workspace_id
 * @property int|null $user_id
 * @property bool $is_sandbox
 * @property int $input_tokens
 * @property int $output_tokens
 * @property int $cost_micro_usd millionths of a US dollar
 * @property int $lines
 * @property CarbonImmutable $created_at
 */
class AiDraft extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'is_sandbox' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
