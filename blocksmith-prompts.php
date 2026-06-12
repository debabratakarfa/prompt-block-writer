<?php
/**
 * Blocksmith Prompts — generate post content from a prompt via the WordPress Abilities API.
 *
 * @wordpress-plugin
 * Plugin Name: Blocksmith Prompts
 * Plugin URI: https://github.com/debabratakarfa/blocksmith-prompts
 * Description: Generates post content from a prompt using the WordPress Abilities API and inserts it as native Gutenberg blocks.
 * Version: 1.0.0
 * Requires at least: 7.0
 * Requires PHP: 7.4
 * Author: Debabrata Karfa
 * Author URI: https://profiles.wordpress.org/dkarfa
 * Text Domain: blocksmith-prompts
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package BlocksmithPrompts
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit();

define( 'BSP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BSP_VERSION', '1.0.0' );

// Check the AI system exists before proceeding.
add_action(
	'plugins_loaded',
	function () {
		// Core provides wp_supports_ai(), but the Abstract_Ability base class
		// this plugin extends is shipped by the separate "AI" feature plugin.
		// Bail (with a notice) if either is missing, otherwise requiring
		// includes/Ability.php would fatal because the parent class is undefined.
		$has_core_ai   = function_exists( 'wp_supports_ai' ) && wp_supports_ai();
		$has_ai_plugin = class_exists(
			\WordPress\AI\Abstracts\Abstract_Ability::class,
		);

		if ( ! $has_core_ai || ! $has_ai_plugin ) {
			add_action(
				'admin_notices',
				function () use ( $has_core_ai ) {
					$message = $has_core_ai
					? esc_html__(
						'Blocksmith Prompts requires the WordPress AI plugin to be installed and active.',
						'blocksmith-prompts',
					)
					: esc_html__(
						'Blocksmith Prompts requires WordPress AI support to be enabled.',
						'blocksmith-prompts',
					);

					wp_admin_notice( $message, array( 'type' => 'error' ) );
				}
			);
			return;
		}

		require_once BSP_PLUGIN_DIR . 'includes/class-ability.php';

		// Register the ability when the Abilities API boots.
		add_action(
			'wp_abilities_api_init',
			function () {
				wp_register_ability(
					'blocksmith-prompts/generate',
					array(
						'label'         => __( 'Content Generator', 'blocksmith-prompts' ),
						'description'   => __(
							'Generates content from post context.',
							'blocksmith-prompts',
						),
						'ability_class' => \DebabrataKarfa\BlocksmithPrompts\Ability::class,
					)
				);
			}
		);

		// Enqueue the block editor script.
		add_action(
			'enqueue_block_editor_assets',
			function () {
				$screen = get_current_screen();

				if (
				! $screen ||
				! in_array(
					$screen->post_type,
					get_post_types( array( 'show_in_rest' => true ), 'names' ),
					true,
				) ||
				'attachment' === $screen->post_type
				) {
					return;
				}

				$asset_file = BSP_PLUGIN_DIR . 'build/index.asset.php';
				if ( ! file_exists( $asset_file ) ) {
					return;
				}

				$asset = require $asset_file;

				// @wordpress/abilities and @wordpress/core-abilities are ES modules, not
				// classic script handles — wp-scripts lists them in .asset.php but they
				// must only be loaded via module_dependencies (see WP 6.9.1+ notices).
				$dependencies = array_values(
					array_diff(
						$asset['dependencies'],
						array( 'wp-abilities', 'wp-core-abilities' ),
					),
				);

				wp_enqueue_script(
					'blocksmith-prompts-editor',
					BSP_PLUGIN_URL . 'build/index.js',
					$dependencies,
					$asset['version'],
					array(
						'in_footer'           => true,
						'strategy'            => 'defer',
						// This loads @wordpress/abilities + @wordpress/core-abilities
						// as ES module dependencies — required for client-side ability execution.
						'module_dependencies' => array(
							'@wordpress/abilities',
							'@wordpress/core-abilities',
						),
					),
				);

				// Reuse the AI plugin's provider check (namespaced helpers, not global).
				$provider_data = \WordPress\AI\get_provider_availability_data();

				wp_add_inline_script(
					'blocksmith-prompts-editor',
					'window.aiProviderData=Object.assign(window.aiProviderData||{},'
					. wp_json_encode( $provider_data )
					. ');',
					'before',
				);
			}
		);
	}
);
