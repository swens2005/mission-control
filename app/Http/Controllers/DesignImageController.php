<?php

namespace App\Http\Controllers;

use App\Models\Design;
use App\Support\Proofmark\DesignFiles;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves a design's image to whoever may see it, in either portal
 * (ADR 0009). Files never live in the web root.
 */
class DesignImageController extends Controller
{
    public function __invoke(Design $design): BinaryFileResponse
    {
        Gate::authorize('view', $design);

        $path = DesignFiles::absolutePath($design);
        abort_unless(is_file($path), 404);

        $response = response()->file($path, [
            'Content-Type' => $design->mime,
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // Only the signed-in browser may keep a copy, never a shared cache.
        // A design's file never changes; a new upload is a new design.
        $response->setPrivate();
        $response->setMaxAge(86400);

        return $response;
    }
}
