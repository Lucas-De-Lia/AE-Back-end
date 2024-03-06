<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgotPassMail;
use Exception;
use Illuminate\Support\Facades\Log;
class SendMailForgotPassJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $name;
    protected $token;

    public $tries = 5; // Number of times the job should be retried
    public $backoff = 60; // Delay between retries in seconds
    /**
     * Create a new job instance.
     */
    public function __construct($email,$name , $token)
    {
        $this->email = $email;
        $this->name = $name;
        $this->token = $token;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try{
            Mail::to($this->email)->send(new ForgotPassMail($this->name,$this->token));
        }catch(Exception $e){
            Log::error($e->getMessage());
            throw $e;
        }
    }
}
