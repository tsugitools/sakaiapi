# sakaiapi

Tsugi tool to exercise Sakai's LTI bearer token (SAT) and `/api` webapi endpoints.

## What it does

1. Launches as a normal LTI 1.3 tool from Sakai.
2. On **Get token & probe**, POSTs to Sakai's token URL (from issuer registration) using Tsugi's `LTI13::get_access_token`.
3. GETs `{issuer}/api/lti/bearer-probe` with `Authorization: Bearer {SAT}`.
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
| Bearer probe | `GET http://localhost:8080/api/lti/bearer-probe` |

## Optional Tsugi settings

| Setting | Description |
|---------|-------------|
| `sakai_token_scope` | Space-separated OAuth scopes (overrides defaults) |
| `sakai_api_root` | e.g. `https://localhost:8080/api` if issuer URL is not the Sakai webapp root |

Default scope is a single IMS LTI scope (`lti-ags/scope/lineitem`) to test signing and bearer round-trip. More scopes can be added here after Sakai’s admin UI and webapi enforcement catch up.

## Related

- Sakai trunk: `SakaiAccessTokenService`, `LtiBearerTokenInterceptor`, `/api/lti/bearer-probe`
- Tsugi: `lib/src/Util/LTI13.php` (`get_access_token`)
