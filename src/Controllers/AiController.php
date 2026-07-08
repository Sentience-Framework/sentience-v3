<?php

namespace Src\Controllers;

use Sentience\Abstracts\Controller;
use Sentience\Ai\Ai;
use Sentience\Ai\Api;
use Sentience\Ai\Attachments\Base64Attachment;
use Sentience\Ai\Attachments\UrlAttachment;
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
            'qwen3.6-35b-a3b-mtp',
            // 'google/gemma-4-e4b',
            // 'granite-4.1-8b',
            'Review the provided files and summarize their contents.'
        );

        $prompt->withSystemPrompt('You are a helpful assistant that reviews files. Answer and reason in maximum 2 sentences');

        $prompt->withAttachment(
            Base64Attachment::fromBase64(
                base64_encode('I love you PHP, you are my world'),
                'loveletter.txt'
            )
        );

        $prompt->withAttachment(Base64Attachment::fromBase64(
            base64_encode(file_get_contents(SENTIENCE_DIR . '/docker-compose.yml')),
            'docker-compose.yml'
        ));

        $prompt->withStructuredOutput(
            Schema::object([
                'files' => Schema::array(
                    Schema::object([
                        'filetype' => Schema::string(),
                        'contents_summary' => Schema::string()
                    ])
                )
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
