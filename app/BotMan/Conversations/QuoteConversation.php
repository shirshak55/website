<?php

declare(strict_types=1);

namespace App\BotMan\Conversations;

use App\BotMan\Traits\HasGroupCheck;
use App\Helpers\Arr;
use App\Helpers\Str;
use App\Models\BotQuote;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

class QuoteConversation extends InvokableConversation
{
    use HasGroupCheck;

    private const STR_THANKS = [
        'Bedankt voor het insturen van je wist-je-datje, :name!',
        'Whoop, lekker bezig :name, ik stuur \'m door naar de Gumbode',
        'Bleep bloop, opgeslagen onder "Chantagemateriaal van :name".',
        'Waarom doe je mij dit aan? Nouja, tijd om dit door te delegeren naar de Gumbode',
        'Ik weet niet waar je het vandaan haalt, en ik wil het ook niet weten',
        '<em>I got nothig</em>',
        'Houd dat wist-je-datje alsjeblieft 1,5 meter bij mij vandaan!',
        'Oeh, <em>juicy</em>.',
    ];

    private const STR_ASK = [
        'Nee :name, zo werkt dat niet. Wat wou je insturen?',
        'Ik ga niet wéér een lege quote opslaan :name, wat is je wist-je-datje?',
        'Bleep bloop, geeef bericht.',
        '<em>Michael Nicht</em>: Klootviool, wat doe je?',
        'Maak je altijd van die lege beloftes :name?',
        'Ik denk dat je even voor de herkansing moet :name...',
        'Je wist-je-datje hoeft niet 1,5 meter afstand te houden van het commando hoor.',
        'Ik bedoel het goed, maar weet niet wat ik hiermee moet 🥺',
    ];

    private const STR_CANCEL = [
        'Is goed, we doen alsof er niks gebeurt is',
        'No worries, iedereen maakt fouten',
        'Nou, dan niet hé'
    ];

    private const STOP_COMMANDS = [
        'stop',
        'cancel',
        '/stop',
        '/cancel'
    ];

    /**
     * Stops the conversation when 'stop' or 'cancel' is spoken.
     * @param IncomingMessage $message
     * @return bool
     */
    public function stopsConversation(IncomingMessage $message)
    {
        if (\in_array(Str::lower(trim($message->getText())), self::STOP_COMMANDS)) {
            $this->getBot()->randomReply(self::STR_CANCEL);
            return true;
        }

        return false;
    }

    /**
     * First question
     */
    public function askForQuote()
    {
        // Ask the question
        $prompt = \str_replace(':name', $this->getName(), Arr::random(self::STR_ASK));

        // Check if we're in a group, and send the message separately
        if ($this->isInGroup()) {
            $this->say(<<<TEXT
            {$prompt}

            Probeer het opnieuw, maar zet je wist-je-datje achter het commando: <code>/wjd [wist-je-datje]</code>.
            TEXT);
            return;
        }

        // Ask it
        $this->ask($prompt, function (Answer $response) {
            $this->storeQuote($response->getText());
        }, [
            'auto-reply' => true
        ]);
    }

    protected function storeQuote(string $quote)
    {
        // Get bot and start typing
        $this->getBot()->types();

        // Save the quote
        $quote = BotQuote::create([
            'user_id' => optional($this->getUser())->id,
            'display_name' => $this->getName(),
            'quote' => $quote,
        ]);

        // Reply about it
        $joke = str_replace(':name', $this->getName(), Arr::random(self::STR_THANKS));
        $this->say("{$joke}\n\n💾 Je wist-je-datje is opgeslagen");
    }

    /**
     * Start the conversation
     */
    public function run()
    {
        // Check for quote
        $bot = $this->getBot();

        // Get whole message, in case of multi-line
        $message = $bot->getMessage()->getText();

        // Trim "/wjd" and "/wjd@[username]"
        $quote = trim(preg_replace('/^\/wjd(\@\w+)?\s+/i', '', $message));

        // Ask for the quote
        if (empty($quote)) {
            $this->askForQuote();
            return;
        }

        // Store the quote
        $this->storeQuote($quote);
    }
}
