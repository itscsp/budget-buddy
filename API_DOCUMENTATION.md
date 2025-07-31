# Budget Buddy REST API Documentation

This document describes the REST API endpoints available in the Budget Buddy WordPress plugin.

## Base URL
```
https://yourdomain.com/wp-json/budget-buddy/v1/
```

## Authentication
All endpoints require user authentication. Users must be logged into WordPress to access the API endpoints.

## Endpoints Overview

### 1. Get Budget Data
**GET** `/budget`

Returns budget data for multiple months in the requested format.

**Parameters:**
- `months` (optional): Number of months to retrieve (default: 6, max: 24)

**Response Format:**
```json
{
  "success": true,
  "data": {
    "January 2025": {
      "report": {
        "income": 50000,
        "expense": 35000,
        "loan": 5000,
        "net": 10000,
        "transaction_count": 25,
        "month_name": "January 2025",
        "spent_50_percent": 15000,
        "spent_25_percent": 8000,
        "spent_15_percent": 7000,
        "spent_10_percent": 5000
      },
      "transactions": [
        {
          "id": 1,
          "user_id": 1,
          "type": "income",
          "amount": 50000,
          "description": "Salary",
          "date": "2025-01-01",
          "category_id": 1,
          "category_name": "Essentials",
          "percentage": 50
        }
      ]
    }
  },
  "user_id": 1,
  "timestamp": "2025-01-31 10:30:00"
}
```

**Example Request:**
```bash
curl -X GET "https://yourdomain.com/wp-json/budget-buddy/v1/budget?months=3" \
  -H "Cookie: wordpress_logged_in_cookie_value"
```

### 2. Get Transactions
**GET** `/transactions`

Retrieve transactions with optional filtering and pagination.

**Parameters:**
- `month` (optional): Month in Y-m format (e.g., "2025-01")
- `limit` (optional): Number of transactions to return (default: 50, max: 200)
- `offset` (optional): Number of transactions to skip (default: 0)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "type": "expense",
      "amount": 2500,
      "description": "Groceries",
      "date": "2025-01-15",
      "category_id": 1,
      "category_name": "Essentials",
      "percentage": 50
    }
  ],
  "pagination": {
    "total": 100,
    "limit": 50,
    "offset": 0,
    "has_more": true
  }
}
```

**Example Requests:**
```bash
# Get all transactions
curl -X GET "https://yourdomain.com/wp-json/budget-buddy/v1/transactions" \
  -H "Cookie: wordpress_logged_in_cookie_value"

# Get transactions for a specific month
curl -X GET "https://yourdomain.com/wp-json/budget-buddy/v1/transactions?month=2025-01" \
  -H "Cookie: wordpress_logged_in_cookie_value"

# Get transactions with pagination
curl -X GET "https://yourdomain.com/wp-json/budget-buddy/v1/transactions?limit=10&offset=20" \
  -H "Cookie: wordpress_logged_in_cookie_value"
```

### 3. Add Transaction
**POST** `/transactions`

Add a new transaction.

**Required Parameters:**
- `type`: Transaction type ("income", "expense", "loan")
- `amount`: Transaction amount (minimum: 0.01)
- `date`: Transaction date in Y-m-d format

**Optional Parameters:**
- `description`: Transaction description
- `category_id`: Budget category ID

**Request Body:**
```json
{
  "type": "expense",
  "amount": 1500,
  "description": "Rent payment",
  "date": "2025-01-01",
  "category_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Transaction added successfully",
  "data": {
    "id": 123,
    "user_id": 1,
    "type": "expense",
    "amount": 1500,
    "description": "Rent payment",
    "date": "2025-01-01",
    "category_id": 1,
    "category_name": "Essentials",
    "percentage": 50
  }
}
```

**Example Request:**
```bash
curl -X POST "https://yourdomain.com/wp-json/budget-buddy/v1/transactions" \
  -H "Content-Type: application/json" \
  -H "Cookie: wordpress_logged_in_cookie_value" \
  -d '{
    "type": "expense",
    "amount": 1500,
    "description": "Rent payment",
    "date": "2025-01-01",
    "category_id": 1
  }'
```

### 4. Update Transaction
**PUT** `/transactions/{id}`

Update an existing transaction.

**Path Parameters:**
- `id`: Transaction ID

**Optional Body Parameters:**
- `type`: Transaction type
- `amount`: Transaction amount
- `description`: Transaction description
- `date`: Transaction date
- `category_id`: Budget category ID

**Example Request:**
```bash
curl -X PUT "https://yourdomain.com/wp-json/budget-buddy/v1/transactions/123" \
  -H "Content-Type: application/json" \
  -H "Cookie: wordpress_logged_in_cookie_value" \
  -d '{
    "amount": 1600,
    "description": "Updated rent payment"
  }'
```

### 5. Delete Transaction
**DELETE** `/transactions/{id}`

Delete a transaction.

**Path Parameters:**
- `id`: Transaction ID

**Response:**
```json
{
  "success": true,
  "message": "Transaction deleted successfully"
}
```

**Example Request:**
```bash
curl -X DELETE "https://yourdomain.com/wp-json/budget-buddy/v1/transactions/123" \
  -H "Cookie: wordpress_logged_in_cookie_value"
```

### 6. Get Single Transaction
**GET** `/transactions/{id}`

Get details of a specific transaction.

**Path Parameters:**
- `id`: Transaction ID

**Example Request:**
```bash
curl -X GET "https://yourdomain.com/wp-json/budget-buddy/v1/transactions/123" \
  -H "Cookie: wordpress_logged_in_cookie_value"
```

### 7. Get Plans
**GET** `/plans`

Get monthly plans.

**Parameters:**
- `month` (optional): Month in Y-m format (e.g., "2025-01")

**Example Request:**
```bash
curl -X GET "https://yourdomain.com/wp-json/budget-buddy/v1/plans?month=2025-01" \
  -H "Cookie: wordpress_logged_in_cookie_value"
```

### 8. Add Plan
**POST** `/plans`

Add a new monthly plan.

**Required Parameters:**
- `plan_text`: Plan description
- `amount`: Plan amount (minimum: 0.01)
- `plan_month`: Plan month date in Y-m-d format

**Optional Parameters:**
- `is_recurring`: Whether the plan repeats monthly (boolean, default: false)

**Example Request:**
```bash
curl -X POST "https://yourdomain.com/wp-json/budget-buddy/v1/plans" \
  -H "Content-Type: application/json" \
  -H "Cookie: wordpress_logged_in_cookie_value" \
  -d '{
    "plan_text": "Save for vacation",
    "amount": 5000,
    "plan_month": "2025-01-01",
    "is_recurring": true
  }'
```

### 9. Get Categories
**GET** `/categories`

Get budget categories for the current user.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "category_name": "Essentials",
      "percentage": 50,
      "created_at": "2025-01-01 00:00:00"
    }
  ]
}
```

**Example Request:**
```bash
curl -X GET "https://yourdomain.com/wp-json/budget-buddy/v1/categories" \
  -H "Cookie: wordpress_logged_in_cookie_value"
```

### 10. Get Monthly Report
**GET** `/reports/{month}`

Get detailed monthly financial report.

**Path Parameters:**
- `month`: Month in Y-m format (e.g., "2025-01")

**Example Request:**
```bash
curl -X GET "https://yourdomain.com/wp-json/budget-buddy/v1/reports/2025-01" \
  -H "Cookie: wordpress_logged_in_cookie_value"
```

## Error Responses

All endpoints return standardized error responses:

```json
{
  "code": "error_code",
  "message": "Error description",
  "data": {
    "status": 400
  }
}
```

Common error codes:
- `unauthorized` (401): User not authenticated
- `not_found` (404): Resource not found
- `invalid_category` (400): Invalid category ID
- `creation_failed` (500): Failed to create resource
- `update_failed` (500): Failed to update resource
- `delete_failed` (404): Failed to delete resource

## Authentication Methods

### 1. Cookie Authentication (Browser)
When making requests from a browser where the user is logged into WordPress, the authentication cookies will be automatically included.

### 2. Application Passwords (WordPress 5.6+)
Generate an application password in WordPress admin and use Basic Authentication:

```bash
curl -X GET "https://yourdomain.com/wp-json/budget-buddy/v1/budget" \
  -u "username:application_password"
```

### 3. Custom Authentication
For custom authentication methods, you can extend the `check_authentication` method in the `BudgetBuddy_REST_API` class.

## Rate Limiting

The API inherits WordPress's built-in rate limiting. For custom rate limiting, consider using plugins like WP REST API Rate Limit.

## Examples

### Complete Budget Management Workflow

1. **Get user categories:**
```bash
curl -X GET "https://yourdomain.com/wp-json/budget-buddy/v1/categories" \
  -H "Cookie: wordpress_logged_in_cookie_value"
```

2. **Add an income transaction:**
```bash
curl -X POST "https://yourdomain.com/wp-json/budget-buddy/v1/transactions" \
  -H "Content-Type: application/json" \
  -H "Cookie: wordpress_logged_in_cookie_value" \
  -d '{
    "type": "income",
    "amount": 50000,
    "description": "Monthly salary",
    "date": "2025-01-01"
  }'
```

3. **Add an expense:**
```bash
curl -X POST "https://yourdomain.com/wp-json/budget-buddy/v1/transactions" \
  -H "Content-Type: application/json" \
  -H "Cookie: wordpress_logged_in_cookie_value" \
  -d '{
    "type": "expense",
    "amount": 15000,
    "description": "Rent",
    "date": "2025-01-01",
    "category_id": 1
  }'
```

4. **Get monthly report:**
```bash
curl -X GET "https://yourdomain.com/wp-json/budget-buddy/v1/reports/2025-01" \
  -H "Cookie: wordpress_logged_in_cookie_value"
```

5. **Get complete budget data:**
```bash
curl -X GET "https://yourdomain.com/wp-json/budget-buddy/v1/budget?months=3" \
  -H "Cookie: wordpress_logged_in_cookie_value"
```

This API provides comprehensive access to all Budget Buddy functionality with proper authentication, validation, and error handling.
