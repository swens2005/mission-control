<?php

namespace App\Http\Controllers;

use App\Models\BrandKit;
use App\Support\Palette\PalettePresenter;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * The read-only style guide behind a public link (story 24). No login:
 * the 40-character random token is the key, and the studio can turn it
 * off (or get a new one) at any time.
 */
class PublicStyleGuideController extends Controller
{
    public function __invoke(string $token): Response
    {
        abort_unless(strlen($token) === 40 && ctype_alnum($token), 404);

        // Nobody is signed in, so no workspace scope applies; the token
        // alone picks the kit.
        $kit = BrandKit::withoutGlobalScopes()
            ->where('public_token', $token)
            ->whereNotNull('shared_at')
            ->with(['colors' => fn ($query) => $query->withoutGlobalScopes(), 'project' => fn ($query) => $query->withoutGlobalScopes()])
            ->first();

        abort_if($kit === null, 404);

        $response = Inertia::render('public/style-guide', [
            'projectName' => $kit->project->name,
            'guide' => PalettePresenter::styleGuide($kit),
        ])->toResponse(request());

        // Shared by link, not published: keep it out of search engines,
        // and don't leak the token to sites it links to.
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
