@php
// Load the jobs for the post
$jobs = $this->model->jobs;

// Jobs Redis
if($jobs){
    // foreach ($jobs as $job) {
        //     // Get the job ID
        //     $jobId = $job->job_id;
        
        //     // Get the status of the job
        //     $status = \Illuminate\Support\Facades\Queue::connection()->getJobStatus($jobId);
        
        //     // Display the job ID and status
        //     echo "Job ID: $jobId<br>";
        //     echo "Status: $status<br>";
        // }
        
        
        // Jobs Database
        foreach ($jobs as $job) {
            // Get the job ID
            $jobId = $job->job_id;

            // get queue from db 
            $queueJob = DB::table('jobs')->where('id', $jobId)->first();

            // if there is no queue job, delete the job from the database
            if (!$queueJob) {
                $job->delete();
                continue;
            }
            
            // Check the status of the job
            if ($queueJob->attempts > 0) {
                // The job has been attempted, so it is either processing or has failed
                if ($queueJob->reserved_at) {
                    $status = "processing";
                } else {
                    $status = "failed";
                }
            } else {
                // The job has not been attempted yet, so it is queued
                $status = "queued";
            }

            echo "Job: " . json_decode($queueJob->payload)->displayName;

            
            // Display the job ID and status
            echo "Job ID: $jobId<br>";
            echo "Status: $status<br>";
        }
    }
    
    
    @endphp