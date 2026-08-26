# Subscription, Wallet, Loyalty & Newsletter APIs

All responses use the existing Mastoo envelope:

```json
{
  "response_code": "default_200",
  "message": "...",
  "content": {},
  "errors": []
}
```

Pagination uses `limit` (page size) and `offset` (page number). Admin APIs require `Authorization: Bearer {admin_token}` and the `admin.api` middleware. Customer purchase, wallet, and loyalty endpoints require a customer Bearer token.

Subscription reuses **Pro Member Management** (`pro_member_plans`, `pro_memberships`, `pro_member_config`). Wallet and loyalty reuse `users.wallet_balance`, `users.loyalty_point`, `transactions`, and `loyalty_point_transactions`. Newsletter adds `newsletter_subscribers`.

The backend calculates wallet balances, subscription prices, payment status, and loyalty conversion amounts. Client-supplied balances and payment-success flags are ignored.

---

## Customer APIs

### Subscription packages

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/subscription/packages` (alias: `/api/v1/customer/pro-member/plans`)
- **Auth:** none
- **Returns:** active packages when subscriptions are enabled
- **Example response content:**

```json
[
  {
    "id": "uuid",
    "name": "Mastoo Premium",
    "price": 499,
    "payable_price": 499,
    "duration_days": 30,
    "duration_unit": "day",
    "duration_value": 30,
    "trial_days": 0,
    "benefits": ["discount", "coupon", "service_fee"],
    "features": ["Exclusive discounts", "Loyalty bonus"],
    "wallet_bonus": 0,
    "loyalty_multiplier": 1,
    "currency_code": "INR"
  }
]
```

### Package details

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/subscription/packages/{id}`
- **Auth:** none
- **Errors:** `default_404` if the package is missing or inactive

### Subscription config

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/pro-member/config`
- **Auth:** optional customer token
- **Returns:** enabled flags, current membership, configured benefits

### Current subscription

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/subscription/current`
- **Auth:** customer
- **Returns:** `is_pro_member`, membership, checkout adjustments preview

### Purchase / renew

- **Method:** `POST`
- **Endpoint:** `/api/v1/customer/subscription/purchase` (renew alias: `/renew`)
- **Auth:** customer
- **Body:**

```json
{
  "plan_id": "uuid",
  "payment_method": "wallet_payment",
  "callback": "https://app.example/subscription-callback"
}
```

- **Validation:** `plan_id` uuid required, `payment_method` in `wallet_payment,razor_pay`, `callback` optional URL
- **Wallet:** server checks balance, debits wallet, writes a `pro_membership` ledger row, then activates inside one DB transaction
- **Razorpay:** creates a pending membership and returns `payment_url`. Activation happens only after gateway amount verification and capture
- **Errors:** `default_403` if purchase disabled, `default_404` unknown/inactive package, `default_400` insufficient wallet or renewal disabled

### Subscription history

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/subscription/history`
- **Auth:** customer
- **Query:** `limit`, `offset` (optional, default 10 / 1)

### Cancel subscription

- **Method:** `POST`
- **Endpoint:** `/api/v1/customer/subscription/cancel`
- **Auth:** customer
- **Errors:** `default_404` no active/pending subscription, `default_400` if cancellation is disabled

### Wallet history

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/wallet-transaction`
- **Auth:** customer
- **Query:** `limit` (required), `offset` (required)
- **Returns:** server-side `wallet_balance` plus paginated ledger rows

### Add fund to wallet

- **Method:** `POST`
- **Endpoint:** `/api/v1/customer/wallet/add-fund`
- **Auth:** customer
- **Body:**

```json
{
  "amount": 500,
  "payment_method": "razor_pay",
  "callback": "https://app.example/wallet-callback"
}
```

- **Validation:** `amount` > 0, `payment_method` = `razor_pay`
- **Returns:** pending request and `payment_url`. Credit happens only after Razorpay amount verification. Duplicate gateway IDs are rejected.

### Loyalty history

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/loyalty-point-transaction`
- **Auth:** customer
- **Query:** `limit`, `offset` (required)

### Convert loyalty points to wallet

- **Method:** `POST`
- **Endpoint:** `/api/v1/customer/loyalty-point/wallet-transfer`
- **Auth:** customer
- **Body:** `{ "point": 100 }`
- **Behavior:** validates available points and `min_loyalty_point_to_transfer`, converts using `loyalty_point_value_per_currency_unit`, then deducts points and credits wallet in one DB transaction
- **Errors:** `default_400` insufficient points or below minimum

### Newsletter subscribe

- **Method:** `POST`
- **Endpoint:** `/api/v1/customer/newsletter/subscribe`
- **Auth:** none (optional customer token links `user_id`)
- **Body:** `{ "email": "user@example.com" }`
- **Validation:** valid email. Emails are stored lowercase. Duplicate subscribed emails return the existing record.

### Newsletter unsubscribe

- **Method:** `POST`
- **Endpoint:** `/api/v1/customer/newsletter/unsubscribe`
- **Auth:** none
- **Body:** `{ "email": "user@example.com" }`
- **Errors:** `default_404` if the email is not on the list

### Newsletter status

- **Method:** `GET`
- **Endpoint:** `/api/v1/customer/newsletter/status`
- **Auth:** none
- **Query:** `email` (required unless the customer is authenticated)
- **Returns:** `{ "email", "subscribed": 0|1, "status", "subscriber" }`

---

## Admin APIs

Admin APIs use existing permissions:

- Subscription: `pro_member_management` (`view`, `manage_plans`, `manage_settings`, `edit`)
- Wallet: `customer_management.manage_wallet` / `view`
- Loyalty: `customer_management.view`
- Newsletter: `newsletter_management` (`view`, `create`, `edit`, `delete`)

### Subscription packages

| Method | Endpoint | Notes |
| --- | --- | --- |
| GET | `/api/v1/admin/subscription-package` | Query: `limit`, `offset`, `status=active\|inactive\|all`, `string` (base64 search) |
| POST | `/api/v1/admin/subscription-package` | Create package |
| GET | `/api/v1/admin/subscription-package/{id}` | View |
| PUT | `/api/v1/admin/subscription-package/{id}` | Update |
| PUT | `/api/v1/admin/subscription-package/status/update` | Body: `{ "status": 0\|1, "plan_ids": [] }` |
| DELETE | `/api/v1/admin/subscription-package/delete` | Body: `{ "plan_ids": [] }` |

Create/update body:

```json
{
  "name": "Mastoo Premium",
  "description": "Premium benefits",
  "price": 499,
  "discounted_price": 399,
  "duration_unit": "day",
  "duration_value": 30,
  "trial_days": 0,
  "wallet_bonus": 50,
  "loyalty_multiplier": 1.5,
  "sort_order": 1,
  "benefits": ["discount", "coupon", "service_fee", "wallet_bonus", "loyalty"],
  "features": ["Exclusive discounts", "Loyalty bonus"]
}
```

### Subscribers

| Method | Endpoint | Notes |
| --- | --- | --- |
| GET | `/api/v1/admin/subscription-subscriber` | Query: `limit`, `offset`, `status`, `plan_id`, `from_date`, `to_date`, `date_type`, `string` |
| GET | `/api/v1/admin/subscription-subscriber/{id}` | Customer, package, payment, dates |
| POST | `/api/v1/admin/subscription-subscriber/{id}/cancel` | Allowed when cancellation is enabled |

Statuses: `pending`, `active`, `expired`, `cancelled`, `suspended`.

### Subscription settings

| Method | Endpoint |
| --- | --- |
| GET | `/api/v1/admin/subscription-settings` |
| PUT | `/api/v1/admin/subscription-settings` |

Update body keys (all optional): `enabled`, `purchase_enabled`, `allow_renewal`, `allow_cancellation`, `trial_enabled`, `auto_renew`, `notify_email`, `grace_period_days`, `reminder_days`, `default_service_fee`. Stored in `business_settings` as `pro_member_config`.

### Wallet

| Method | Endpoint | Notes |
| --- | --- | --- |
| POST | `/api/v1/admin/wallet/add-fund` | Body: `{ "user_id", "amount", "reference?" }`. Credits wallet and writes `fund_by_admin` ledger. |
| GET | `/api/v1/admin/wallet-transaction` | Query: `limit`, `offset`, `customer_id`, `transaction_type`, `from_date`, `to_date`, `min_amount`, `max_amount` |

### Loyalty

| Method | Endpoint | Notes |
| --- | --- | --- |
| GET | `/api/v1/admin/loyalty-point-transaction` | Query: `limit`, `offset`, `customer_id`, `transaction_type`, `from_date`, `to_date` |

### Newsletter

| Method | Endpoint | Notes |
| --- | --- | --- |
| GET | `/api/v1/admin/newsletter-subscriber` | Query: `limit`, `offset`, `status`, `from_date`, `to_date` |
| POST | `/api/v1/admin/newsletter-subscriber` | Body: `{ "email" }` |

---

## Admin panel

Existing Pro Member screens are labeled **Subscription Management**:

- Subscription Package → `/admin/pro-member/plans`
- Subscriber List → `/admin/pro-member/members`
- Settings → `/admin/pro-member/settings`

Customer Wallet and Loyalty reuse `/admin/customer/wallet/*` and `/admin/customer/loyalty-point/report`. Newsletter is `/admin/newsletter`.

---

## Automation

`pro-member:expire` runs hourly. It expires due memberships (after the configured grace period), sends one reminder per membership (`expiry_reminder_sent_at`), and is idempotent.

---

## Example errors

```json
{
  "response_code": "default_400",
  "message": "...",
  "content": { "message": "insufficient_wallet_balance" },
  "errors": []
}
```
