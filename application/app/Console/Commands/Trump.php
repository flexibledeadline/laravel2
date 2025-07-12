<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Laravel\Facades\Telegram;

class Trump extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:trump';

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
        $yesterday = now()->subDay()->format('d.m.Y');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.env('OPENAI_API_KEY'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.openai.com/v1/responses', [
            "model"             => "gpt-4.1",
            "input"             => [
                [
                    "role"    => "system",
                    "content" => [
                        [
                            "type" => "input_text",
                            "text" => <<<PROMPT
Use web search feature to check and prepare highlights of what Trump said {$yesterday}.
6 quotes max. 

Respond in JSON with such format: 
[
{
 'quote_en' => 'Trump's quote',
 'quote_ua' => 'Translated Trump's quote',
}
]
PROMPT
                        ]
                    ]
                ]
            ],
            "text"              => [
                "format" => [
                    "type" => "text"
                ]
            ],
            "reasoning"         => new \stdClass(),
            "tools"             => [
                [
                    "type"                => "web_search_preview",
                    "user_location"       => [
                        "type" => "approximate"
                    ],
                    "search_context_size" => "medium"
                ]
            ],
            "temperature"       => 1,
            "max_output_tokens" => 2048,
            "top_p"             => 1,
            "store"             => true,
        ]);

        $result = $response->json();
        $raw    = $result['output'][1]['content'][0]['text'] ?? null;

        $quotes = collect(json_decode($raw, true));

        $formatted = $quotes->map(function ($item, $i) {
            $quote = $item['quote_ua'] ?? '';
            $quote = htmlspecialchars($quote, ENT_QUOTES | ENT_XML1);

            return '<i>'.($i + 1).'. "'.$quote.'"</i>';
        })->implode("\n");

        $message = "<b>[{$yesterday}] Трамп: </b>\n".$formatted;

        if (! empty($formatted)) {
            Telegram::bot('samurai')->sendMessage(
                [
                    'chat_id'    => '@TrumpRecentlySaid',
                    'text'       => $message,
                    'parse_mode' => 'HTML',
                ]
            );
        }
    }
}
