<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmationLink;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Exception;
use Illuminate\Support\Facades\Log;

class SendVerifyMailJob implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $id;
    protected $code;


    public $tries = 5; // Number of times the job should be retried
    public $backoff = 60; // Delay between retries in seconds
    /**
     * Create a new job instance.
     */
    public function __construct($user, $id, $code)
    {
        $this->user = $user;
        $this->id = $id;
        $this->code = $code;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try{
            Mail::to($this->user->email)->send(new ConfirmationLink($this->user->name, $this->id, $this->code));
        }catch(Exception $e){
            Log::error($e->getMessage());
            throw $e;
        }
    }
}
