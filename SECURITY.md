# Security Policy

## Supported versions

| Version | PHP    | Supported |
| ------- | ------ | --------- |
| 2.0.x   | 8.2–8.5 | Yes       |
| 1.x     | —      | No        |

1.x is unsupported and unmaintained; it lacks state validation, PKCE, single-use transactions and
safe diagnostics. Upgrade to 2.x — see [UPGRADING.md](UPGRADING.md). Security fixes are released for
the latest 2.x only; there is no backport policy for older 2.0 patch releases.

## Reporting a vulnerability

**Do not open a public issue for a security problem.**

Report it privately through GitHub's private reporting form:
[github.com/AlloVince/EvaOAuth/security/advisories/new](https://github.com/AlloVince/EvaOAuth/security/advisories/new)
(choose "Report a vulnerability"). If that form is unavailable, open a regular issue that contains
only a one-line pointer such as "private report available on request", with no technical detail.

Please include: affected version and PHP version, the exact request/response shape or API call, the
impact you believe it has, and whether it is already known to you through a third party. Expect an
acknowledgement within a few days. Please allow a reasonable window for a fix and a release before
disclosure. Do not include secrets, tokens or working exploits against third-party accounts.

**Never put any of the following in an issue, pull request, log excerpt or test fixture:**

- client ids/secrets, refresh tokens, access tokens, OAuth1 token secrets, PKCE verifiers;
- raw callback query strings or `Location` headers containing `code`, `state`, `oauth_token` or
  `oauth_verifier`;
- full HTTP traces, request/response bodies, `Authorization` headers, cookies or session dumps.

The library's own logging is metadata-only by design (`provider`, `stage`, `method`, `status`,
`category`, `duration_ms`) and is safe to attach to a report; a provider's own error text is not.

## Scope

EvaOAuth is a library, so most deployment and policy questions are yours: TLS termination, session
backend and locking, encrypted token storage, refresh concurrency, account linking, CSRF on local
actions, log retention, provider registration and scope review, and incident response. A report is
most useful when it concerns behaviour inside `lib/` — state/PKCE handling, callback validation,
token parsing or binding, request signing, credential redaction, or origin policy. A finding that
requires an application to misconfigure its own trust boundary (for example pointing a provider at an
attacker-controlled host) is documented behaviour, not a vulnerability.
