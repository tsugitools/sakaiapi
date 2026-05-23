# sakaiapi

Tsugi tool to exercise Sakai's LTI bearer token (SAT) and `/api` webapi and `/direct` Entity Broker endpoints.

## What it does

1. Launches as a normal LTI 1.3 tool from Sakai.
2. **LTI scope & probe** or **Sakai scope & probe** — POSTs to Sakai's token URL using Tsugi's `LTI13::get_access_token`, then GETs the bearer probe on `/api` or `/direct`.
3. GETs `{issuer}/api/lti/bearer-probe` or `{issuer}/direct/lti/bearer-probe` with `Authorization: Bearer {SAT}`.
4. Shows token JSON, probe JSON, and a debug log.

## Sakai setup

Register Tsugi as an LTI 1.3 platform in Sakai. The issuer record in Tsugi needs:

- **Token URL**: full URL from Sakai registration, e.g. `https://{sakai-host}/imsblis/lti13/token/{tool-id}`
- **Token audience**: same as any other LTI 1.3 token request (Tsugi uses `lti13_token_audience` from the issuer row when set, otherwise the token URL)
- **Keyset / client id**: as for any LTI 1.3 tool

Local dev URLs (adjust host):

| Purpose | URL |
|---------|-----|
| Keyset | `http://localhost:8080/imsblis/lti13/keyset` |
| Token | `POST http://localhost:8080/imsblis/lti13/token/{tool-id}` |
| /api bearer probe | `GET http://localhost:8080/api/lti/bearer-probe` |
| /direct bearer probe | `GET http://localhost:8080/direct/lti/bearer-probe` |

## Optional Tsugi settings

| Setting | Description |
|---------|-------------|
| `sakai_token_scope` | Space-separated OAuth scopes (overrides defaults) |
| `sakai_api_root` | e.g. `https://localhost:8080/api` if issuer URL is not the Sakai webapp root |
| `sakai_direct_root` | e.g. `https://localhost:8080/direct` if issuer URL is not the Sakai webapp root |

| Button | Scope | Target |
|--------|--------|--------|
| LTI scope & /api probe | `https://purl.imsglobal.org/spec/lti-ags/scope/lineitem` | webapi |
| Sakai scope & /api probe | `sakai.lti.api.content.read` (expected to fail until granted in Sakai) | webapi |
| LTI scope & /direct probe | `https://purl.imsglobal.org/spec/lti-ags/scope/lineitem` | Entity Broker |
| Sakai scope & /direct probe | `sakai.lti.api.content.read` | Entity Broker |

`?probe=1` is an alias for `?probe=lti` (/api). Use `?probe=lti-direct` or `?probe=sakai-direct` for `/direct`.

## Related

- Sakai trunk: `SakaiAccessTokenService`, `LtiBearerTokenInterceptor`, `/api/lti/bearer-probe`, `LtiBearerDirectInterceptor`, `/direct/lti/bearer-probe`
- Tsugi: `lib/src/Util/LTI13.php` (`get_access_token`)
