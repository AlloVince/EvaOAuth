# Future work

Non-blocking ideas recorded during the 2.0.0 acceptance pass. None of these is required for 2.0.0 and
none is scheduled; each needs its own acceptance criteria before it can be implemented.

## Providers and protocols

- A built-in provider for a widely used OAuth2 service, added only with a wire-level test fixture and
  an identity-mapping decision. The generic `Provider\OAuth2Provider` already covers the common case.
- `Token::expiresAt` convenience for OAuth1, which has no expiry concept; today `null` is the only
  correct value.
- Optional OAuth2 device-authorization grant. It is deliberately out of scope: 2.0 supports the
  Authorization Code (+ PKCE) and refresh grants only.

## Diagnostics

- A per-call correlation id passed by the application into the trace context, so several concurrent
  logins can be told apart without enabling request logging.
- Optional per-stage timing for the whole `callback()` call (currently each event reports its own
  `duration_ms`).
- Structured events for `authorize()` outcomes, which today produce no HTTP event because no request
  is made; the transaction creation itself is not logged.

## Application ergonomics

- A `login()`-style helper that combines `authorize()` and the browser redirect. It was left out
  because the library deliberately sends no headers.
- A documented recipe for a shared cache-backed `StateStore` for multi-node deployments. The
  interface is public; no implementation is shipped.
- First-class revocation support (`revoke()`/introspection) as a separate API, not an implicit part of
  `refresh()`.

## Known limitations kept for 2.0

- GitHub private email is not fetched automatically; applications that need it must request
  `/user/emails` themselves with `request()`.
- The OAuth1 adapter accepts a documented subset of legal request shapes and rejects ambiguous ones
  (duplicate names, query/form collisions, bracketed names, caller-supplied `oauth_*` fields,
  pre-existing `Authorization` headers, non-seekable or non-form bodies).
- Injected PSR-18 clients are trusted: the library cannot stop a misconfigured client from following
  a redirect that already exposed credentials.
