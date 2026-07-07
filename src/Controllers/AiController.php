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
            Api::OpenAI,
            // Api::Anthropic,
            'http://localhost:1234',
            'abcdefgh12345678'
        );

        $prompt = $ai->prompt(
            // 'qwen3.6-35b-a3b-mtp',
            // 'granite-4.1-8b',
            'google/gemma-4-e4b',
            'What will the weather be in Amsterdam?'
        );

        $prompt->withSystemPrompt('Use the provided tools to generate an answer. Answer and reason in maximum 2 sentences');

        $prompt->withTool(
            'get_weather_info',
            fn(string $city = 'Amsterdam'): string => "23 degrees mostly in $city, with lows of 19 and highs of 28",
            // ['city' => Schema::string()->description('City name')]
        );

        $prompt->withStructuredOutput(
            Schema::object([
                'weather' => Schema::float()->description('The temperature in celcius'),
                'lows_and_highs' => Schema::object([
                    'min' => Schema::float()->description('The lows of temps'),
                    'max' => Schema::float()->description('The highs of temps')
                ]),
                'reasons_why_you_think' => Schema::array(Schema::string())
            ])
        );

        $response = $prompt->execute();

        Stdio::printLn(
            Json::encode(
                [
                    'content' => $response->getContent(),
                    'reasoning' => $response->getReasoningContent(),
                    'tool_calls' => $response->getToolCalls(),
                    'finish_reason' => $response->getFinishReason(),
                    'structured_output' => $response->getStructuredOutput(),
                ],
                JSON_PRETTY_PRINT
            )
        );
    }
}
