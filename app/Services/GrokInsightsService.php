<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GrokInsightsService
{
    public function generateInsights(string $prompt): string
    {
        $apiKey = (string) config('services.grok.key');

        if ($apiKey === '') {
            throw new RuntimeException('Grok API key is missing. Set GROK_API_KEY in your .env file.');
        }

        [$baseUrl, $model] = $this->resolveEndpointAndModel($apiKey);

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
                        'content' => 'You are a senior ERP finance analyst. Provide concise, actionable business insights in French with short bullet points.',
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
            throw new RuntimeException('AI analysis request failed: ' . $exception->getMessage());
        }

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');

        if ($content === '') {
            throw new RuntimeException('AI analysis returned an empty response.');
        }

        return trim($content);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveEndpointAndModel(string $apiKey): array
    {
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
