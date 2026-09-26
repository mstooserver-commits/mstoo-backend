Integrate Razorpay payment for a booking and wallet top-up flow in a Next.js website using the backend APIs from this Laravel project.

Requirements:
- Use backend API: POST /api/v1/customer/booking/request/send for booking payment
- Use backend API: POST /api/v1/customer/wallet/add-fund for wallet top-up
- Fetch Razorpay public key from GET /api/v1/customer/config
- Use Razorpay Checkout script from https://checkout.razorpay.com/v1/checkout.js
- For booking, send these fields:
  - payment_method: 'razor_pay'
  - zone_id
  - service_schedule
  - service_address_id
  - razorpay_payment_id
  - razorpay_order_id
  - razorpay_signature
- For wallet top-up, first call /api/v1/customer/wallet/add-fund with:
  - amount
  - payment_method: 'razor_pay'
  - callback (optional)
  - payment_platform (optional, like 'web')
  Then redirect to the returned payment_url
- Add proper loading states, error handling, and success callbacks
- Handle Razorpay success, failure, and cancellation
- Use bearer token auth
- Keep API base URL configurable via .env
- Create reusable functions for:
  - getConfig()
  - openRazorpayCheckout({ amount, name, description, email, phone, onSuccess, onFailure })
  - createBookingWithRazorpay(payload)
  - addWalletFund(amount)
- Use TypeScript
- Use React hooks
- Make code production-ready and secure
- Do not expose Razorpay secret key in frontend
- Use environment variables for NEXT_PUBLIC_API_URL and NEXT_PUBLIC_RAZORPAY_KEY
- Return clean, typed code with reusable API service layer and component examples

Important backend details:
- Booking route: POST https://YOUR_BACKEND_URL/api/v1/customer/booking/request/send
- Wallet route: POST https://YOUR_BACKEND_URL/api/v1/customer/wallet/add-fund
- Config route: GET https://YOUR_BACKEND_URL/api/v1/customer/config
- Razorpay mode expected in test environment is test with keys starting with rzp_test_
- Server verifies payment signature before creating the booking

Generate:
1. API helper file with typed functions
2. Razorpay checkout hook/component
3. Example booking checkout page
4. Example wallet top-up page
5. .env.example values
6. brief README usage instructions

Keep the code clean, modular, and ready to paste into a Next.js app.
