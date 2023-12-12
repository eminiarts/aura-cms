# Belongs To Relationship

The `product` method defines a relationship between a `Booking` and a `Product` using the `Meta` (or `post_meta`) table as an intermediary. It establishes this connection using Laravel's `hasOneThrough` relationship.

## Method Definition

```php
/**
 * Defines a relationship to associate a Booking with a Product.
 *
 * This method leverages the hasOneThrough relationship in Laravel's Eloquent ORM,
 * using the Meta (or post_meta) table as an intermediary.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
 */
public function product()
{
    return $this->hasOneThrough(
        Product::class,
        Meta::class,
        'post_id',     // Foreign key on the post_meta table
        'id',          // Foreign key on the products table
        'id',          // Local key on the bookings table
        'value'        // Local key on the post_meta table
    )->where('post_meta.key', 'product_id');
}
```

## Explanation

The relationship is established as follows:

- A `Booking` has one `Product` through the `Meta` model.
- The `post_id` column on the `post_meta` table corresponds to the `id` on the `Booking` (which uses the `posts` table).
- The `value` column on the `post_meta` table corresponds to the `id` on the `Product` (which also uses the `posts` table).
- The relationship is further filtered to consider only rows where the `key` in the `post_meta` table is 'product_id'.

To retrieve the associated product of a booking, you can use:

```php
$booking = Booking::with('product')->find(1);
$product = $booking->product;
```

---









---

# Has Many Relationship

The `reviews` method defines a relationship where a `Product` has many `Reviews`, using the `Meta` (or `post_meta`) table as an intermediary. This relationship is realized using Laravel's `hasManyThrough` relationship.

## Method Definition

```php
/**
 * Defines a relationship to associate a Product with multiple Reviews.
 *
 * This method leverages the hasManyThrough relationship in Laravel's Eloquent ORM,
 * using the Meta (or post_meta) table as an intermediary.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
 */
public function reviews()
{
    return $this->hasManyThrough(
        Review::class,
        Meta::class,
        'post_id',     // Foreign key on the post_meta table
        'id',          // Foreign key on the reviews table
        'id',          // Local key on the products table
        'value'        // Local key on the post_meta table
    )->where('post_meta.key', 'review_id');
}
```

## Explanation

The relationship is established as follows:

- A `Product` has many `Reviews` through the `Meta` model.
- The `post_id` column on the `post_meta` table corresponds to the `id` on the `Product` (which uses the `posts` table).
- The `value` column on the `post_meta` table corresponds to the `id` on the `Review`.
- The relationship is further filtered to consider only rows where the `key` in the `post_meta` table is 'review_id'.

To retrieve all reviews associated with a product, you can use:

```php
$product = Product::with('reviews')->find(1);
$reviews = $product->reviews;
```

---

This documentation assumes that you have a `Review` model, and products can have multiple reviews. The `post_meta` table holds the connection between the products and their reviews. Adjust the scenario and model names as needed to fit your actual use case.
