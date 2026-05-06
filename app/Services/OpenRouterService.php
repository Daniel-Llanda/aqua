<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    public function generateHarvestAnalysis(array $context): array
    {
        $apiKey = config('services.openrouter.key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            return [
                'ok' => false,
                'message' => 'AI harvest analysis is unavailable because the OpenRouter API key is not configured.',
                'analysis' => null,
            ];
        }

        $endpoint = (string) config('services.openrouter.endpoint', 'https://openrouter.ai/api/v1/chat/completions');
        $model = (string) config('services.openrouter.model', 'tencent/hy3-preview:free');
        $timeout = (int) config('services.openrouter.timeout', 20);

        $attempts = [
            [
                'name' => 'standard',
                'payload' => $this->buildRequestPayload($model, $this->buildHarvestPrompt($context)),
            ],
            [
                'name' => 'simple-retry',
                'payload' => $this->buildRequestPayload($model, $this->buildSimpleHarvestPrompt($context)),
            ],
        ];

        $lastFailureMessage = 'AI harvest analysis could not be generated right now. Please try again later.';

        foreach ($attempts as $attempt) {
            $result = $this->sendCompletionRequest($endpoint, $apiKey, $timeout, $attempt['payload'], $attempt['name'], $context);

            if ($result['ok']) {
                return $result;
            }

            $lastFailureMessage = $result['message'];
        }

        return $this->failedResponse($lastFailureMessage);
    }

    private function sendCompletionRequest(
        string $endpoint,
        string $apiKey,
        int $timeout,
        array $payload,
        string $attempt,
        array $context
    ): array {
        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout($timeout)
                ->post($endpoint, $payload);
        } catch (\Throwable $exception) {
            Log::warning('OpenRouter harvest analysis request failed.', [
                'attempt' => $attempt,
                'message' => $exception->getMessage(),
                'context' => $this->contextSummary($context),
            ]);

            return $this->failedResponse();
        }

        $json = $this->decodeResponseJson($response);

        if (! $response->successful()) {
            Log::warning('OpenRouter harvest analysis returned an unsuccessful response.', [
                'attempt' => $attempt,
                'status' => $response->status(),
                'error' => data_get($json, 'error.message'),
                'context' => $this->contextSummary($context),
                'response' => $this->safeResponseSummary($json, $response->body()),
            ]);

            return $this->failedResponse($this->errorMessageForStatus($response->status(), $json));
        }

        if (! is_array($json)) {
            Log::warning('OpenRouter harvest analysis returned invalid JSON.', [
                'attempt' => $attempt,
                'status' => $response->status(),
                'context' => $this->contextSummary($context),
                'response' => $this->safeResponseSummary(null, $response->body()),
            ]);

            return $this->failedResponse('AI harvest analysis received an invalid response from OpenRouter.');
        }

        $content = $this->extractAssistantText($json);

        if (! is_string($content) || trim($content) === '') {
            Log::warning('OpenRouter harvest analysis response did not include usable text.', [
                'attempt' => $attempt,
                'status' => $response->status(),
                'context' => $this->contextSummary($context),
                'response' => $this->safeResponseSummary($json, $response->body()),
            ]);

            return $this->failedResponse('OpenRouter returned an empty answer for the harvest analysis.');
        }

        $analysis = $this->cleanAnalysis($content);

        if ($analysis === '') {
            Log::warning('OpenRouter harvest analysis response only contained template or meta text.', [
                'attempt' => $attempt,
                'status' => $response->status(),
                'context' => $this->contextSummary($context),
                'response' => $this->safeResponseSummary($json, $response->body()),
            ]);

            return $this->failedResponse('OpenRouter returned template text instead of harvest analysis.');
        }

        return [
            'ok' => true,
            'message' => null,
            'analysis' => $analysis,
        ];
    }

    private function buildRequestPayload(string $model, string $prompt): array
    {
        return [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an aquaculture analyst. Return only the final answer to the harvest comparison question. Do not include greetings, introductions, labels, headings, bullets, templates, formatting notes, recommendations, reasoning steps, or meta commentary. Base the answer only on the selected pond data and frame causes as likely factors, not certainties.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.2,
            'max_tokens' => 180,
        ];
    }

    private function buildHarvestPrompt(array $context): string
    {
        $json = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Answer this exact question for the selected pond only: why did the cycle with the higher harvest perform better than the other cycle?

If the previous cycle harvested more, explain why the previous cycle may have performed better than the latest cycle.
If the latest cycle harvested more, explain why the latest cycle may have performed better than the previous cycle.

Use only the supplied JSON data, especially harvest quantity, fish species, pH, water temperature, ammonia, hatching quantity, expected harvest, and cycle duration when present.
Return only the final answer in no more than 3 short sentences.
Do not include labels, headings, bullets, templates, recommendations, formatting instructions, or phrases such as "the analysis should", "here is the analysis", "based on the provided data", "format", "summary", "reason", or "recommendation".

POND DATA JSON:
{$json}
PROMPT;
    }

    private function buildSimpleHarvestPrompt(array $context): string
    {
        $json = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Answer only why the higher-harvest cycle performed better for this selected pond.

Use the previous and latest cycle JSON values directly, including harvest quantity, species, hatching, expected harvest, duration, pH, temperature, or ammonia when useful.
Return no more than 3 short sentences and do not include labels, templates, instructions, recommendations, or meta text.

POND JSON:
{$json}
PROMPT;
    }

    private function decodeResponseJson($response): ?array
    {
        try {
            $json = $response->json();
        } catch (\Throwable) {
            return null;
        }

        return is_array($json) ? $json : null;
    }

    private function extractAssistantText(array $json): ?string
    {
        $paths = [
            'choices.0.message.content',
            'choices.0.text',
            'choices.0.message.reasoning',
            'choices.0.delta.content',
            'output_text',
            'output.0.content.0.text',
            'output.0.content.0.output_text',
        ];

        foreach ($paths as $path) {
            $text = $this->stringifyTextCandidate(data_get($json, $path));

            if ($text !== null && trim($text) !== '') {
                return $text;
            }
        }

        $choice = data_get($json, 'choices.0');

        if (is_array($choice)) {
            return $this->findFirstNestedText($choice);
        }

        return null;
    }

    private function stringifyTextCandidate(mixed $candidate): ?string
    {
        if (is_string($candidate)) {
            return $candidate;
        }

        if (! is_array($candidate)) {
            return null;
        }

        if (isset($candidate['text']) && is_string($candidate['text'])) {
            return $candidate['text'];
        }

        if (isset($candidate['output_text']) && is_string($candidate['output_text'])) {
            return $candidate['output_text'];
        }

        if (isset($candidate['summary']) && is_string($candidate['summary'])) {
            return $candidate['summary'];
        }

        if (isset($candidate['summary']) && is_array($candidate['summary'])) {
            $summary = collect($candidate['summary'])
                ->map(fn (mixed $item) => $this->stringifyTextCandidate($item))
                ->filter()
                ->implode("\n");

            return $summary !== '' ? $summary : null;
        }

        if (! array_is_list($candidate)) {
            return null;
        }

        $parts = collect($candidate)
            ->map(fn (mixed $item) => $this->stringifyTextCandidate($item))
            ->filter()
            ->values();

        return $parts->isNotEmpty() ? $parts->implode("\n") : null;
    }

    private function findFirstNestedText(array $data): ?string
    {
        foreach ($data as $key => $value) {
            if (in_array($key, ['content', 'text', 'output_text', 'reasoning', 'summary'], true)) {
                $text = $this->stringifyTextCandidate($value);

                if ($text !== null && trim($text) !== '') {
                    return $text;
                }
            }

            if (is_array($value)) {
                $nested = $this->findFirstNestedText($value);

                if ($nested !== null && trim($nested) !== '') {
                    return $nested;
                }
            }
        }

        return null;
    }

    private function cleanAnalysis(string $content): string
    {
        $content = strip_tags($content);
        $content = preg_replace('/```(?:\w+)?|```/', ' ', $content) ?? $content;
        $content = preg_replace('/(?:^|\s)#{1,6}\s*/', ' ', $content) ?? $content;
        $content = preg_replace('/(?:^|\s)\d+[.)]\s+/', ' ', $content) ?? $content;
        $content = preg_replace('/\b(?:summary|reason|possible reasons|water quality observations|recommendations|final answer|final analysis|analysis)\s*(?:-|:)\s*/i', ' ', $content) ?? $content;
        $content = preg_replace('/\b(?:format|output|response)\s*(?:-|:)\s*/i', ' ', $content) ?? $content;
        $content = preg_replace('/(?:^|\s)[-*]\s+/', ' ', $content) ?? $content;
        $content = preg_replace('/\s+/', ' ', $content) ?? $content;

        $sentences = preg_split('/(?<=[.!?])\s+/', trim($content), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $usefulSentences = collect($sentences)
            ->map(fn (string $sentence) => $this->cleanAnalysisSentence($sentence))
            ->filter(fn (string $sentence) => $sentence !== '' && ! $this->isMetaSentence($sentence))
            ->values()
            ->take(3)
            ->all();

        $content = $usefulSentences !== []
            ? implode(' ', $usefulSentences)
            : '';

        return mb_substr($content, 0, 900);
    }

    private function cleanAnalysisSentence(string $sentence): string
    {
        $sentence = trim($sentence);
        $sentence = preg_replace('/^\d+[.)]\s*/', '', $sentence) ?? $sentence;
        $sentence = preg_replace('/^(?:summary|reason|possible reasons|water quality observations|recommendations|final answer|final analysis|analysis)\s*(?:-|:)\s*/i', '', $sentence) ?? $sentence;
        $sentence = preg_replace('/^(?:based on|using only|from)\s+(?:the\s+)?(?:provided|supplied|selected pond|pond)\s+(?:json|data|information|records),?\s*/i', '', $sentence) ?? $sentence;
        $sentence = preg_replace('/^(?:based on|from)\s+(?:the\s+)?data,?\s+(?:the\s+)?analysis\s+(?:is|would be)\s*:?\s*/i', '', $sentence) ?? $sentence;
        $sentence = preg_replace('/^(?:here(?:\s+is|\'s)\s+)?(?:the\s+)?analysis\s+(?:is|would be)\s*:?\s*/i', '', $sentence) ?? $sentence;
        $sentence = preg_replace('/\b(?:based on|using)\s+(?:the\s+)?provided\s+(?:json|data)\b/i', '', $sentence) ?? $sentence;
        $sentence = preg_replace('/\s+/', ' ', $sentence) ?? $sentence;
        $sentence = trim($sentence, " \t\n\r\0\x0B:-");

        if (preg_match('/^[a-z]/', $sentence) && ! str_starts_with($sentence, 'pH')) {
            $sentence = mb_strtoupper(mb_substr($sentence, 0, 1)).mb_substr($sentence, 1);
        }

        return $sentence;
    }

    private function isMetaSentence(string $sentence): bool
    {
        return collect([
            '/^(?:got it|sure|okay|ok|certainly)\b/i',
            '/^(?:let\'?s|let us)\b/i',
            '/^wait\b/i',
            '/^first[, ]+i\b/i',
            '/^first\b.*\b(?:question|prompt|user)\b.*\b(?:says|asks|said|asked)\b/i',
            '/^i\s+(?:need|will|should|am going|can|have)\s+to\b/i',
            '/^i\s+will\s+now\b/i',
            '/\b(?:the\s+)?question\s+(?:says|asks|is)\b/i',
            '/\b(?:the\s+)?user\s+(?:said|asked|says|wants)\b/i',
            '/\b(?:this|that|it)\s+is\s+a\s+format\s+question\b/i',
            '/^the\s+(?:task|instruction|prompt)\s+(?:asks|requires|says)\b/i',
            '/^here(?:\s+is|\'s)\s+(?:the|a)\b/i',
            '/^to\s+(?:answer|analyze|comply)\b/i',
            '/^(?:the\s+)?(?:analysis|output|response)\s+(?:should|must|needs to|will|would)\b/i',
            '/^(?:this|the)\s+sentence\s+should\b/i',
            '/^(?:write|return|use|format|include|mention|explain)\b.*\b(?:sentence|format|output|analysis|response)\b/i',
            '/^format\s*:/i',
            '/^use\s+this\b/i',
            '/^use\s+this\s+format\b/i',
            '/^the\s+final\s+answer\s+should\b/i',
            '/^(?:summary|reason|reasons|recommendation|recommendations)(?:,|\b)/i',
            '/\b(?:sentence|text|part)\s+should\s+be\s+(?:removed|deleted|ignored)\b/i',
            '/^(?:for\s+the\s+)?next\s+cycle\b/i',
            '/^(?:keep|continue|monitor|maintain|adjust|improve|record|track)\b.*\b(?:next cycle|water quality|ph|ammonia|temperature|feeding|hatching|logs?)\b/i',
        ])->contains(fn (string $pattern) => (bool) preg_match($pattern, $sentence));
    }

    private function errorMessageForStatus(int $status, ?array $json): string
    {
        $apiMessage = data_get($json, 'error.message');

        if (is_string($apiMessage) && trim($apiMessage) !== '') {
            return 'OpenRouter API error: '.$apiMessage;
        }

        return match ($status) {
            401, 403 => 'OpenRouter rejected the API key or request permissions.',
            408 => 'OpenRouter timed out while generating the harvest analysis.',
            422 => 'OpenRouter could not process the harvest analysis request.',
            429 => 'OpenRouter rate limit reached while generating the harvest analysis.',
            default => 'OpenRouter could not generate the harvest analysis right now.',
        };
    }

    private function contextSummary(array $context): array
    {
        return [
            'pond_id' => data_get($context, 'pond.id'),
            'previous_cycle' => data_get($context, 'comparison.previous_cycle_number'),
            'latest_cycle' => data_get($context, 'comparison.latest_cycle_number'),
            'previous_total_kg' => data_get($context, 'comparison.previous_total_harvest_kg'),
            'latest_total_kg' => data_get($context, 'comparison.latest_total_harvest_kg'),
            'previous_telemetry_records' => data_get($context, 'previous_cycle.telemetry_summary.record_count'),
            'latest_telemetry_records' => data_get($context, 'latest_cycle.telemetry_summary.record_count'),
        ];
    }

    private function safeResponseSummary(?array $json, string $rawBody): array
    {
        return [
            'has_json' => is_array($json),
            'error' => is_array($json) ? data_get($json, 'error.message') : null,
            'finish_reason' => is_array($json) ? data_get($json, 'choices.0.finish_reason') : null,
            'has_message_content' => is_array($json) && trim((string) $this->stringifyTextCandidate(data_get($json, 'choices.0.message.content'))) !== '',
            'has_message_reasoning' => is_array($json) && trim((string) $this->stringifyTextCandidate(data_get($json, 'choices.0.message.reasoning'))) !== '',
            'has_reasoning_details' => is_array($json) && data_get($json, 'choices.0.message.reasoning_details') !== null,
            'body_snippet' => mb_substr($rawBody, 0, 2000),
        ];
    }

    private function failedResponse(string $message = 'AI harvest analysis could not be generated right now. Please try again later.'): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'analysis' => null,
        ];
    }
}
