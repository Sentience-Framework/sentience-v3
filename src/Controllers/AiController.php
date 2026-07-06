<?php

namespace Src\Controllers;

use Sentience\Abstracts\Controller;
use Sentience\Ai\Ai;
use Sentience\Ai\Api;
use Sentience\Ai\Schema\Schema;
use Sentience\Ai\Schema\StructuredOutput;
use Sentience\Ai\Schema\StructuredOutputInterface;
use Sentience\Ai\Schema\Schemable;
use Sentience\Database\Databases\SQLite\SQLiteDatabase;
use Sentience\Database\Queries\Enums\ReferentialActionEnum;
use Sentience\Database\Queries\Objects\HavingGroup;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\Objects\WhereGroup;
use Sentience\Database\Queries\Query;
use Sentience\Helpers\Json;
use Sentience\Mapper\Mapper;
use Sentience\ORM\Database\DB;
use Sentience\Sentience\Request;
use Sentience\Sentience\Response;
use Sentience\Sentience\Stdio;
use Src\Models\Author;
use Src\Models\Book;
use Src\Models\Migration;
use Src\Payloads\TestPayload;

class AiController extends Controller
{
    public function __construct(protected ?Request $request)
    {
    }

    public function ai(array $words, array $flags): void
    {
        $ai = Ai::connect(
            Api::OpenAI,
            'http://localhost:1234/v1',
            'abcdefgh12345678'
        );

        $prompt = $ai->prompt(
            // 'qwen3.6-35b-a3b-mtp',
            // 'granite-4.1-8b',
            'google/gemma-4-e4b',
            'What will the weather be in Germany'
        );

        $prompt->withSystemPrompt('Use the provided tools to generate an answer. Answer and reason in maximum 2 sentences');

        $prompt->withTool(
            'get_weather_info',
            fn(): string => "23 degrees mostly"
        );

        $prompt->withStructuredOutput(
            function (Schema $schema) {
                return $schema->object([
                    'weather' => $schema->float()->description('The temperature in celcius')
                ]);
            }
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
