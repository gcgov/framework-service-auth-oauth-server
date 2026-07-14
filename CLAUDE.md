# CLAUDE.md — gcgov/framework-service-auth-oauth-server

A **framework-service plugin** for `gcgov/framework`. Read the framework's own `CLAUDE.md` first for the
plugin/router/lifecycle model — this file only covers what this plugin adds. See `README.md` for prose.

## Purpose
Turns a framework app into a **full OAuth server**. Authenticates users by **username/password** (against the
Mongo user collection) or via **third-party OAuth providers** (HybridAuth), issues framework **access + refresh
JWTs**, and enforces **TOTP multi-factor auth**. It also installs a **global authentication guard** over every
app route marked `authentication: true`.

Namespace / PSR-4: `gcgov\framework\services\authoauth\` → `src/`. Composer type: `framework-service`.

## Install & register
```php
// \app\app::registerFrameworkServiceNamespaces()
return [ '\gcgov\framework\services\authoauth' ];
```
`composer require gcgov/framework-service-auth-oauth-server`. Requires ext `mongodb`, `sodium`, `imagick`, and
`robthree/twofactorauth` + `bacon/bacon-qr-code` (MFA QR codes), `hybridauth/hybridauth` (third-party OAuth),
`lcobucci/jwt`. Use **either** this plugin **or** `auth-ms-front`, not both.

## Routes added (`src/router.php`, all prefixed with `environment.getBasePath()`)
| Method | Path | Controller method | Auth | Purpose |
|--------|------|-------------------|------|---------|
| GET | `/.well-known/jwks.json` | `auth::jwks` | no | Public keys for front-end JWT validation. |
| GET | `/.well-known/openid-configuration` | `auth::openId` | no | Public OIDC discovery doc. |
| POST | `/auth/authorize` | `auth::oauthPostAuthorize` | no | **Token endpoint** — grant dispatch (see below). |
| GET | `/auth/authorize` | `auth::oauthGetAuthorize` | no | Start interactive/redirect auth. |
| GET | `/auth/hybridauth/{provider}` | `auth::oauthHybridAuth` | no | Third-party OAuth return endpoint. |
| GET | `/auth/out` | `auth::out` | yes | Sign out: kill refresh token, clear session/cookie. |
| GET | `/auth/fileToken` | `auth::fileToken` | yes | Mint a short-lived token usable as `?fileAccessToken=`. |
| POST | `/auth/verifyMfaSecret` | `auth::verifyMfaSecret` | yes | Validate a TOTP code and save the user's MFA secret (enrollment). |
| POST | `/auth/verifyMfaCode` | `auth::verifyMfaCode` | yes | Validate a TOTP code for an already-enrolled user. |

## Token endpoint — `POST /auth/authorize`
Reads the body via `request::getPostData()`. Requires `client_id` == `app.json → app.guid`, and a
`grant_type`. Dispatches by grant:
- **`password`** — body `scope=login`, `username`, `password`. Verifies via
  `request::getUserClassFqdn()::verifyUsernamePassword()`. If the user requires MFA, returns a
  `configureMfaResponse` (not yet enrolled) or `requireMfaResponse` (enrolled) carrying a **restricted** access
  token (roles emptied) so the client can complete MFA before receiving a full token.
- **`refresh_token`** — exchanges a valid refresh token for a new access token.
- **`authorization_code`** — completes an authorization-code flow.

Success returns `stdAuthResponse`: `{ token_type: "Bearer", expires_in, access_token, refresh_token }`.
Access/refresh tokens are minted by `\gcgov\framework\services\jwtAuth\jwtAuth`.

## Authentication guard (`router::authentication()`)
Runs for **every** app route with `authentication: true` (framework merges this guard automatically). It:
1. Reads the JWT from `Authorization: Bearer …`; if absent and the route has `allowShortLivedUrlTokens`,
   falls back to `?fileAccessToken=`; otherwise `401`.
2. Validates the token with `jwtAuth::validateAccessToken()`; parse/validation failure → `401`.
3. Populates the request user: `request::getAuthUser()->setFromJwtToken($data, $scopes)`.
4. Enforces `routeHandler->requiredRoles`: any missing role → `403`.

To bypass this guard for specific routes, `\app\router` can implement
`getRunFrameworkServiceRouteAuthentication($routeHandler): bool` and return `false` for them.

## Configuration — `oauthConfig` singleton (`src/oauthConfig.php`)
Tweak in `\app\app::_before()`:
```php
$c = \gcgov\framework\services\authoauth\oauthConfig::getInstance();
$c->setBlockNewUsers(false, ['Role1.Read']);   // allow auto-provisioning of new users + their default roles
$c->setAuthorizeUrlParameters([...]);          // extra params forwarded on the authorize redirect
```
- `blockNewUsers` (default **true**): if true, only users already in the DB may sign in. Set false to
  auto-create a DB user for anyone who passes third-party OAuth, assigning `defaultNewUserRoles`.
- JWT issuer/audience/redirects come from `environment.json → jwtAuth`. JWT signing keys live under the
  framework's `srv/jwtCertificates` (generate with the framework's `scripts/create-jwt-keys.ps1`).

## MFA (`src/services/multifactor.php`, `src/models/*`)
TOTP via `robthree/twofactorauth`; enrollment QR via `bacon/bacon-qr-code` (needs ext-imagick). Models:
`stdAuthResponse`, `requireMfaResponse`, `configureMfaResponse`, `verifyMfaCodeRequest`,
`verifyMfaSecretRequest`. `app.json → settings.forceMfaForPasswordUsers` and the user model's
`mfaRequired`/`mfaConfigured` drive whether MFA is demanded. Enrollment: sign in → `configureMfaResponse` →
`POST /auth/verifyMfaSecret`. Subsequent logins: `requireMfaResponse` → `POST /auth/verifyMfaCode`.

## User model
Operates on the class from `request::getUserClassFqdn()` (`\app\models\user` if present, else
`\gcgov\framework\services\mongodb\models\auth\user`), which must implement
`\gcgov\framework\interfaces\auth\user` (`verifyUsernamePassword`, `getFromOauth`, roles, MFA fields).

## When editing this plugin
- Keep lowercase class/file names. New endpoints: add to `router::getRoutes()` **and** a method on
  `\gcgov\framework\services\authoauth\controllers\auth`, returning a `controllerResponse`.
- Preserve the `stdAuthResponse` body shape — front-end clients depend on it.
- OpenAPI annotations on controller methods are consumed by the documentation plugin; keep them accurate.
- `composer ci` (phpstan + phpunit) before pushing.
