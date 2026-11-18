<?php

namespace App\Support\Ai;

use App\Mail\AiCapReached;
use App\Models\AiDraft;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Who may draft with AI right now, and the ledger behind it (story 42,
 * ADR 0014): a key, a monthly spending cap for everyone, and for the
 * public demo a number of drafts per sandbox and per day.
 */
final class DraftLimits
{
    /** Is the assistant set up at all? Without a key the button is hidden. */
    public function configured(): bool
    {
        $key = config('services.anthropic.key');

        return is_string($key) && $key !== '';
    }

    /**
     * Why this workspace can't draft now, written for the studio; null when
     * it can.
     */
    public function refusal(Workspace $workspace): ?string
    {
        if ($this->monthSpent() >= $this->monthlyCap()) {
            return 'Drafting with AI is paused until next month: this month’s budget is used up.';
        }

        if (! $workspace->is_sandbox) {
            return null;
        }

        if ($this->remainingInSandbox($workspace) <= 0) {
            return 'This demo has used its '.$this->perSandbox().' AI drafts. Start a new demo to try again.';
        }

        $today = AiDraft::query()
            ->where('is_sandbox', true)
            ->where('created_at', '>=', CarbonImmutable::now()->startOfDay())
            ->count();

        if ($today >= $this->demoPerDay()) {
            return 'The demo’s AI drafts for today are used up. Try again tomorrow.';
        }

        return null;
    }

    /** Drafts left in a sandbox; null for a real studio. */
    public function remaining(Workspace $workspace): ?int
    {
        return $workspace->is_sandbox ? max(0, $this->remainingInSandbox($workspace)) : null;
    }

    /**
     * Records a call and, the first time the month's spend reaches the cap,
     * tells Meagan once.
     */
    public function record(User $user, int $inputTokens, int $outputTokens, int $lines): AiDraft
    {
        $workspace = $user->workspace;

        $draft = new AiDraft;
        $draft->workspace_id = $workspace->id;
        $draft->user_id = $user->id;
        $draft->is_sandbox = $workspace->is_sandbox;
        $draft->input_tokens = $inputTokens;
        $draft->output_tokens = $outputTokens;
        $draft->cost_micro_usd = TokenCost::microUsd($inputTokens, $outputTokens);
        $draft->lines = $lines;
        $draft->save();

        $this->noticeCapOnce();

        return $draft;
    }

    /** This month's spend, in micro-dollars. */
    public function monthSpent(): int
    {
        return (int) AiDraft::query()
            ->where('created_at', '>=', CarbonImmutable::now()->startOfMonth())
            ->sum('cost_micro_usd');
    }

    /** The monthly cap, in micro-dollars. */
    public function monthlyCap(): int
    {
        return max(0, (int) config('services.anthropic.monthly_cap_dollars')) * 1_000_000;
    }

    private function noticeCapOnce(): void
    {
        $spent = $this->monthSpent();

        if ($spent < $this->monthlyCap()) {
            return;
        }

        $month = CarbonImmutable::now()->format('Y-m');

        // One row per month: the insert that wins sends the one email.
        $first = DB::table('ai_cap_notices')->insertOrIgnore(['month' => $month, 'notified_at' => now()]) === 1;

        if (! $first) {
            return;
        }

        $message = 'The proposal assistant reached its monthly cap ('.TokenCost::format($this->monthlyCap()).", spent {$this->formatSpent($spent)}) and is paused until next month.";
        $to = config('services.anthropic.cap_email');

        if (! is_string($to) || $to === '') {
            Log::warning($message.' Set AI_CAP_EMAIL to get this by email.');

            return;
        }

        try {
            Mail::to($to)->send(new AiCapReached($month, TokenCost::format($this->monthlyCap()), $this->formatSpent($spent)));
        } catch (Throwable $e) {
            // A mail problem must not turn a finished draft into an error.
            Log::warning($message.' The email could not be sent: '.$e->getMessage());
        }
    }

    private function formatSpent(int $spent): string
    {
        return TokenCost::format($spent);
    }

    private function remainingInSandbox(Workspace $workspace): int
    {
        return $this->perSandbox() - AiDraft::query()->where('workspace_id', $workspace->id)->count();
    }

    private function perSandbox(): int
    {
        return max(0, (int) config('services.anthropic.drafts_per_sandbox'));
    }

    private function demoPerDay(): int
    {
        return max(0, (int) config('services.anthropic.demo_drafts_per_day'));
    }
}
