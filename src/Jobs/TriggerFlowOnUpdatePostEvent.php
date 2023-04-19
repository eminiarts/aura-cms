<?php

namespace Eminiarts\Aura\Jobs;

use Aura\Flows\Resources\Flow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TriggerFlowOnUpdatePostEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public $post)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Is there a Flow with 'trigger' == 'post' and 'options.resource' == 'Post' and 'options.event' == 'created'
        // If so, run the Flow

        // dump($this->post->type);

        $flows = Flow::where('trigger', 'post')
            ->where('options->resource', get_class($this->post))
            ->where('options->event', 'updated')
            ->get();

        // dump(Flow::get());

        if ($flows && $flows->count() > 0) {
            // Trigger the Flow
            // get the Flow's Operation
            // Run the Operation
            foreach ($flows as $flow) {
                // Get the Operation
                $operation = $flow->operation;

                // Create a Flow Log
                $flowLog = $flow->logs()->create([
                    'post_id' => $this->post->id,
                    'status' => 'running',
                ]);

                // Run the Operation
                $operation->run($this->post, $flowLog->id);
            }
        }
    }
}
