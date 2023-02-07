<?php

namespace App\Jobs;

use App\Aura\Resources\Attachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateImageThumbnail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The attachment to generate a thumbnail for.
     *
     * @var Attachment
     */
    public $attachment;

    /**
     * Create a new job instance.
     *
     * @param  Attachment  $attachment
     * @return void
     */
    public function __construct(Attachment $attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // $this->attachment->jobs()->create([
        //     'job_id' => $this->job->getJobId(),
        //     'job_status' => 'pending',
        // ]);

        // sleep 10 seconds to simulate a long running job
        // sleep(2);

        // // Load the original image
        // $image = Image::make($this->attachment->path);

        // // Generate a thumbnail image
        // $image->fit(100, 100);

        // // Save the thumbnail image to disk
        // $image->save($this->attachment->thumbnail_path);
    }
}
