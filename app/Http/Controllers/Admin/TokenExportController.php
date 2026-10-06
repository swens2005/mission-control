<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandKit;
use App\Support\Palette\TokenExporter;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Downloads a kit export as a file (story 23).
 */
class TokenExportController extends Controller
{
    public function __invoke(BrandKit $kit, string $format): Response
    {
        Gate::authorize('view', $kit);
        abort_unless(array_key_exists($format, TokenExporter::FORMATS), 404);

        $kit->load(['colors', 'project']);

        return response(TokenExporter::export($kit, $format), 200, [
            'Content-Type' => TokenExporter::FORMATS[$format]['mime'].'; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.TokenExporter::filename($kit, $format).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
