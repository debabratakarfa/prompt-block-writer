<?php
declare(strict_types=1);

namespace DebabrataKarfa\PromptBlockWriter;

use WP_Error;
use WordPress\AI\Abstracts\Abstract_Ability;

defined("ABSPATH") || exit();

class Ability extends Abstract_Ability
{
    protected function input_schema(): array
    {
        return [
            "type" => "object",
            "properties" => [
                "prompt" => [
                    "type" => "string",
                    "sanitize_callback" => "sanitize_textarea_field",
                    "description" => "What the user wants generated.",
                ],
                "context" => [
                    "type" => "string",
                    "sanitize_callback" => "sanitize_textarea_field",
                    "description" => "Post content used as context.",
                ],
            ],
            "required" => ["prompt"],
        ];
    }

    protected function output_schema(): array
    {
        return [
            "type" => "string",
            "description" => "Generated content.",
        ];
    }

    protected function execute_callback($input)
    {
        $prompt = trim((string) ($input["prompt"] ?? ""));
        $context = trim((string) ($input["context"] ?? ""));

        if ("" === $prompt) {
            return new WP_Error("no_prompt", "A prompt is required.");
        }

        $user_message = "" !== $context
            ? "Post context:\n\n{$context}\n\nUser request:\n\n{$prompt}"
            : $prompt;

        $prompt_builder = wp_ai_client_prompt($user_message)
            ->using_system_instruction(
                "You are a helpful writer. Follow the user's request using the post context when provided. Return only the content to insert into the editor, with no preamble or explanation.",
            )
            ->using_temperature(0.7);

        if (
            function_exists(
                'WordPress\\AI\\get_preferred_models_for_text_generation',
            )
        ) {
            $preferred = \WordPress\AI\get_preferred_models_for_text_generation();
            if (!empty($preferred)) {
                $prompt_builder->using_model_preference(...$preferred);
            }
        }

        if (!$prompt_builder->is_supported_for_text_generation()) {
            return new WP_Error(
                "unsupported_model",
                "No text generation model available.",
            );
        }

        return $prompt_builder->generate_text();
    }

    protected function permission_callback($input): bool
    {
        return current_user_can("edit_posts");
    }

    protected function meta(): array
    {
        return ["show_in_rest" => true];
    }

    protected function guideline_categories(): array
    {
        return ["site", "copy"];
    }
}
