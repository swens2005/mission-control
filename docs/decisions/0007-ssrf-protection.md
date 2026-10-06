# 0007. Fetching user-supplied URLs safely (SSRF)

- Status: accepted
- Date: 2026-10-06

## Context

Launch Control's automated checks fetch a site URL that a user typed in.
Server-side request forgery (SSRF) is the classic risk: a URL like
`http://127.0.0.1/`, `http://169.254.169.254/` (cloud metadata) or a hostname
that resolves to a private address makes our server read things on its own
network and show the result back. Variants include redirects to private
addresses, DNS rebinding (a hostname that resolves to a public address when
checked and a private one when fetched), odd IP spellings (`2130706433`,
`0177.0.0.1`, `::ffff:127.0.0.1`) and slow or huge responses that tie up a
shared-hosting PHP worker.

The public demo makes anyone on the internet a user, so this also has to
stop the app being used as a free request relay against third parties.

Whether the shared host allows outbound HTTP(S) at all was never verified.
A token-protected diagnostic was built but not run (calling it needed the
deploy token, which by design nobody holds); the first production check
run against codelaunch.nl is the test instead. A failure there shows up as
a plain "couldn't connect" result, not a crash.

## Decision

One class, `App\Support\Http\SafeFetcher`, is the only way the app fetches a
user-supplied URL. It uses Laravel's HTTP client (Guzzle on curl) so feature
tests can fake responses, and it:

1. **Allows only `http` and `https` on ports 80 and 443**, with no
   credentials in the URL. Hosts that look numeric but aren't a normal
   dotted IPv4 address (decimal, octal or hex forms) are refused.
2. **Resolves the host first**, through a `Resolver` interface (system DNS in
   production, a fake in tests), A and AAAA records. **Every** address must be
   public: not private, loopback, link-local (including `169.254.169.254`),
   CGNAT, multicast, reserved, documentation or unspecified ranges, in IPv4
   or IPv6, and IPv4-mapped/NAT64 IPv6 forms are unwrapped and checked as
   IPv4. One bad address refuses the whole host.
3. **Pins the connection** to the checked address with curl's
   `CURLOPT_RESOLVE`, so DNS can't change between the check and the
   connection. If curl isn't available, it refuses to fetch rather than fall
   back to an unpinned handler (fail closed).
4. **Follows redirects by hand**, at most 5, and repeats steps 1 to 3 for
   every hop.
5. **Limits each request:** 5 s to connect, 10 s in total, and 2 MB of body;
   the download is aborted once the limit is passed.
6. **Identifies itself** with an honest User-Agent that links to the app.

Around it, Launch Control adds:

- **Rate limits:** 5 check runs per user per 10 minutes, and per launch.
- **Demo sandboxes can only check `codelaunch.nl`.** The demo moment needs
  nothing else, and it stops 200 anonymous sandboxes from aiming the server
  at arbitrary sites. Real workspaces can check any public site.
- **No response bodies are shown back** to the user. Checks report what they
  found ("title is 72 characters"), not the fetched content.

## Consequences

- Every check is unit tested with fixture HTML and headers; `SafeFetcher` is
  unit tested with a fake resolver, so the test suite never touches the
  network.
- Sites that are only reachable over IPv6, on custom ports, or behind HTTP
  authentication can't be checked. Acceptable for public launch checks.
- If the host blocks outbound traffic, the automated checks can't run there
  until the host allows it; the manual checklist and go/no-go board still
  work.
