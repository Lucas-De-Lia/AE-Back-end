<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use App\Mail\ConfirmationCode;
use Exception;
use Illuminate\Support\Facades\Log;

class SendMailCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $name;
    protected $code;

    public $tries = 5; // Number of times the job should be retried
    public $backoff = 60; // Delay between retries in seconds
    /**
     * Create a new job instance.
     */
    public function __construct($email , $name, $code)
    {
        $this->email = $email;
        $this->name = $name;
        $this->code = $code;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try{
            Mail::to($this->email)->send(new ConfirmationCode($this->code, $this->name));
        }catch (\Exception $e) {
            Log::error($e->getMessage());
            throw $e;
        }

    }
}
