<?php

namespace Src\Controllers;

use Sentience\Abstracts\Controller;
use Sentience\Ai\Ai;
use Sentience\Ai\Api;
use Sentience\Ai\Schema\Schema;
use Sentience\Helpers\Json;
use Sentience\Sentience\Request;
use Sentience\Sentience\Stdio;

class AiController extends Controller
{
    public function __construct(protected ?Request $request)
    {
    }

    public function ai(array $words, array $flags): void
    {
        $ai = Ai::connect(
            // Api::OpenAI,
            Api::Anthropic,
            'http://localhost:1234',
            'abcdefgh12345678'
        );

        $prompt = $ai->prompt(
            // 'qwen3.6-35b-a3b-mtp',
            'google/gemma-4-e4b',
            // 'granite-4.1-8b',
            'Review the provided files and summarize their contents, and give me the weather for Amsterdam'
        );

        $prompt->withSystemPrompt('You are a helpful assistant that reviews files. Answer and reason in maximum 2 sentences');

        $prompt->withAttachment(SENTIENCE_DIR . '/docker-compose.yml');

        $prompt->withRawAttachment(
            'I love you PHP, you are my world',
            // 'loveletter.txt'
        );

        $prompt->withTool(
            'get_weather_info',
            fn (string $city): string => $this->getWeatherInfo($city)
        );

        $prompt->withStream();

        $prompt->withStructuredOutput(
            Schema::object([
                'files' => Schema::array(
                    Schema::object([
                        'filetype' => Schema::string(),
                        'contents_summary' => Schema::string()
                    ])
                ),
                'weather' => Schema::string()
            ])
        );

        Stdio::printLn('Running prompt');

        $responses = $prompt->execute();

        foreach ($responses as $response) {
            while ($response->read()) {
                Stdio::printLn(
                    Json::encode(
                        [
                            'content' => $response->getContent(),
                            'reasoning' => $response->getReasoningContent(),
                            'tool_calls' => $response->getToolCalls(),
                            'finish_reason' => $response->getFinishReason(),
                            'structured_output' => $response->getStructuredOutput()
                        ],
                        JSON_PRETTY_PRINT
                    )
                );
            }
        }

        $responses = $responses
            ->continue('Can you write all sumaries and weathers in Dutch')
            ->withStructuredOutput(Schema::object([
                'files' => Schema::array(
                    Schema::object([
                        'filetype' => Schema::string(),
                        'contents_summary_in_dutch' => Schema::string()
                    ])
                ),
                'weather_in_dutch' => Schema::string()
            ]))
            ->execute();

        foreach ($responses as $response) {
            while ($response->read()) {
                Stdio::printLn(
                    Json::encode(
                        [
                            'content' => $response->getContent(),
                            'reasoning' => $response->getReasoningContent(),
                            'tool_calls' => $response->getToolCalls(),
                            'finish_reason' => $response->getFinishReason(),
                            'structured_output' => $response->getStructuredOutput()
                        ],
                        JSON_PRETTY_PRINT
                    )
                );
            }
        }
    }

    public function getWeatherInfo(string $city): string
    {
        return '23 degrees';
    }
}
