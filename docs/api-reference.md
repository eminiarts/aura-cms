# API Reference

Aura CMS provides a comprehensive API system that allows you to interact with resources programmatically. Built on Laravel's API infrastructure with Sanctum authentication, the API enables you to build headless applications, mobile apps, and third-party integrations.

## Table of Contents

- [Overview](#overview)
- [Authentication](#authentication)
- [API Architecture](#api-architecture)
- [RESTful Resources](#restful-resources)
- [Field APIs](#field-apis)
- [Media API](#media-api)
- [Search API](#search-api)
- [Webhooks](#webhooks)
- [Rate Limiting](#rate-limiting)
- [Error Handling](#error-handling)
- [API Versioning](#api-versioning)
- [GraphQL Support](#graphql-support)
- [Best Practices](#best-practices)

## Overview

The Aura CMS API provides:
- **RESTful Endpoints**: Standard CRUD operations for all resources
- **Authentication**: Token-based auth with Laravel Sanctum
- **Resource Transformation**: Consistent JSON responses
- **Field Support**: All field types work via API
- **Media Handling**: Upload and manage files
- **Search**: Global and resource-specific search
- **Filtering**: Advanced query capabilities
- **Pagination**: Efficient data loading
- **Rate Limiting**: Protection against abuse

> 📹 **Video Placeholder**: [Overview of Aura CMS API showing authentication, requests, and responses]

## Authentication

### Sanctum Setup

Enable API authentication in your application:

```php
// config/aura.php
'api' => [
    'enabled' => true,
    'prefix' => 'api',
    'version' => 'v1',
    'rate_limit' => 60,
],

// Enable Sanctum middleware in app/Http/Kernel.php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

### API Token Authentication

```php
// Generate API token
$token = $user->createToken('api-token')->plainTextToken;

// Use token in requests
curl -H "Authorization: Bearer {token}" \
     https://your-app.com/api/v1/resources
```

### Login Endpoint

```php
// routes/api.php
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    $user = User::where('email', $request->email)->first();
    
    return response()->json([
        'user' => $user,
        'token' => $user->createToken('api')->plainTextToken,
    ]);
});
```

### Logout Endpoint

```php
Route::post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    
    return response()->json([
        'message' => 'Logged out successfully'
    ]);
})->middleware('auth:sanctum');
```

### SPA Authentication

For single-page applications:

```javascript
// First, get CSRF cookie
await axios.get('/sanctum/csrf-cookie');

// Then login
const response = await axios.post('/login', {
    email: 'user@example.com',
    password: 'password'
});

// Subsequent requests are authenticated
const resources = await axios.get('/api/v1/products');
```

## API Architecture

### Route Structure

```php
// routes/api.php
use Aura\Base\Http\Controllers\Api\ResourceController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Dynamic resource routes
    foreach (Aura::getResources() as $resource) {
        $slug = app($resource)->getSlug();
        
        Route::apiResource($slug, ResourceController::class)
            ->parameters([$slug => 'model']);
    }
    
    // Additional routes
    Route::get('search', [SearchController::class, 'index']);
    Route::post('media/upload', [MediaController::class, 'store']);
});
```

### Resource Controller

```php
namespace Aura\Base\Http\Controllers\Api;

use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Http\Resources\ResourceResource;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    protected $resource;
    
    public function __construct(Request $request)
    {
        $this->resource = $this->resolveResource($request);
    }
    
    public function index(Request $request)
    {
        $this->authorize('viewAny', $this->resource);
        
        $query = $this->resource->query();
        
        // Apply filters
        $query = $this->applyFilters($query, $request);
        
        // Apply sorting
        $query = $this->applySorting($query, $request);
        
        // Apply search
        if ($request->has('search')) {
            $query = $this->applySearch($query, $request->search);
        }
        
        $perPage = $request->get('per_page', 15);
        $results = $query->paginate($perPage);
        
        return ResourceResource::collection($results);
    }
    
    public function show($model)
    {
        $this->authorize('view', $model);
        
        return new ResourceResource($model->load($this->resource->getApiIncludes()));
    }
    
    public function store(Request $request)
    {
        $this->authorize('create', $this->resource);
        
        $validated = $request->validate($this->resource->getApiRules());
        
        $model = $this->resource->create($validated);
        
        return new ResourceResource($model);
    }
    
    public function update(Request $request, $model)
    {
        $this->authorize('update', $model);
        
        $validated = $request->validate($this->resource->getApiRules($model));
        
        $model->update($validated);
        
        return new ResourceResource($model);
    }
    
    public function destroy($model)
    {
        $this->authorize('delete', $model);
        
        $model->delete();
        
        return response()->json([
            'message' => 'Resource deleted successfully'
        ]);
    }
}
```

### Resource Transformation

```php
namespace Aura\Base\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResourceResource extends JsonResource
{
    public function toArray($request)
    {
        $resource = app($this->resource->type);
        $fields = collect($resource->getFields());
        
        $data = [
            'id' => $this->id,
            'type' => $this->type,
            'slug' => $this->slug,
        ];
        
        // Transform fields
        foreach ($fields as $field) {
            $fieldClass = app($field['type']);
            $value = $this->{$field['slug']};
            
            $data[$field['slug']] = $fieldClass->transformForApi($value, $this);
        }
        
        // Add timestamps
        $data['created_at'] = $this->created_at;
        $data['updated_at'] = $this->updated_at;
        
        // Add relationships if requested
        if ($request->has('include')) {
            $includes = explode(',', $request->include);
            foreach ($includes as $include) {
                if ($this->relationLoaded($include)) {
                    $data[$include] = $this->$include;
                }
            }
        }
        
        return $data;
    }
}
```

## RESTful Resources

### Standard Endpoints

All resources follow RESTful conventions:

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/{resource}` | List resources |
| GET | `/api/v1/{resource}/{id}` | Get single resource |
| POST | `/api/v1/{resource}` | Create resource |
| PUT/PATCH | `/api/v1/{resource}/{id}` | Update resource |
| DELETE | `/api/v1/{resource}/{id}` | Delete resource |

### List Resources

```bash
GET /api/v1/products
```

Query parameters:
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15)
- `sort`: Sort field (e.g., `name`, `-created_at`)
- `filter[field]`: Filter by field value
- `search`: Search query
- `include`: Include relationships

Example request:
```bash
curl -H "Authorization: Bearer {token}" \
  "https://app.com/api/v1/products?page=1&per_page=20&sort=-created_at&filter[status]=active&include=category,tags"
```

Response:
```json
{
  "data": [
    {
      "id": 1,
      "type": "Product",
      "slug": "product-name",
      "name": "Product Name",
      "price": 99.99,
      "status": "active",
      "category": {
        "id": 1,
        "name": "Electronics"
      },
      "tags": [
        {"id": 1, "name": "Featured"},
        {"id": 2, "name": "Sale"}
      ],
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 20,
    "to": 20,
    "total": 100
  },
  "links": {
    "first": "https://app.com/api/v1/products?page=1",
    "last": "https://app.com/api/v1/products?page=5",
    "prev": null,
    "next": "https://app.com/api/v1/products?page=2"
  }
}
```

### Get Single Resource

```bash
GET /api/v1/products/1
```

Response:
```json
{
  "data": {
    "id": 1,
    "type": "Product",
    "slug": "product-name",
    "name": "Product Name",
    "description": "Product description",
    "price": 99.99,
    "stock": 50,
    "images": [
      {
        "id": 1,
        "url": "https://app.com/storage/media/product1.jpg",
        "thumbnail": "https://app.com/storage/thumbnails/product1.jpg"
      }
    ],
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

### Create Resource

```bash
POST /api/v1/products
Content-Type: application/json

{
  "name": "New Product",
  "price": 149.99,
  "description": "Product description",
  "category_id": 1,
  "status": "active"
}
```

Response:
```json
{
  "data": {
    "id": 2,
    "type": "Product",
    "slug": "new-product",
    "name": "New Product",
    "price": 149.99,
    "created_at": "2024-01-02T00:00:00Z",
    "updated_at": "2024-01-02T00:00:00Z"
  }
}
```

### Update Resource

```bash
PUT /api/v1/products/1
Content-Type: application/json

{
  "name": "Updated Product",
  "price": 129.99
}
```

### Delete Resource

```bash
DELETE /api/v1/products/1
```

Response:
```json
{
  "message": "Resource deleted successfully"
}
```

### Bulk Operations

```bash
POST /api/v1/products/bulk
Content-Type: application/json

{
  "action": "delete",
  "ids": [1, 2, 3]
}
```

## Field APIs

### Field Value Endpoint

Get field values for dynamic fields:

```bash
POST /api/v1/fields/values
Content-Type: application/json

{
  "field": "Aura\\Base\\Fields\\AdvancedSelect",
  "model": "App\\Models\\Product",
  "slug": "category_id",
  "search": "Electronics"
}
```

Response:
```json
{
  "data": [
    {"id": 1, "text": "Electronics"},
    {"id": 2, "text": "Electronic Accessories"}
  ]
}
```

### Field Validation

```bash
POST /api/v1/fields/validate
Content-Type: application/json

{
  "field": "email",
  "value": "user@example.com",
  "rules": "required|email|unique:users"
}
```

### Field Configuration

```bash
GET /api/v1/resources/products/fields
```

Response:
```json
{
  "data": [
    {
      "name": "Name",
      "slug": "name",
      "type": "text",
      "validation": "required|max:255",
      "searchable": true,
      "on_index": true
    },
    {
      "name": "Price",
      "slug": "price",
      "type": "number",
      "validation": "required|numeric|min:0",
      "prefix": "$"
    }
  ]
}
```

## Media API

### Upload Files

```bash
POST /api/v1/media/upload
Content-Type: multipart/form-data

file: (binary)
name: "product-image.jpg"
folder: "products"
```

Response:
```json
{
  "data": {
    "id": 123,
    "name": "product-image.jpg",
    "url": "https://app.com/storage/media/products/product-image.jpg",
    "mime_type": "image/jpeg",
    "size": 245678,
    "thumbnails": {
      "xs": "https://app.com/storage/thumbnails/xs/product-image.jpg",
      "sm": "https://app.com/storage/thumbnails/sm/product-image.jpg",
      "md": "https://app.com/storage/thumbnails/md/product-image.jpg",
      "lg": "https://app.com/storage/thumbnails/lg/product-image.jpg"
    }
  }
}
```

### Get Media

```bash
GET /api/v1/media/123
```

### Delete Media

```bash
DELETE /api/v1/media/123
```

### Bulk Upload

```bash
POST /api/v1/media/bulk-upload
Content-Type: multipart/form-data

files[]: (binary)
files[]: (binary)
folder: "gallery"
```

## Search API

### Global Search

```bash
GET /api/v1/search?q=product&limit=10
```

Response:
```json
{
  "data": [
    {
      "type": "Product",
      "id": 1,
      "title": "Product Name",
      "subtitle": "Electronics",
      "url": "/products/1",
      "highlight": "Matching <mark>product</mark> description"
    }
  ],
  "meta": {
    "total": 25,
    "query": "product"
  }
}
```

### Resource Search

```bash
GET /api/v1/products/search?q=laptop&fields=name,description
```

### Advanced Search

```bash
POST /api/v1/search/advanced
Content-Type: application/json

{
  "query": "laptop",
  "resources": ["products", "posts"],
  "filters": {
    "created_after": "2024-01-01",
    "status": "active"
  },
  "limit": 20
}
```

## Webhooks

### Webhook Configuration

```php
// config/aura.php
'webhooks' => [
    'enabled' => true,
    'events' => [
        'resource.created',
        'resource.updated',
        'resource.deleted',
        'media.uploaded',
    ],
],
```

### Register Webhook

```bash
POST /api/v1/webhooks
Content-Type: application/json

{
  "url": "https://your-app.com/webhook",
  "events": ["resource.created", "resource.updated"],
  "secret": "your-webhook-secret"
}
```

### Webhook Payload

```json
{
  "event": "resource.created",
  "timestamp": "2024-01-01T00:00:00Z",
  "data": {
    "resource": "products",
    "id": 123,
    "attributes": {
      "name": "New Product",
      "price": 99.99
    }
  },
  "signature": "sha256=..."
}
```

### Verify Webhook Signature

```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    throw new UnauthorizedException('Invalid signature');
}
```

## Rate Limiting

### Configuration

```php
// app/Http/Kernel.php
'api' => [
    'throttle:api',
    // or custom limits
    'throttle:60,1', // 60 requests per minute
],

// Custom rate limits
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Different limits for different endpoints
RateLimiter::for('uploads', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()->id);
});
```

### Rate Limit Headers

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200
```

### Handling Rate Limits

```javascript
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 429) {
      const retryAfter = error.response.headers['retry-after'];
      console.log(`Rate limited. Retry after ${retryAfter} seconds`);
    }
    return Promise.reject(error);
  }
);
```

## Error Handling

### Standard Error Response

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": [
      "The name field is required."
    ],
    "email": [
      "The email has already been taken."
    ]
  }
}
```

### HTTP Status Codes

| Status | Description |
|--------|-------------|
| 200 | Success |
| 201 | Created |
| 204 | No Content |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Server Error |

### Custom Error Handler

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $exception)
{
    if ($request->is('api/*')) {
        if ($exception instanceof ValidationException) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $exception->errors(),
            ], 422);
        }
        
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        }
        
        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'message' => 'Unauthorized action',
            ], 403);
        }
    }
    
    return parent::render($request, $exception);
}
```

## API Versioning

### URL Versioning

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    // Version 1 routes
});

Route::prefix('v2')->group(function () {
    // Version 2 routes with breaking changes
});
```

### Header Versioning

```php
Route::middleware('api.version:v1')->group(function () {
    // Routes for version 1
});

// Middleware
class ApiVersion
{
    public function handle($request, Closure $next, $version)
    {
        if ($request->header('API-Version') !== $version) {
            return response()->json([
                'message' => 'Invalid API version'
            ], 400);
        }
        
        return $next($request);
    }
}
```

### Resource Versioning

```php
namespace App\Http\Resources\V1;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
        ];
    }
}

namespace App\Http\Resources\V2;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->name, // Changed field name
            'pricing' => [
                'amount' => $this->price,
                'currency' => 'USD',
            ],
        ];
    }
}
```

## GraphQL Support

### Setup GraphQL

```bash
composer require nuwave/lighthouse
php artisan vendor:publish --tag=lighthouse-schema
```

### Schema Definition

```graphql
# graphql/schema.graphql
type Query {
    products(
        first: Int!
        page: Int
        where: ProductWhereConditions @whereConditions
        orderBy: [ProductOrderBy!] @orderBy
    ): ProductPaginator! @paginate

    product(id: ID! @eq): Product @find
}

type Mutation {
    createProduct(input: CreateProductInput! @spread): Product! @create
    updateProduct(id: ID!, input: UpdateProductInput! @spread): Product! @update
    deleteProduct(id: ID!): Product! @delete
}

type Product {
    id: ID!
    name: String!
    price: Float!
    description: String
    category: Category @belongsTo
    tags: [Tag!] @belongsToMany
    created_at: DateTime!
    updated_at: DateTime!
}

input CreateProductInput {
    name: String!
    price: Float!
    description: String
    category_id: ID
}

input UpdateProductInput {
    name: String
    price: Float
    description: String
    category_id: ID
}
```

### GraphQL Queries

```graphql
# Get products
query GetProducts {
    products(first: 10, page: 1) {
        data {
            id
            name
            price
            category {
                name
            }
        }
        paginatorInfo {
            currentPage
            lastPage
            total
        }
    }
}

# Create product
mutation CreateProduct {
    createProduct(input: {
        name: "New Product"
        price: 99.99
        category_id: 1
    }) {
        id
        name
        price
    }
}
```

### GraphQL Client

```javascript
import { ApolloClient, InMemoryCache, gql } from '@apollo/client';

const client = new ApolloClient({
    uri: 'https://app.com/graphql',
    cache: new InMemoryCache(),
    headers: {
        authorization: `Bearer ${token}`,
    },
});

// Query
const { data } = await client.query({
    query: gql`
        query GetProduct($id: ID!) {
            product(id: $id) {
                id
                name
                price
            }
        }
    `,
    variables: { id: 1 },
});
```

## Best Practices

### 1. API Design

```php
// Use consistent naming
/api/v1/products        // Plural for collections
/api/v1/products/1      // Singular for items

// Use proper HTTP methods
GET     // Read
POST    // Create
PUT     // Full update
PATCH   // Partial update
DELETE  // Delete

// Return appropriate status codes
return response()->json($data, 201);        // Created
return response()->json(null, 204);         // No content
return response()->json($errors, 422);      // Validation error
```

### 2. Security

```php
// Always authenticate API routes
Route::middleware('auth:sanctum')->group(function () {
    // API routes
});

// Validate all input
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
]);

// Use policies for authorization
$this->authorize('update', $product);

// Sanitize output
return response()->json([
    'content' => strip_tags($content),
]);

// Rate limit sensitive endpoints
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/password/reset', ...);
});
```

### 3. Performance

```php
// Use eager loading
$products = Product::with(['category', 'tags'])->get();

// Implement caching
return Cache::remember('products', 3600, function () {
    return Product::all();
});

// Use pagination
$products = Product::paginate(20);

// Select only needed fields
$products = Product::select(['id', 'name', 'price'])->get();

// Use database indexes
Schema::table('products', function ($table) {
    $table->index(['status', 'created_at']);
});
```

### 4. Documentation

```php
/**
 * @OA\Get(
 *     path="/api/v1/products",
 *     summary="Get list of products",
 *     tags={"Products"},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number",
 *         required=false,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(ref="#/components/schemas/ProductCollection")
 *     )
 * )
 */
```

### 5. Testing

```php
public function test_can_list_products()
{
    $user = User::factory()->create();
    $products = Product::factory()->count(5)->create();
    
    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/products');
    
    $response->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'price']
            ],
            'meta' => ['current_page', 'total']
        ]);
}
```

### 6. SDK Development

```javascript
// JavaScript SDK
class AuraSDK {
    constructor(apiKey, baseUrl = 'https://app.com/api/v1') {
        this.apiKey = apiKey;
        this.baseUrl = baseUrl;
    }
    
    async request(endpoint, options = {}) {
        const response = await fetch(`${this.baseUrl}${endpoint}`, {
            ...options,
            headers: {
                'Authorization': `Bearer ${this.apiKey}`,
                'Content-Type': 'application/json',
                ...options.headers,
            },
        });
        
        if (!response.ok) {
            throw new Error(`API Error: ${response.statusText}`);
        }
        
        return response.json();
    }
    
    // Resource methods
    products = {
        list: (params) => this.request('/products', { params }),
        get: (id) => this.request(`/products/${id}`),
        create: (data) => this.request('/products', {
            method: 'POST',
            body: JSON.stringify(data),
        }),
        update: (id, data) => this.request(`/products/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        }),
        delete: (id) => this.request(`/products/${id}`, {
            method: 'DELETE',
        }),
    };
}

// Usage
const aura = new AuraSDK('your-api-key');
const products = await aura.products.list({ page: 1 });
```

> 📹 **Video Placeholder**: [Building applications with the Aura CMS API - authentication, requests, and best practices]

### Pro Tips

1. **Version from Start**: Always version your API from v1
2. **Use Standards**: Follow REST/GraphQL conventions
3. **Document Everything**: Use OpenAPI/Swagger
4. **Test Thoroughly**: Automated tests for all endpoints
5. **Monitor Usage**: Track API metrics and errors
6. **Provide SDKs**: Make integration easier
7. **Secure by Default**: Require authentication
8. **Cache Wisely**: Balance freshness and performance
9. **Handle Errors Gracefully**: Consistent error format
10. **Deprecate Carefully**: Give users time to migrate

The API system provides a robust foundation for building modern applications that integrate with Aura CMS, whether you're building mobile apps, SPAs, or third-party integrations.