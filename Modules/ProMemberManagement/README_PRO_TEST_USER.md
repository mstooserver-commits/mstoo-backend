# MSTOO Pro test user (dev only)

Create or upgrade a free Pro membership for testing Flutter Pro features **without** real payment.

## Requirements

- `APP_ENV` must be one of: `local`, `development`, `testing`, `dev`
- Command is **refused** on `production` / `staging` / etc.

## Command

```bash
cd /path/to/masto-backend

php artisan mstoo:create-pro-test-user

# Custom options
php artisan mstoo:create-pro-test-user \
  --phone=9876543210 \
  --password=Test@12345 \
  --name="Pro Test User" \
  --days=30 \
  --wallet=5000
```

## What it does

1. Creates or updates a **customer** with verified phone
2. Sets the given password (hashed)
3. Sets wallet balance (at least `--wallet`)
4. Activates Pro via `ProMemberService::grantTestMembership` with gateway id `dev-test-grant-*` and payment method `test_grant`
5. Does **not** create a fake Razorpay capture

## Flutter login

Use **phone + password**. OTP is only required when phone verification is enabled **and** the phone is unverified.

Default test phone: `9876543210` / password: `Test@12345`
