<?php

namespace App\Models;

use App\Aura\Collection\MetaCollection;
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

    protected $fillable = ['key', 'value'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'post_meta';

    /**
     * @param  array  $models
     * @return MetaCollection
     */
    public function newCollection(array $models = [])
    {
        return new MetaCollection($models);
    }
}
