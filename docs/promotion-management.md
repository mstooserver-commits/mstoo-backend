# Promotion Management APIs

All responses use the existing Mastoo envelope:

```json
{
  "response_code": "default_200",
  "message": "...",
  "content": {},
  "errors": []
}
```

Pagination uses `limit` (page size) and `offset` (page number). Customer list endpoints default to `limit=10` and `offset=1`.

Admin APIs require `Authorization: Bearer {admin_token}` and the `admin.api` middleware. Promotion modules use the existing `promotion_management` permission (`view`, `create`, `edit`, `delete`). Customer coupon apply/remove and wallet bonus history require a customer Bearer token.

The backend recalculates every discount, coupon, campaign amount, tax, and wallet bonus. Cart, checkout, and booking creation read server-side cart totals. Client-supplied `final_price` / discount amounts are ignored.

---

## Customer APIs

### Active promotional banners

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/banner`
- **Auth:** none
- **Query:** `limit`, `offset` (optional)
- **Returns:** active banners where `is_active=1` and today is inside `start_date`/`end_date` (null dates are always valid)
- **Example:** `GET /api/v1/customer/banner?limit=10&offset=1`

### Active advertisements

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/advertisement`
- **Auth:** none
- **Query:** `limit`, `offset` (optional)
- **Returns:** currently active advertisements only

### Active discounts

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/discount`
- **Auth:** none
- **Query:** `limit`, `offset` (optional)
- **Returns:** `promotion_type=discount`, active, in date range

### Campaigns

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/campaign`
- **Auth:** none
- **Query:** `limit` (required), `offset` (required)
- **Returns:** active campaigns in date range (zone filter applied only when a mapped customer zone exists)

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/campaign/data/items`
- **Query:** `campaign_id` (uuid), `limit`, `offset`

### Coupons

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/coupon`
- **Auth:** optional (pro-member coupons hidden unless the user qualifies)
- **Query:** `limit`, `offset` (required)

- **Method:** `POST`
- **Endpoint:** `/api/v1/customer/coupon/apply`
- **Auth:** customer
- **Body:** `{ "coupon_code": "SAVE10" }`
- **Behavior:** looks up the coupon (case-insensitive), checks status, dates, total usage, per-user usage, coupon type, minimum cart amount, and product/category eligibility, then writes `coupon_discount` / `coupon_code` / tax / `total_cost` on cart rows. Returns server-calculated `coupon_discount` and `cart_total`.
- **Errors:** `default_404` (unknown/inactive/expired), `coupon_not_valid_for_your_cart` (eligibility/usage/minimum)

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/coupon/remove`
- **Auth:** customer
- **Behavior:** clears coupon fields and recalculates tax and line totals

### Wallet bonuses

- **Method:** `GET`
- **Endpoints:** `/api/v1/customer/wallet-bonus`, `/api/v1/customer/wallet-bonus-list`, `/api/v1/customer/wallet/bonus-list`, `/api/v1/customer/bonus-list`
- **Auth:** none
- **Returns:** currently active wallet bonus offers

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/wallet-bonus/history`
- **Auth:** customer
- **Returns:** this user's granted bonus usages and ledger amounts

Wallet bonuses are credited only through `add_fund_transaction()` → `PromotionService::grantAddFundBonus()`. A `transactions` row (`trx_type=wallet_bonus`) is created. The same add-fund transaction cannot receive the bonus twice.

---

## Admin APIs

Common list query: `limit`, `offset`, `status=active|inactive|all`, plus module-specific filters. Search uses `string` as base64-encoded text (existing convention).

### Discounts

| Method | Endpoint | Notes |
| --- | --- | --- |
| GET | `/api/v1/admin/discount` | `discount_type=category\|service\|zone\|mixed\|all` |
| POST | `/api/v1/admin/discount` | create |
| GET | `/api/v1/admin/discount/{id}/edit` | details |
| PUT | `/api/v1/admin/discount/{id}` | update |
| PUT | `/api/v1/admin/discount/status/update` | `{ "status": 1, "discount_ids": [] }` |
| DELETE | `/api/v1/admin/discount/delete` | `{ "discount_ids": [] }` |

**Create body:** `discount_title`, `discount_type`, `discount_amount_type` (`percent`/`amount`), `discount_amount`, `min_purchase`, `max_discount_amount`, `start_date`, `end_date`, `category_ids[]`, `service_ids[]`, `zone_ids[]`.

Validation errors return `default_400` with `errors` from `error_processor`.

### Coupons

| Method | Endpoint | Notes |
| --- | --- | --- |
| GET | `/api/v1/admin/coupon` | `coupon_type` |
| GET | `/api/v1/admin/coupon/config` | coupon type list |
| POST | `/api/v1/admin/coupon` | `coupon_code` unique, stored uppercase |
| GET | `/api/v1/admin/coupon/{id}/edit` | |
| PUT | `/api/v1/admin/coupon/{id}` | |
| PUT | `/api/v1/admin/coupon/status/update` | `{ "coupon_ids": [] }` |
| DELETE | `/api/v1/admin/coupon/delete` | `{ "coupon_ids": [] }` |

Duplicate `coupon_code` fails unique validation.

### Campaigns

| Method | Endpoint |
| --- | --- |
| GET | `/api/v1/admin/campaign` |
| POST | `/api/v1/admin/campaign` |
| GET | `/api/v1/admin/campaign/{id}/edit` |
| PUT | `/api/v1/admin/campaign/{id}` |
| PUT | `/api/v1/admin/campaign/status/update` |
| DELETE | `/api/v1/admin/campaign/delete` |

Campaign discounts are stored as `discounts.promotion_type=campaign` and calculated by the same `PromotionService`.

### Wallet bonuses

| Method | Endpoint |
| --- | --- |
| GET | `/api/v1/admin/wallet-bonus` |
| POST | `/api/v1/admin/wallet-bonus` |
| GET | `/api/v1/admin/wallet-bonus/{id}/edit` |
| PUT | `/api/v1/admin/wallet-bonus/{id}` |
| PUT | `/api/v1/admin/wallet-bonus/status/update` | `{ "wallet_bonus_ids": [] }` |
| DELETE | `/api/v1/admin/wallet-bonus/delete` | `{ "wallet_bonus_ids": [] }` |

**Create body:**

```json
{
  "bonus_title": "Add money bonus",
  "description": "20% extra",
  "bonus_amount_type": "percent",
  "bonus_amount": 20,
  "min_add_money_amount": 100,
  "max_bonus_amount": 50,
  "usage_limit": 0,
  "per_user_limit": 1,
  "start_date": "2026-08-01",
  "end_date": "2026-12-31"
}
```

`usage_limit` / `per_user_limit` of `0` means unlimited.

### Advertisements

| Method | Endpoint |
| --- | --- |
| GET | `/api/v1/admin/advertisement` | `resource_type=category\|service\|campaign\|link\|all` |
| POST | `/api/v1/admin/advertisement` | multipart, `image` required |
| GET | `/api/v1/admin/advertisement/{id}/edit` |
| PUT | `/api/v1/admin/advertisement/{id}` |
| PUT | `/api/v1/admin/advertisement/status/update` | `{ "advertisement_ids": [] }` |
| DELETE | `/api/v1/admin/advertisement/delete` | `{ "advertisement_ids": [] }` |

**Create fields:** `title`, `description`, `resource_type`, `resource_id`, `redirect_link`, `sort_order`, `start_date`, `end_date`, `image`.

### Promotional banners

| Method | Endpoint |
| --- | --- |
| GET | `/api/v1/admin/banner` |
| POST | `/api/v1/admin/banner` |
| GET | `/api/v1/admin/banner/{id}/edit` |
| PUT | `/api/v1/admin/banner/{id}` |
| PUT | `/api/v1/admin/banner/status/update` | `{ "banner_ids": [] }` |
| DELETE | `/api/v1/admin/banner/delete` | `{ "banner_ids": [] }` |

Additional fields: `description`, `start_date`, `end_date`, `sort_order`. Images use the existing `file_uploader('banner/', ...)`.

---

## Admin web

Sidebar **Promotion Management**: Discounts, Coupons, Wallet Bonus, Campaigns, Advertisements, Promotional Banners. Middleware: `admin` + `mpc:promotion_management`.

---

## Cart / checkout / orders

1. Cart add/update uses `basic_discount_calculation` and `campaign_discount_calculation`, which call `PromotionService::discountAmount`.
2. Coupon apply writes server-calculated line totals.
3. `BookingTrait` copies cart `coupon_discount`, discounts, tax, and totals into the booking. It does not accept a client final price.

---

## Example coupon apply response

```json
{
  "response_code": "default_200",
  "message": "successfully loaded",
  "content": {
    "coupon_discount": 50,
    "cart_total": 450,
    "coupon_code": "SAVE10"
  },
  "errors": null
}
```
