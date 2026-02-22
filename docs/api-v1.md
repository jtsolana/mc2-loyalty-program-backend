# MC2 Loyalty Reward — API Documentation v1

## Overview

This document covers all active API endpoints for the MC2 Loyalty Reward backend. The API is consumed by the mobile app and integrates with Loyverse POS.

---

## Base URL

```
https://mc2-backend.test/api/v1
```

---

## Required Headers

All requests must include:

| Header | Value |
|---|---|
| `Accept` | `application/json` |
| `Content-Type` | `application/json` |

Authenticated endpoints additionally require:

| Header | Value |
|---|---|
| `Authorization` | `Bearer {token}` |

The token is obtained from the login or register response.

---

## Common Error Responses

| Status | Meaning |
|---|---|
| `401` | Missing or invalid token |
| `403` | Authenticated but lacks the required permission |
| `404` | Resource not found (invalid or expired hashed ID) |
| `422` | Validation failed — response body contains an `errors` object |

**Validation error example:**
```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## Authentication

### Register

**`POST /auth/register`**

Creates a new customer account and returns a Sanctum token.

**Body**

| Field | Type | Required | Notes |
|---|---|---|---|
| `name` | string | Yes | Full name |
| `email` | string | Yes | Must be unique |
| `password` | string | Yes | Min 8 characters |
| `password_confirmation` | string | Yes | Must match `password` |
| `username` | string | No | Alphanumeric/dash, unique |
| `phone` | string | No | Max 30 characters |
| `device_name` | string | No | Defaults to `"mobile"` — used as Sanctum token name |

**Sample Request**
```json
{
  "name": "Juan dela Cruz",
  "email": "juan@example.com",
  "username": "juandc",
  "phone": "09171234567",
  "password": "password123",
  "password_confirmation": "password123",
  "device_name": "iPhone 14"
}
```

**Response `201`**
```json
{
  "user": {
    "id": 1,
    "name": "Juan dela Cruz",
    "username": "juandc",
    "email": "juan@example.com",
    "phone": "09171234567",
    "avatar": null,
    "hashed_id": "xEq9Kn2p",
    "loyverse_customer_id": "49a9f354-376c-46d7-8ce2-2f7896a833db",
    "roles": [
      { "name": "customer", "display_name": "Customer" }
    ],
    "loyalty_point": {
      "total_points": 0,
      "lifetime_points": 0
    },
    "created_at": "2026-02-21T10:00:00Z"
  },
  "token": "1|abc123tokenhere"
}
```

**Error `422`** — duplicate email
```json
{
  "message": "This email address is already registered.",
  "errors": {
    "email": ["This email address is already registered."]
  }
}
```

---

### Login

**`POST /auth/login`**

Authenticate with email or username.

**Body**

| Field | Type | Required | Notes |
|---|---|---|---|
| `login` | string | Yes | Email address or username |
| `password` | string | Yes | |
| `device_name` | string | No | Defaults to `"mobile"` |

**Sample Request**
```json
{
  "login": "juan@example.com",
  "password": "password123",
  "device_name": "iPhone 14"
}
```

**Response `200`**
```json
{
  "user": {
    "id": 1,
    "name": "Juan dela Cruz",
    "username": "juandc",
    "email": "juan@example.com",
    "hashed_id": "xEq9Kn2p",
    "loyverse_customer_id": "49a9f354-376c-46d7-8ce2-2f7896a833db",
    "loyalty_point": {
      "total_points": 150,
      "lifetime_points": 300
    }
  },
  "token": "2|xyz789tokenhere"
}
```

**Error `422`** — wrong credentials
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "login": ["The provided credentials are incorrect."]
  }
}
```

---

### Social Login

**`POST /auth/social/{provider}`**

Login or register via Google or Facebook OAuth.

**URL Parameters**

| Parameter | Values |
|---|---|
| `provider` | `google` or `facebook` |

**Body**

| Field | Type | Required | Notes |
|---|---|---|---|
| `token` | string | Yes | OAuth access token from the mobile SDK |
| `device_name` | string | No | Defaults to `"mobile"` |

**Sample Request**
```json
{
  "token": "ya29.a0AfH6SMBx...",
  "device_name": "Android Pixel 7"
}
```

**Response `200`**
```json
{
  "user": {
    "id": 2,
    "name": "Maria Santos",
    "email": "maria@gmail.com",
    "avatar": "https://lh3.googleusercontent.com/photo.jpg",
    "hashed_id": "nKp3Lx8q",
    "loyalty_point": {
      "total_points": 0,
      "lifetime_points": 0
    }
  },
  "token": "3|social456tokenhere"
}
```

**Error `422`** — unsupported provider
```json
{ "message": "Unsupported provider: twitter" }
```

---

### Logout

**`POST /auth/logout`**

Revokes the current access token.

**Auth:** Required

**Response `200`**
```json
{ "message": "Logged out successfully." }
```

---

## Customer

> All customer endpoints require authentication and the `points.view` permission (assigned to the `customer` role by default).

---

### Get Profile

**`GET /customer/profile`**

Returns the authenticated customer's profile and loyalty point balance.

**Response `200`**
```json
{
  "data": {
    "id": 1,
    "name": "Juan dela Cruz",
    "username": "juandc",
    "email": "juan@example.com",
    "phone": "09171234567",
    "avatar": null,
    "hashed_id": "xEq9Kn2p",
    "loyverse_customer_id": "49a9f354-376c-46d7-8ce2-2f7896a833db",
    "loyalty_point": {
      "total_points": 150,
      "lifetime_points": 300
    }
  }
}
```

---

### Update Profile

**`PUT /customer/profile`**

Update the authenticated customer's profile. All fields are optional — only send what needs to change.

**Body**

| Field | Type | Required | Notes |
|---|---|---|---|
| `name` | string | No | |
| `username` | string | No | Must be unique |
| `phone` | string | No | |
| `avatar` | string (URL) | No | Must be a valid URL |

**Sample Request**
```json
{
  "name": "Juan D. Cruz",
  "phone": "09189876543"
}
```

**Response `200`** — returns updated profile (same shape as GET profile)

---

### Get Points Balance

**`GET /customer/points`**

Returns the current loyalty point balance.

**Response `200`**
```json
{
  "data": {
    "total_points": 150,
    "lifetime_points": 300
  }
}
```

---

### Get Points History

**`GET /customer/points/history`**

Returns a paginated list of point transactions.

**Query Parameters**

| Parameter | Type | Notes |
|---|---|---|
| `page` | integer | Defaults to 1 |

**Response `200`**
```json
{
  "data": [
    {
      "id": 10,
      "type": "earn",
      "points": 6,
      "balance_after": 156,
      "description": "Earned 6 points for 3 item(s)",
      "created_at": "2026-02-21T15:19:22Z"
    },
    {
      "id": 9,
      "type": "redeem",
      "points": -50,
      "balance_after": 150,
      "description": "Redeemed 50 points for ₱25 discount",
      "created_at": "2026-02-20T11:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "total": 42
  }
}
```

> Transaction `type` values: `earn`, `redeem`, `reward`, `expire`, `adjust`

---

### Get Rewards

**`GET /customer/rewards`**

Returns a paginated list of the authenticated customer's rewards across all statuses, with full reward rule details.

**Query Parameters**

| Parameter | Type | Notes |
|---|---|---|
| `page` | integer | Defaults to 1 |

**Response `200`**
```json
{
  "data": [
    {
      "id": "abc123",
      "status": "pending",
      "points_deducted": 500,
      "expires_at": "2026-03-21T10:00:00Z",
      "claimed_at": null,
      "created_at": "2026-02-21T10:00:00Z",
      "reward_rule": {
        "name": "Free Drink Tier",
        "reward_title": "1 Free Regular Drink",
        "points_required": 500
      }
    },
    {
      "id": "def456",
      "status": "claimed",
      "points_deducted": 500,
      "expires_at": "2026-03-10T10:00:00Z",
      "claimed_at": "2026-03-01T14:30:00Z",
      "created_at": "2026-02-10T10:00:00Z",
      "reward_rule": {
        "name": "Free Drink Tier",
        "reward_title": "1 Free Regular Drink",
        "points_required": 500
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "total": 2
  }
}
```

> Reward `status` values: `pending`, `claimed`, `expired`

---

## Staff

> Staff endpoints require authentication and the `points.redeem` permission (assigned to the `staff` and `admin` roles by default).

---

### List Customer Rewards

**`GET /staff/customers/{hashed_id}/rewards`**

Returns a customer's pending rewards after scanning their QR code. Used by staff to select a reward before processing a claim.

**URL Parameters**

| Parameter | Notes |
|---|---|
| `hashed_id` | The customer's hashed ID — encoded in their QR code |

**Response `200`**
```json
{
  "data": [
    {
      "id": "abc123",
      "status": "pending",
      "points_deducted": 500,
      "expires_at": "2026-03-21T10:00:00Z",
      "claimed_at": null,
      "created_at": "2026-02-21T10:00:00Z",
      "reward_rule": {
        "name": "Free Drink Tier",
        "reward_title": "1 Free Regular Drink",
        "points_required": 500
      }
    }
  ]
}
```

> Returns an empty `data` array when the customer has no pending rewards.

---

### Claim Reward

**`POST /staff/rewards/{hashed_id}/claim`**

Marks a customer's pending reward as claimed. Should be called after the staff has confirmed the reward with the customer.

**URL Parameters**

| Parameter | Notes |
|---|---|
| `hashed_id` | The reward's hashed ID — selected from the customer's reward list |

**Response `200`**
```json
{
  "message": "Reward successfully claimed.",
  "data": {
    "id": "abc123",
    "status": "claimed",
    "points_deducted": 500,
    "expires_at": "2026-03-21T10:00:00Z",
    "claimed_at": "2026-02-22T09:45:00Z",
    "created_at": "2026-02-21T10:00:00Z",
    "reward_rule": {
      "name": "Free Drink Tier",
      "reward_title": "1 Free Regular Drink",
      "points_required": 500
    }
  }
}
```

**Error `422`** — reward is not pending
```json
{ "message": "Reward cannot be claimed. Current status: claimed." }
```

**Error `422`** — reward has expired
```json
{ "message": "Reward has expired." }
```

---

## Promotions

> Public endpoints — no authentication required.

---

### List Promotions

**`GET /promotions`**

Returns a paginated list of published promotions and announcements.

**Query Parameters**

| Parameter | Type | Notes |
|---|---|---|
| `type` | string | Filter by `promotion` or `announcement`. Omit to return all. |
| `page` | integer | Defaults to 1 |

**Sample Request**
```
GET /promotions?type=announcement&page=1
```

**Response `200`**
```json
{
  "data": [
    {
      "id": "prom1",
      "title": "Summer Sale",
      "excerpt": "Get double points on all drinks this summer!",
      "thumbnail_url": "https://mc2-backend.test/storage/promotions/abc.jpg",
      "content": "<p>Get double points on all drinks this summer!</p>",
      "type": "promotion",
      "is_published": true,
      "published_at": "2026-02-01T08:00:00Z",
      "created_at": "2026-01-28T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

---

### Show Promotion

**`GET /promotions/{hashed_id}`**

Returns a single published promotion or announcement.

**Response `200`**
```json
{
  "data": {
    "id": "prom1",
    "title": "Summer Sale",
    "excerpt": "Get double points on all drinks this summer!",
    "thumbnail_url": "https://mc2-backend.test/storage/promotions/abc.jpg",
    "content": "<p>Get double points on all drinks this summer!</p>",
    "type": "promotion",
    "is_published": true,
    "published_at": "2026-02-01T08:00:00Z",
    "created_at": "2026-01-28T10:00:00Z"
  }
}
```

**Error `404`** — promotion not found or not published

---

## Loyverse Webhook

> This endpoint has no authentication. It is called automatically by Loyverse POS when a sale is completed.

---

### Receipt Webhook

**`POST /loyverse/webhook`**

Processes incoming Loyverse POS receipt events. Creates a purchase record and awards points to the matched customer.

- Only `receipts.update` events are processed
- Only `SALE` receipt types award points (refunds are skipped)
- Duplicate receipts (same `receipt_number`) are silently ignored

**Sample Payload (sent by Loyverse)**
```json
{
  "type": "receipts.update",
  "merchant_id": "ac4e11b9-3358-4ea7-bf1b-341064d78f14",
  "created_at": "2026-02-21T15:19:27.837Z",
  "receipts": [
    {
      "receipt_number": "1-0009",
      "receipt_type": "SALE",
      "total_money": 300,
      "customer_id": "49a9f354-376c-46d7-8ce2-2f7896a833db",
      "line_items": [
        {
          "item_name": "Dirty Matcha",
          "variant_name": "Large",
          "quantity": 2,
          "price": 150
        }
      ]
    }
  ]
}
```

**Response `200`**
```json
{ "message": "Webhook processed successfully." }
```

> The `customer_id` in the Loyverse payload is matched against the `loyverse_customer_id` field on the user record to identify and award points to the correct customer.

---

## Permission Reference

| Permission | Group | Default Roles |
|---|---|---|
| `auth.register` | auth | customer |
| `points.view` | points | customer, staff, admin |
| `points.redeem` | points | staff, admin |
| `points.adjust` | points | admin |
| `customers.view` | customers | staff, admin |
| `customers.manage` | customers | admin |
| `point-rules.manage` | admin | admin |
| `reward-rules.manage` | admin | admin |
| `promotions.manage` | admin | admin |
| `roles.manage` | admin | admin |

---

## Notes for Mobile Integration

- The `hashed_id` field on the user object is the value to use as the **QR code** in the mobile app. It encodes the user's ID using Hashids and is what Loyverse stores as the `customer_code`.
- All resource IDs in URL paths (e.g. `{hashed_id}`) use the hashed ID, not the raw integer ID.
- Token format is `{id}|{token}` — store and send the full string as-is in the `Authorization` header.
- Point balances are always integers. Discount amounts are decimal strings (e.g. `"25.00"`).
- Reward `points_deducted` is the number of points that were deducted from the customer's balance when the reward was issued.
