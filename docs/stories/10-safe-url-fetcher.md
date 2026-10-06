# 10. Safe URL fetcher (SSRF protection)

As the studio, I want every outside request the app makes to go through one
guarded client, so a user-supplied URL can never be used to reach the
server's own network or tie up the host.

ADR 0007 (SSRF protection) is written and accepted before any fetching code.

## Acceptance criteria

- [x] `App\Support\Http\SafeFetcher` is the only way the app fetches
      user-supplied URLs. It returns a small response object (final URL,
      status, headers, body, timings, redirect chain) or a clear error.
- [x] Only `http` and `https`, ports 80 and 443. No credentials in the URL.
- [x] The host is **resolved first**; every resolved IPv4 and IPv6 address
      must be public. Blocked: private (10/8, 172.16/12, 192.168/16, fc00::/7),
      loopback, link-local (169.254/16 including cloud metadata, fe80::/10),
      CGNAT 100.64/10, multicast, reserved, unspecified, and IPv4-mapped IPv6
      forms of all of these. Literal IP hosts get the same check.
- [x] The connection is **pinned** to the checked IP (curl `CURLOPT_RESOLVE`),
      so a DNS answer can't change between the check and the request (DNS
      rebinding).
- [x] Redirects are followed by hand, at most 5, and **every hop** is
      re-checked as above.
- [x] Limits: 5 s connect timeout, 10 s total per request, 2 MB response cap
      (the download is aborted past it, not truncated after).
- [x] Sends an honest User-Agent, with a link back to the app:
      `MissionControl-LaunchCheck/1.0 (+https://codelaunch.nl/mission-control)`
- [x] DNS resolution sits behind a small `Resolver` interface so tests never
      touch the network.

## Tests (unit)

- [x] Every blocked range, IPv4 and IPv6, including `::ffff:127.0.0.1`,
      `0.0.0.0`, `[::1]`, decimal/octal IP forms (`2130706433`, `0177.0.0.1`).
- [x] A hostname that resolves to one public and one private address is
      refused.
- [x] A redirect to a private address, and a redirect loop, are refused.
- [x] Non-http schemes, other ports and `user:pass@` URLs are refused.
- [x] Responses over the size cap fail cleanly.

## Notes

- gzip responses are unpacked by the app, not curl, with the same 2 MB cap,
  so a small compressed "zip bomb" can't expand in memory.
- Guzzle only accepts allow-listed curl options: pinning uses
  `CURLOPT_RESOLVE` (allowed); the size cap uses Guzzle's `progress` option
  and the scheme limit its `protocols` option.
- Checked against the real network from a development machine (not part of
  the test suite): codelaunch.nl over https and its http→https redirect,
  `localhost` refused, a 14.7 MB image aborted after 0.12 s, a gzip file
  that unpacks past 2 MB refused, and pinning proven by pointing
  codelaunch.nl at another server's address (the TLS check then fails).
