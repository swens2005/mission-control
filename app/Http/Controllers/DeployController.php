<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs pending migrations after CI has uploaded a new release.
 *
 * The host has no SSH, so CI calls this endpoint with a bearer token instead.
 * Artisan runs in-process, which also works when proc_open is disabled.
 */
final class DeployController
{
    public function __invoke(Request $request): JsonResponse
    {
        $expected = (string) config('app.deploy_token');
        $given = (string) $request->bearerToken();

        // 404 rather than 401/403, so the endpoint doesn't advertise itself.
        abort_if($expected === '' || ! hash_equals($expected, $given), 404);

        try {
            $status = Artisan::call('migrate', ['--force' => true]);
        } catch (Throwable $e) {
            report($e);
            Log::error('Post-deploy migration failed.');

            // CI logs are public, so return the exception type only; the
            // message (which can name hosts or users) stays in the server log.
            return response()->json(['ok' => false, 'error' => class_basename($e)], 500);
        }

        return response()->json([
            'ok' => $status === 0,
            'output' => trim(Artisan::output()),
        ], $status === 0 ? 200 : 500);
    }
}
