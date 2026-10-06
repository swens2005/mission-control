<?php

namespace App\Support\Proofmark;

use App\Models\Design;
use App\Models\Workspace;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Where design images live (ADR 0009): uploads on the private disk under
 * proofmark/{workspace_id}/, demo designs in resources/demo/proofmark/.
 */
final class DesignFiles
{
    public const ROOT = 'proofmark';

    public static function disk(): Filesystem
    {
        return Storage::disk('local');
    }

    /**
     * Stores a processed upload under a random name; returns its path.
     */
    public static function store(int $workspaceId, ProcessedImage $image): string
    {
        $path = self::ROOT."/{$workspaceId}/".Str::random(40).'.'.$image->extension;

        self::disk()->put($path, $image->contents);

        return $path;
    }

    /**
     * The file on disk to serve for a design.
     */
    public static function absolutePath(Design $design): string
    {
        return $design->source === 'demo'
            ? resource_path('demo/proofmark/'.basename($design->path))
            : self::disk()->path($design->path);
    }

    /**
     * Deletes an uploaded design's file. Demo files are shared and stay.
     */
    public static function delete(Design $design): void
    {
        if ($design->source === 'upload') {
            self::disk()->delete($design->path);
        }
    }

    /**
     * Why this upload would break the demo's disk quota, or null if it fits.
     * Real workspaces have no app-level limit.
     */
    public static function quotaProblem(Workspace $workspace, int $bytes): ?string
    {
        if (! $workspace->is_sandbox) {
            return null;
        }

        $uploads = Design::withoutGlobalScopes()->where('source', 'upload');

        $used = (int) (clone $uploads)->where('workspace_id', $workspace->id)->sum('bytes');

        if ($used + $bytes > config()->integer('demo.upload_quota_bytes')) {
            return 'This demo has used its '.self::humanSize(config()->integer('demo.upload_quota_bytes')).' of uploads. Remove a design to make room.';
        }

        $allSandboxes = (int) $uploads
            ->whereIn('workspace_id', Workspace::query()->where('is_sandbox', true)->select('id'))
            ->sum('bytes');

        if ($allSandboxes + $bytes > config()->integer('demo.total_upload_quota_bytes')) {
            return 'The demo is very busy right now and has run out of upload space. Please try again later.';
        }

        return null;
    }

    /**
     * Bytes of uploads a workspace stores.
     */
    public static function used(Workspace $workspace): int
    {
        return (int) Design::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('source', 'upload')
            ->sum('bytes');
    }

    /**
     * Deletes everything a workspace uploaded (used when a sandbox expires).
     */
    public static function purge(int $workspaceId): void
    {
        self::disk()->deleteDirectory(self::ROOT."/{$workspaceId}");
    }

    /**
     * Deletes upload folders whose workspace no longer exists; returns how
     * many it removed.
     */
    public static function sweep(): int
    {
        $ids = collect(self::disk()->directories(self::ROOT))
            ->map(fn (string $directory) => basename($directory));

        $existing = Workspace::query()
            ->whereIn('id', $ids->filter(fn (string $id) => ctype_digit($id))->all())
            ->pluck('id')
            ->map(fn (int $id) => (string) $id);

        $orphans = $ids->diff($existing);

        foreach ($orphans as $id) {
            self::disk()->deleteDirectory(self::ROOT."/{$id}");
        }

        return $orphans->count();
    }

    /**
     * A file size for people: "15 KB" below 1 MB, otherwise "1.5 MB".
     */
    public static function humanSize(int $bytes): string
    {
        if ($bytes < 1_048_576) {
            return intdiv($bytes + 1023, 1024).' KB';
        }

        return Str::of(number_format($bytes / 1_048_576, 1))->replaceEnd('.0', '').' MB';
    }
}
