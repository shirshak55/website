<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\JoinRequest;

/**
 * Mail sent to the board when a user wants to join
 *
 * @author Roelof Roos <github@roelof.io>
 * @license MPL-2.0
 */
class JoinBoardMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The join request to handle
     *
     * @var JoinRequest
     */
    protected $request;

    /**
     * Creates an email for the user about their registration
     *
     * @param JoinRequest $request
     */
    public function __construct(JoinRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('mail.join-board')->with([
            'request' => $this->request
        ]);
    }
}