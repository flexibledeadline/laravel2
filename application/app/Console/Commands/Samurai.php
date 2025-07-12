<?php

namespace App\Console\Commands;

use App\Services\QuoteDeduplicator;
use Illuminate\Console\Command;
use OpenAI\Laravel\Facades\OpenAI;
use Telegram\Bot\Laravel\Facades\Telegram;

class Samurai extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:samurai';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // extract to quotes service
        $result = OpenAI::chat()->create([
            'model'    => 'gpt-4.1-mini',
            'messages' => [
                [
                    'role'    => 'user',
                    'content' => <<<PROMPT
Generate a random 5 samurai quotes in the following Telegram-safe HTML format:

<i>"[QUOTE_TEXT]"</i>
— <b>[AUTHOR_NAME]</b>

- Quotes should be separated with @@@@@@
- The quote must be authentic or stylistically consistent with samurai philosophy.
- Use historical figures or anonymous traditional sayings.
- Quote is likely to be related to current date.
- Try to not repeat previous quotes.
- Do not include any unsupported HTML tags like <br> or <div>.
- The quote must be no longer than 300 characters.
- Output only the formatted quote — no intro, no explanation.
PROMPT,
                ],
            ],
        ]);

        // Extract service
        $deduplicator = new QuoteDeduplicator();

        // Extract samurai
        foreach (explode('@@@@@@', $result->choices[0]->message->content) as $quote) {
            $quote = trim($quote);

            if ($deduplicator->isDuplicate($quote)) {
                echo "Duplicate quote detected. Try another.\n";
            } else {
                $deduplicator->addQuote($quote);
                Telegram::bot('samurai')->sendMessage(
                    [
                        'chat_id'    => '@DailySamurai',
                        'text'       => $quote,
                        'parse_mode' => 'HTML',
                    ]
                );
                echo "Quote added!\n";
                break;
            }
        }
    }
}
