<?php

namespace App\Mail\Join;

use Laravel\Nova\Nova;
use App\Models\JoinSubmission;
use App\Nova\Resources\JoinSubmission as NovaJoinSubmission;

/**
 * Email sent to the board concerning the new member
 *
 * @author Roelof Roos <github@roelof.io>
 * @license MPL-2.0
 */
class BoardJoinMail extends BaseMail
{
    /**
     * Board should not reply to these mails.
     *
     * @var array
     */
    public $replyTo = [];

    /**
     * @inheritDoc
     */
    public function build()
    {
        // Build link to admin panel
        $adminRoute = implode('/', [
            Nova::path(),
            'resources',
            NovaJoinSubmission::uriKey(),
            $this->submission->id
        ]);

        // Render view
        return $this->markdown('mail.join.board')->with(['adminRoute' => $adminRoute]);
    }

    /**
     * @inheritDoc
     */
    protected function createSubject(JoinSubmission $submission): string
    {
        return sprintf('[site] Nieuwe aanmelding van %s.', $submission->name);
    }
}
