<?php

namespace Aura\Base\Models;

use Aura\Base\Collection\MetaCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = ['key', 'value', 'metable_type', 'metable_id'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'meta';

    /**
     * Get the owning metable model.
     */
    public function metable()
    {
        return $this->morphTo();
    }

    /**
     * @return MetaCollection
     */
    public function newCollection(array $models = [])
    {
        return new MetaCollection($models);
    }
}
