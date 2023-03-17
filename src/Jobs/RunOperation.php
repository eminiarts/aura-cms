<?php

namespace Eminiarts\Aura\Jobs;

use Aura\Flows\Resources\Operation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunOperation implements ShouldQueue
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
    public function __construct(public Operation $operation, public $post, public $flowLogId, public $data = null)
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
        $request = [
            'post' => $this->post->id,
            'operation' => $this->operation->type,
        ];
        // create a operation_log
        $operationLog = $this->operation->logs()->create([
            'post_id' => $this->post->id,
            'status' => 'running',
            'flow_log_id' => $this->flowLogId,
            'started_at' => now(),
            'request' => json_encode($request),
        ]);

        try {
            // Run the operation
            $data = app($this->operation->type)->run($this->operation, $this->post, $operationLog, $this->data);

            // Operation is finished
            $operationLog->status = 'success';
            $operationLog->finished_at = now();
            $operationLog->save();

            // Resolve the next operation
            if ($this->operation->resolve) {
                return $this->operation->resolve->run($this->post, $this->flowLogId, $data);
            }

            // If there is no resolve and no reject, then the flow is done
            if (! $this->operation->resolve) {
                $this->operation->flow->logs()->find($this->flowLogId)->update([
                    'status' => 'finished',
                    'finished_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            // Run the operation
            // app($this->operation->type)->run($this->operation, $this->post, $operationLog);

            // Operation is finished
            $operationLog->status = 'failed';
            $operationLog->response = [
                'message' => $e->getMessage(),
            ];
            $operationLog->finished_at = now();
            $operationLog->save();

            // Run the reject operation
            if ($this->operation->reject) {
                $this->operation->reject->run($this->post, $this->flowLogId, $this->data);
            }

            if (! $this->operation->reject) {
                $this->operation->flow->logs()->find($this->flowLogId)->update([
                    'status' => 'finished',
                    'finished_at' => now(),
                ]);
            }
        }
    }
}
