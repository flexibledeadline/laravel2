<?php

namespace App\Services;

use App\Models\SamuraiQuote;
use OpenAI\Laravel\Facades\OpenAI;

class QuoteDeduplicator
{

    protected float $threshold;

    public function __construct(float $threshold = 0.85)
    {
        $this->threshold = $threshold;
    }

    protected function generateEmbedding(string $quoteText): ?array
    {
        try {
            $response = OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-large',
                'input' => $quoteText,
            ]);

            return $response['data'][0]['embedding'] ?? null;
        } catch (\Exception $e) {
            // log error if needed
            return null;
        }
    }

    protected function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dot   = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $len   = count($vec1);

        for ($i = 0; $i < $len; $i++) {
            $dot   += $vec1[$i] * $vec2[$i];
            $normA += $vec1[$i] ** 2;
            $normB += $vec2[$i] ** 2;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    public function isDuplicate(string $quoteText): bool
    {
        $newEmbedding = $this->generateEmbedding($quoteText);

        if (! $newEmbedding) {
            return false;
        }

        $storedQuotes = SamuraiQuote::all();

        foreach ($storedQuotes as $stored) {
            $similarity = $this->cosineSimilarity($newEmbedding, $stored->embedding);
            if ($similarity >= $this->threshold) {
                return true;
            }
        }

        return false;
    }

    public function addQuote(string $quoteText): bool
    {
        $embedding = $this->generateEmbedding($quoteText);
        if (! $embedding) {
            return false;
        }

        SamuraiQuote::create([
            'quote'     => $quoteText,
            'embedding' => $embedding,
        ]);

        return true;
    }
}
