<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * A strict Content-Security-Policy for every HTML page.
 *
 * Scripts must come from this origin and carry the per-request nonce that
 * Vite adds to its tags. No inline styles or scripts without it; React's
 * `style` prop is fine because it writes through the CSSOM, not markup.
 *
 * The policy is sent twice, on purpose:
 *  - as a header (everything, including frame-ancestors), and
 *  - as a <meta http-equiv> tag in the page (everything except
 *    frame-ancestors, which browsers ignore in meta tags).
 * On codelaunch.nl, LiteSpeed replaces our header with the portfolio's
 * root .htaccess one (frame-ancestors 'none' only), so the meta tag is what
 * carries the policy there. Browsers enforce every policy they get.
 * See docs/decisions/0006-csp-delivery.md.
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();
        $directives = $this->directives($nonce);

        View::share('contentSecurityPolicy', $this->compile(
            array_diff_key($directives, ['frame-ancestors' => true]),
        ));

        $response = $next($request);

        if (str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')) {
            $response->headers->set('Content-Security-Policy', $this->compile($directives));
        }

        return $response;
    }

    /**
     * @return array<string, list<string>>
     */
    private function directives(string $nonce): array
    {
        $dev = $this->viteDevServer();

        return [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", ...$dev],
            // The dev server injects <style> tags for hot reloading.
            'style-src' => ["'self'", "'nonce-{$nonce}'", ...($dev ? [...$dev, "'unsafe-inline'"] : [])],
            'img-src' => ["'self'", 'data:'],
            'font-src' => ["'self'", ...$dev],
            'connect-src' => ["'self'", ...$dev, ...array_map(fn (string $origin) => (string) preg_replace('/^http/', 'ws', $origin), $dev)],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];
    }

    /**
     * @param  array<string, list<string>>  $directives
     */
    private function compile(array $directives): string
    {
        return collect($directives)
            ->map(fn (array $sources, string $directive) => $directive.' '.implode(' ', $sources))
            ->implode('; ');
    }

    /**
     * The Vite dev server's origin while `npm run dev` is running locally.
     *
     * @return list<string>
     */
    private function viteDevServer(): array
    {
        if (app()->isProduction() || ! Vite::isRunningHot()) {
            return [];
        }

        $url = trim((string) file_get_contents(public_path('hot')));
        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return [];
        }

        return [$parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '')];
    }
}
