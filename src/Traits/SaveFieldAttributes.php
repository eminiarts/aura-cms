<?php

namespace Aura\Base\Traits;

use Aura\Base\Fields\ID;
use Aura\Base\Fields\Password;

trait SaveFieldAttributes
{
    /**
     * Register post-save meta persistence. Resource invokes the pre-save
     * field pipeline directly, with the step order hard-coded.
     *
     * The three steps (initial post columns → pack field attributes into the
     * `fields` array → persist `fields` as meta) used to be three separate
     * `saving` listeners registered by three trait boot methods. Their order
     * then depended on trait boot order — and Laravel ≥13 invokes trait boot
     * methods in ReflectionClass::getMethods() order, which PHP 8.5 changed:
     * a trait method re-imported by a subclass now reflects BEFORE inherited
     * methods. A subclass re-`use`ing SaveMetaFields therefore booted the
     * meta consumer before the packer, and the literal `fields` array leaked
     * into the INSERT ("table posts has no column named fields", issue #37).
     *
     * One explicit pipeline — immune to boot order.
     */
    protected static function bootSaveFieldAttributes()
    {
        static::saved(function ($post) {
            static::persistMetaFieldsOnSaved($post);
        });
    }

    /**
     * Set Fields Attributes
     *
     * Take Fields Attributes and Put all fields from getFieldSlugs() in the Fields Column
     *
     * @return void
     */
    protected static function packFieldAttributes($post)
    {
        if (! optional($post->attributes)['fields']) {
            $post->attributes['fields'] = [];
        }

        collect($post->inputFieldsSlugs())->each(function ($slug) use ($post) {

            if (array_key_exists($slug, $post->attributes)) {

                $class = $post->fieldClassBySlug($slug);

                if ($slug == 'password') {
                }

                // Do not continue if the Field is not found
                if (! $class) {
                    return;
                }

                // Do not set password fields manually, since they would overwrite the hashed password
                if ($class instanceof Password) {

                    // If the password is available in the $post->attributes, unset it
                    // if (empty($post->attributes[$slug])) {
                    //     unset($post->attributes[$slug]);
                    // }

                    // Check if the password field is dirty (i.e., has been modified)
                    // if (! $post->isDirty($slug)) {
                    //     // Remove it from attributes so it won't be saved
                    //     unset($post->attributes[$slug]);

                    //     return;
                    // }

                    // return;
                }

                if ($class instanceof ID) {
                    return;
                }

                if (! array_key_exists($slug, $post->attributes['fields'])) {
                    $post->attributes['fields'][$slug] = $post->attributes[$slug];

                    if ($post->isTableField($slug) && method_exists($post, 'markPhysicalFieldAsPacked')) {
                        $post->markPhysicalFieldAsPacked($slug);
                    }
                }

                // Set the field value into nested fields array if it contains a dot
                if (strpos($slug, '.') !== false) {
                    self::setNestedFieldValue($post->attributes['fields'], $slug, $post->attributes[$slug]);
                    // Unset the attribute from the main attributes array
                    // unset($post->attributes[$slug]);
                    unset($post->attributes['fields'][$slug]);
                } else {
                    // If no dot, set the attribute directly in fields
                    // $post->attributes['fields'][$slug] = $post->attributes[$slug];
                }
            }

            if ($slug == 'title') {
                return;
            }

            // Dont unset Field if it is in baseFillable
            if ($post->isTableField($slug)) {
                return;
            }
            // if ($post->usesCustomTable() && $post->usesCustomMeta()) {
            //     return;
            // }

            // Unset fields from the attributes
            unset($post->attributes[$slug]);
        });
    }

    protected function prepareFieldAttributesForPersistence(
        bool $globalWrite = false,
        bool $trustedOwnerIntent = false,
        int|string|null $trustedOwnerId = null,
        bool $trustedTeamIntent = false,
        int|string|null $trustedTeamId = null,
    ): void {
        static::applyInitialPostFields(
            $this,
            $globalWrite,
            $trustedOwnerIntent,
            $trustedOwnerId,
            $trustedTeamIntent,
            $trustedTeamId,
        );
        static::packFieldAttributes($this);
        static::persistMetaFieldsOnSaving($this);
    }

    /**
     * Set a nested field value based on the slug with dots.
     *
     * @param  string  $slug
     * @param  mixed  $value
     * @return void
     */
    protected static function setNestedFieldValue(array &$fields, $slug, $value)
    {
        $keys = explode('.', $slug);
        $temp = &$fields;

        foreach ($keys as $key) {
            if (! isset($temp[$key])) {
                $temp[$key] = [];
            }
            $temp = &$temp[$key];
        }

        $temp = $value;
    }
}
