<?php

namespace Eminiarts\Aura\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostJob extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'post_id', 'job_id', 'job_status',
    ];

    // table is called post_job
    protected $table = 'post_job';

    /**
     * Get the post that the job belongs to.
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
