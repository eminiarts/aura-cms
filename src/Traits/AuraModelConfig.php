<?php

namespace Aura\Base\Traits;

/**
 * Aggregator trait mixed into every Resource (and BaseResource) — the stable
 * public surface behind which the resource configuration concerns live.
 *
 * The individual concerns are decomposed into Concerns\Aura* sub-traits (issue
 * #40). This trait exists only to preserve the `use AuraModelConfig` contract
 * every host-app resource depends on; it composes the sub-traits and keeps the
 * team() relation (consumed directly by BaseResource).
 *
 * The storage strategy (posts+meta / posts-no-meta / custom-no-meta /
 * custom+meta) lives in Concerns\AuraResourceMeta; its decision matrix is
 * documented on isMetaField()/isTableField() and pinned by
 * tests/Feature/Resource/StorageMatrixTest.php. See CONTEXT.md for the wider
 * resource/domain vocabulary.
 */
trait AuraModelConfig
{
    use Concerns\AuraQueriesMeta;
    use Concerns\AuraResourceActions;
    use Concerns\AuraResourceComponents;
    use Concerns\AuraResourceConfiguration;
    use Concerns\AuraResourceIdentity;
    use Concerns\AuraResourceMeta;
    use Concerns\AuraResourceNavigation;
    use Concerns\AuraResourceTableConfig;
    use Concerns\AuraResourceTaxonomy;
    use Concerns\AuraResourceUrls;
    use Concerns\AuraResourceViews;

    public function team()
    {
        return $this->belongsTo(config('aura.resources.team'));
    }
}
