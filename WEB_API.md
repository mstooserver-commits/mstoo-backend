# MSTOO Web API Guide (Next.js)

Additive APIs for the Next.js website. Flutter contracts remain unchanged.

## Base URL

- Production: `https://api.mstoo.co.in/api/v1`
- Local: `{APP_URL}/api/v1`

## CORS / Origins

Set in `.env`:
php artisan tinker --execute="dump(\Illuminate\Support\Facades\Http::withToken(config('services.whatsapp.access_token'))->get('https://graph.facebook.com/v24.0/1427483248955077/message_templates', ['name' => 'mstoo_otp_verification'])->json());"
```
FRONTEND_URL=https://mstoo.co.in
ADMIN_URL=https://admin.mstoo.co.in
CORS_ALLOWED_ORIGINS=http://localhost:3000,https://www.mstoo.co.in
CORS_SUPPORTS_CREDENTIALS=false
```

Allowed headers include: `Authorization`, `Content-Type`, `Accept`, `X-localization`, `zoneid`, `zoneId`, `zone-id`.

Auth for web uses **Passport Bearer tokens** (same as Flutter). Cookie/SPA Sanctum is optional and off by default.

## Required zone headers

Location-first listings depend on zone:

1. Resolve zone:

```
GET /customer/config/get-zone-id?lat=18.52&lng=73.85
```

2. Send on subsequent calls (any of):

- Header `zoneid: {uuid}`
- Header `zoneId` / `zone-id`
- Query/body `zone_id`

Optional location extras: `latitude`/`lat`, `longitude`/`lng`/`long`, `radius` (km).

## New web endpoints

### Aggregated homepage

```
GET /customer/web/home?zone_id={uuid}&lat=&lng=&radius=&limit=12
```

Returns:

```json
{
  "response_code": "default_200",
  "content": {
    "zone": {"id": "...", "name": "..."},
    "banners": [],
    "categories": [],
    "featured_categories": [],
    "featured_ads": [],
    "popular_ads": [],
    "latest_ads": [],
    "campaigns": []
  }
}
```

Ads include `display_price`, `cover_image_full_url`, `slug` (after migration).

### Sitemap / ISR

```
GET /customer/web/sitemap-services?page=1&limit=100&zone_id=
```

Returns `{ total, page, limit, last_page, data: [{ id, slug, name, updated_at }] }`.

Omit `zone_id` for full-site sitemap.

## Existing listing APIs (reuse)

All under `/customer/service*`, `/customer/category*`, `/customer/banner`, `/customer/campaign`.

Detail accepts **UUID or slug**:

```
GET /customer/service/detail/{idOrSlug}
```

SEO block added on detail:

```json
"seo": {
  "title": "...",
  "description": "...",
  "image": "https://.../storage/app/public/service/xxx.jpg",
  "canonical_path": "/ads/{slug}",
  "slug": "honda-city-for-rent"
}
```

## Auth

```
POST /customer/auth/login
{
  "email_or_phone": "9876543210",
  "password": "...",
  "device_token": "optional-web-fcm-or-empty"
}
```

Phone accepted as `10 digits`, `91XXXXXXXXXX`, `+91XXXXXXXXXX`.

Login content now includes:

```json
{
  "token": "...",
  "is_active": 1,
  "user": { "id": "...", "first_name": "...", "phone": "...", "email": "..." }
}
```

OTP:

```
POST /user/verification/send-otp
{ "identity": "9876543210", "identity_type": "phone", "signature_id": "optional" }
```

`signature_id` is optional for web (Android SMS retriever). Empty does not hard-fail validation.

## Config extras

`GET /customer/config` now also returns:

- `frontend_url`, `admin_url`
- `base_url` from `APP_URL` (no longer hardcoded only)
- `seo.site_title`, `seo.site_description`, `seo.og_image`
- existing feature flags used by Flutter

## Payments (web)

Wallet:

```
POST /customer/wallet/add-fund
Authorization: Bearer {token}
{ "amount": 100, "payment_method": "razor_pay", "callback": "https://mstoo.co.in/wallet/result", "payment_platform": "web" }
```

Response includes `payment_url` and `redirect_url` for browser redirect.

Pro member:

```
POST /customer/pro-member/purchase
{ "plan_id": "...", "payment_method": "razor_pay", "callback": "https://mstoo.co.in/pro/result", "payment_platform": "web" }
```

Callbacks redirect to your `callback` with `?payment_status=success|failed|cancelled`.

## Media

Images are stored under `storage/app/public/{service|category|provider}/`.

Absolute URL pattern:

```
{APP_URL}/storage/app/public/service/{filename}
```

Provider `add_service` now auto-creates folders and validates image mime/size; returns JSON 400 instead of 500 on bad uploads.

## Non-breaking notes

- Prefer additive fields only
- Flutter can ignore `user`, `seo`, `slug`, `frontend_url`, `redirect_url`
- Keep `/api/v1` unless a true breaking change needs `/api/v2`

## Env checklist (production)

```
FRONTEND_URL=https://mstoo.co.in
ADMIN_URL=https://admin.mstoo.co.in
CORS_ALLOWED_ORIGINS=https://mstoo.co.in,https://www.mstoo.co.in
APP_URL=https://api.mstoo.co.in
```

Then:

```
php artisan migrate
php artisan storage:link
php artisan config:clear
mkdir -p storage/app/public/{service,category,provider}
chmod -R ug+rwx storage/app/public
```
