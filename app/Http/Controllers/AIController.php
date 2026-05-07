<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

final class AIController extends Controller
{
    public function complete(): ResponseInterface
    {
        $prompt = $this->request()->getParsedBody()['prompt'] ?? 'Hello, how are you?';

        $response = ai()->complete($prompt);

        return $this->json([
            'prompt' => $prompt,
            'response' => $response,
        ]);
    }

    public function chat(): ResponseInterface
    {
        $message = $this->request()->getParsedBody()['message'] ??
            $this->request()->getQueryParams()['message'] ?? '';

        $chat = ai()->conversation('You are a helpful Marwa PHP developer assistant.');
        $chat->user($message);

        $response = $chat->send()->getContent();

        return $this->json([
            'message' => $message,
            'response' => $response,
        ]);
    }

    public function stream(): void
    {
        $prompt = $this->request()->getParsedBody()['prompt'] ??
            $this->request()->getQueryParams()['prompt'] ?? 'Write a short story';

        $this->response('', 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
        ]);

        ai()->stream($prompt, function (string $chunk): void {
            echo $chunk;
            flush();
        });
    }

    public function embed(): ResponseInterface
    {
        $text = $this->request()->getParsedBody()['text'] ??
            $this->request()->getQueryParams()['text'] ?? 'Hello world';

        $result = ai()->embed([$text]);

        return $this->json([
            'text' => $text,
            'embedding' => $result->getVector(),
            'dimensions' => count($result->getVector()),
        ]);
    }

    public function image(): ResponseInterface
    {
        $prompt = $this->request()->getParsedBody()['prompt'] ??
            $this->request()->getQueryParams()['prompt'] ?? 'A sunset over mountains';

        $result = ai()->image($prompt);

        return $this->json([
            'prompt' => $prompt,
            'url' => $result->getUrl(),
        ]);
    }

    public function tools(): ResponseInterface
    {
        $question = $this->request()->getParsedBody()['question'] ??
            $this->request()->getQueryParams()['question'] ?? 'What is the current time?';

        $response = ai()
            ->tool([
                'name' => 'get_current_time',
                'description' => 'Get the current system time',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ])
            ->conversation('You are a helpful assistant.')
            ->user($question)
            ->send()
            ->getContent();

        return $this->json([
            'question' => $question,
            'response' => $response,
        ]);
    }

    public function providers(): ResponseInterface
    {
        return $this->json([
            'available' => ai()->providers(),
            'default' => ai()->configuration()['default'] ?? 'none',
            'config' => ai()->configuration(),
        ]);
    }
}
