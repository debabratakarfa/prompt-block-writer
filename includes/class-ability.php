<?php
/**
 * Ability class for generating post content via the WordPress AI Abilities API.
 *
 * @package BlocksmithPrompts
 */

declare(strict_types=1);

namespace DebabrataKarfa\BlocksmithPrompts;

use WP_Error;
use WordPress\AI\Abstracts\Abstract_Ability;

defined( 'ABSPATH' ) || exit();

/**
 * Registers a content-generation ability that builds Gutenberg blocks from a prompt.
 */
class Ability extends Abstract_Ability {

	/**
	 * Returns the JSON schema describing the accepted input parameters.
	 *
	 * @return array<string, mixed>
	 */
	protected function input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'prompt'  => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
					'description'       => 'What the user wants generated.',
				),
				'context' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
					'description'       => 'Post content used as context.',
				),
			),
			'required'   => array( 'prompt' ),
		);
	}

	/**
	 * Returns the JSON schema describing the output value.
	 *
	 * @return array<string, mixed>
	 */
	protected function output_schema(): array {
		return array(
			'type'        => 'string',
			'description' => 'Generated content.',
		);
	}

	/**
	 * Runs the ability: builds the AI prompt and returns generated text.
	 *
	 * @param array<string, mixed> $input Validated input matching input_schema().
	 * @return string|\WP_Error Generated content string, or WP_Error on failure.
	 */
	protected function execute_callback( $input ) {
		$prompt  = trim( (string) ( $input['prompt'] ?? '' ) );
		$context = trim( (string) ( $input['context'] ?? '' ) );

		if ( '' === $prompt ) {
			return new WP_Error( 'no_prompt', 'A prompt is required.' );
		}

		$user_message = '' !== $context
			? "Post context:\n\n{$context}\n\nUser request:\n\n{$prompt}"
			: $prompt;

		$prompt_builder = wp_ai_client_prompt( $user_message )
			->using_system_instruction(
				"You are a helpful writer. Follow the user's request using the post context when provided. Return only the content to insert into the editor, with no preamble or explanation.",
			)
			->using_temperature( 0.7 );

		if (
			function_exists(
				'WordPress\\AI\\get_preferred_models_for_text_generation',
			)
		) {
			$preferred = \WordPress\AI\get_preferred_models_for_text_generation();
			if ( ! empty( $preferred ) ) {
				$prompt_builder->using_model_preference( ...$preferred );
			}
		}

		if ( ! $prompt_builder->is_supported_for_text_generation() ) {
			return new WP_Error(
				'unsupported_model',
				'No text generation model available.',
			);
		}

		return $prompt_builder->generate_text();
	}

	/**
	 * Checks whether the current user may invoke this ability.
	 *
	 * @param array<string, mixed> $input Validated input (unused).
	 * @return bool True if the user can edit posts.
	 */
	protected function permission_callback( $input ): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Returns REST-API metadata for this ability.
	 *
	 * @return array<string, mixed>
	 */
	protected function meta(): array {
		return array( 'show_in_rest' => true );
	}

	/**
	 * Returns the AI guideline categories that apply to generated content.
	 *
	 * @return string[]
	 */
	protected function guideline_categories(): array {
		return array( 'site', 'copy' );
	}
}
