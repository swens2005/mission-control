<?php

namespace App\Support\Ai;

/**
 * What one call costs, in millionths of a US dollar (ADR 0014).
 *
 * Claude Sonnet 5.5 costs $2 per million input tokens and $10 per million
 * output tokens: 2 and 10 micro-dollars a token, so whole numbers all the
 * way. Cache tokens count at the full input price; the assistant doesn't
 * cache, and over-counting is the safe side of a cap.
 */
final class TokenCost
{
    public const INPUT_MICRO_USD_PER_TOKEN = 2;

    public const OUTPUT_MICRO_USD_PER_TOKEN = 10;

    public static function microUsd(int $inputTokens, int $outputTokens): int
    {
        return $inputTokens * self::INPUT_MICRO_USD_PER_TOKEN
            + $outputTokens * self::OUTPUT_MICRO_USD_PER_TOKEN;
    }

    /**
     * Input and output tokens from the API's `usage` object.
     *
     * @param  array<string, mixed>  $usage
     * @return array{0: int, 1: int}
     */
    public static function tokens(array $usage): array
    {
        $count = fn (string $key): int => is_int($usage[$key] ?? null) ? max(0, $usage[$key]) : 0;

        return [
            $count('input_tokens') + $count('cache_creation_input_tokens') + $count('cache_read_input_tokens'),
            $count('output_tokens'),
        ];
    }

    /**
     * "$1.23" for messages; floats only for display.
     */
    public static function format(int $microUsd): string
    {
        return '$'.number_format($microUsd / 1_000_000, 2);
    }
}
