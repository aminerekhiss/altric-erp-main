<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GrokWorkflowService
{
    /**
     * @return array{raw:string,title:string,summary:string,steps:array<int,array<string,mixed>>}
     */
    public function generate(string $prompt): array
    {
        $apiKey = (string) config('services.grok.key');
        [$baseUrl, $model] = $this->resolveEndpointAndModel($apiKey);

        if ($apiKey === '') {
            throw new RuntimeException('Grok API key is missing. Set GROK_API_KEY in your .env file.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(90)
            ->post($baseUrl . '/chat/completions', [
                'model' => $model,
                'temperature' => 0.2,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You generate operational workflows for ERP teams. Return ONLY valid JSON with keys: title, summary, steps. steps must be an array of objects containing: name, description, owner, tools.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Grok request failed: ' . $exception->getMessage());
        }

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');

        if ($content === '') {
            throw new RuntimeException('Grok returned an empty response.');
        }

        $decoded = $this->decodeWorkflowJson($content);

        return [
            'raw' => $content,
            'title' => (string) ($decoded['title'] ?? 'Generated Workflow'),
            'summary' => (string) ($decoded['summary'] ?? ''),
            'steps' => is_array($decoded['steps'] ?? null) ? $decoded['steps'] : [],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeWorkflowJson(string $content): array
    {
        $trimmed = trim($content);

        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```(?:json)?\\s*/i', '', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('/\\s*```$/', '', $trimmed) ?? $trimmed;
        }

        $decoded = json_decode($trimmed, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $content, $matches) === 1) {
            $decoded = json_decode($matches[0], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'title' => 'Generated Workflow',
            'summary' => $content,
            'steps' => [],
        ];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveEndpointAndModel(string $apiKey): array
    {
        // gsk_ keys belong to Groq; xAI Grok keys usually use xai- prefix.
        if (str_starts_with($apiKey, 'gsk_')) {
            $baseUrl = rtrim((string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/');
            $model = (string) config('services.groq.model', 'llama-3.3-70b-versatile');

            return [$baseUrl, $model];
        }

        $baseUrl = rtrim((string) config('services.grok.base_url', 'https://api.x.ai/v1'), '/');
        $model = (string) config('services.grok.model', 'grok-3-mini');

        return [$baseUrl, $model];
    }
}
