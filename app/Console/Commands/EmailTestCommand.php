<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EmailTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the SMTP email connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Mail::raw('This is a test email to check SMTP connection.', function ($message) {
            $message->to('ignacio21496@gmail.com')->subject('SMTP Connection Test');
        });

        $this->info('Test email sent successfully!');
    }
}
