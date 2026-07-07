<?php
/**
 * Wiki interna: base di conoscenza per designer e AI.
 *
 * @package SalientCustomElements
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SCE_Wiki {

	private const PARENT = 'sce-elements';
	private const SLUG   = 'sce-wiki';
	private const CAP    = 'edit_pages';

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
	}

	public static function menu(): void {
		add_submenu_page(
			self::PARENT,
			__( 'Wiki and rules', 'salient-custom-elements' ),
			__( 'Wiki and rules', 'salient-custom-elements' ),
			self::CAP,
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'salient-custom-elements' ) );
		}

		echo '<div class="wrap sce-wrap sce-wiki">';
		echo '<h1>' . esc_html__( 'Wiki and rules', 'salient-custom-elements' ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'Shared reference for designers and the AI generator. Rules are applied to generated code and enforced on the model.', 'salient-custom-elements' ) . '</p>';

		self::render_external_docs();
		self::render_rules();
		self::render_tokens();
		self::render_param_types();
		self::render_vc_map_patterns();
		self::render_bindable_options();
		self::render_golden_examples();
		self::render_salient_reference();

		SCE_Admin::render_footer();

		echo '</div>';
	}

	private static function render_external_docs(): void {
		echo '<div class="sce-card">';
		echo '<h2>' . esc_html__( 'Official resources', 'salient-custom-elements' ) . '</h2>';
		echo '<ul class="sce-doc-links">';
		foreach ( SCE_Reference::external_docs() as $doc ) {
			echo '<li><a href="' . esc_url( $doc['url'] ) . '" target="_blank" rel="noopener noreferrer"><strong>' . esc_html( $doc['title'] ) . '</strong></a>';
			echo ' — ' . esc_html( $doc['note'] ) . '</li>';
		}
		echo '</ul></div>';
	}

	private static function render_rules(): void {
		$labels = SCE_Rules::group_labels();

		echo '<div class="sce-card">';
		echo '<h2>' . esc_html__( 'Production rules', 'salient-custom-elements' ) . '</h2>';

		foreach ( SCE_Rules::ruleset() as $group => $rules ) {
			echo '<h3>' . esc_html( $labels[ $group ] ?? $group ) . '</h3>';
			echo '<ul style="list-style:disc;margin-left:20px;">';
			foreach ( $rules as $rule ) {
				echo '<li>' . esc_html( $rule ) . '</li>';
			}
			echo '</ul>';
		}
		echo '</div>';
	}

	private static function render_tokens(): void {
		echo '<div class="sce-card">';
		echo '<h2>' . esc_html__( 'Template tokens', 'salient-custom-elements' ) . '</h2>';
		echo '<table class="widefat striped"><tbody>';
		self::token_row( '{{param_name}}', __( 'Value of a parameter, escaped according to its type.', 'salient-custom-elements' ) );
		self::token_row( '{{binding:opt-key}}', __( 'Direct value of a Salient theme option.', 'salient-custom-elements' ) );
		self::token_row( '{{content}}', __( 'Enclosed content for shortcodes with opening and closing tags.', 'salient-custom-elements' ) );
		echo '</tbody></table>';
		echo '</div>';
	}

	private static function token_row( string $token, string $desc ): void {
		echo '<tr><td style="width:220px;"><code>' . esc_html( $token ) . '</code></td><td>' . esc_html( $desc ) . '</td></tr>';
	}

	private static function render_param_types(): void {
		echo '<div class="sce-card">';
		echo '<h2>' . esc_html__( 'WPBakery param types', 'salient-custom-elements' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Type', 'salient-custom-elements' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'salient-custom-elements' ) . '</th>';
		echo '<th>' . esc_html__( 'Note', 'salient-custom-elements' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( SCE_Reference::wpbakery_param_types() as $pt ) {
			$badge = $pt['recommended']
				? '<span class="sce-badge sce-badge-ok">' . esc_html__( 'Recommended', 'salient-custom-elements' ) . '</span>'
				: '<span class="sce-badge">' . esc_html__( 'Optional', 'salient-custom-elements' ) . '</span>';
			echo '<tr><td><code>' . esc_html( $pt['type'] ) . '</code></td><td>' . $badge . '</td><td>' . esc_html( $pt['note'] ) . '</td></tr>';
		}
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Salient custom param types', 'salient-custom-elements' ) . '</h3>';
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Type', 'salient-custom-elements' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'salient-custom-elements' ) . '</th>';
		echo '<th>' . esc_html__( 'Note', 'salient-custom-elements' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( SCE_Reference::salient_param_types() as $pt ) {
			if ( $pt['avoid_for_ai'] ) {
				$badge = '<span class="sce-badge sce-badge-warn">' . esc_html__( 'Avoid for AI', 'salient-custom-elements' ) . '</span>';
			} elseif ( $pt['recommended'] ) {
				$badge = '<span class="sce-badge sce-badge-ok">' . esc_html__( 'Recommended', 'salient-custom-elements' ) . '</span>';
			} else {
				$badge = '<span class="sce-badge">' . esc_html__( 'Optional', 'salient-custom-elements' ) . '</span>';
			}
			echo '<tr><td><code>' . esc_html( $pt['type'] ) . '</code></td><td>' . $badge . '</td><td>' . esc_html( $pt['note'] ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	private static function render_vc_map_patterns(): void {
		echo '<div class="sce-card">';
		echo '<h2>' . esc_html__( 'Salient vc_map patterns', 'salient-custom-elements' ) . '</h2>';
		echo '<ul style="list-style:disc;margin-left:20px;">';
		foreach ( SCE_Reference::vc_map_patterns() as $note ) {
			echo '<li>' . esc_html( $note ) . '</li>';
		}
		echo '</ul></div>';
	}

	private static function render_bindable_options(): void {
		$labels = SCE_Reference::tier_labels();
		$groups = SCE_Reference::bindable_options_grouped();

		echo '<div class="sce-card">';
		echo '<h2>' . esc_html__( 'Bindable Salient options', 'salient-custom-elements' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Values read in real time from the active theme settings. Use them as salient_option on a parameter or as {{binding:key}}.', 'salient-custom-elements' ) . '</p>';

		foreach ( $groups as $tier => $items ) {
			if ( empty( $items ) ) {
				continue;
			}
			echo '<details class="sce-tier-details" open>';
			echo '<summary><strong>' . esc_html( $labels[ $tier ] ?? $tier ) . '</strong> (' . count( $items ) . ')</summary>';
			echo '<table class="widefat striped"><thead><tr>';
			echo '<th>' . esc_html__( 'Key', 'salient-custom-elements' ) . '</th>';
			echo '<th>' . esc_html__( 'Description', 'salient-custom-elements' ) . '</th>';
			echo '<th>' . esc_html__( 'Current value', 'salient-custom-elements' ) . '</th>';
			echo '</tr></thead><tbody>';
			foreach ( $items as $item ) {
				echo '<tr>';
				echo '<td><code>' . esc_html( $item['key'] ) . '</code></td>';
				echo '<td>' . esc_html( $item['label'] ) . '</td>';
				echo '<td><code>' . esc_html( '' !== $item['value'] ? $item['value'] : '—' ) . '</code></td>';
				echo '</tr>';
			}
			echo '</tbody></table></details>';
		}
		echo '</div>';
	}

	private static function render_golden_examples(): void {
		echo '<div class="sce-card">';
		echo '<h2>' . esc_html__( 'Golden examples (Salient)', 'salient-custom-elements' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Real patterns from nectar_maps that AI draws inspiration from.', 'salient-custom-elements' ) . '</p>';

		foreach ( SCE_Reference::golden_elements() as $el ) {
			echo '<details class="sce-golden-details"><summary><strong>' . esc_html( $el['name'] ) . '</strong> <code>' . esc_html( $el['base'] ) . '</code></summary>';
			echo '<p><code>' . esc_html( $el['source'] ) . '</code></p>';
			if ( ! empty( $el['params'] ) ) {
				echo '<ul style="list-style:disc;margin:6px 0 6px 24px;">';
				foreach ( $el['params'] as $p ) {
					echo '<li><code>' . esc_html( $p['param_name'] ) . '</code> — ' . esc_html( $p['type'] );
					if ( '' !== ( $p['heading'] ?? '' ) ) {
						echo ' (' . esc_html( $p['heading'] ) . ')';
					}
					echo '</li>';
				}
				echo '</ul>';
			}
			echo '</details>';
		}
		echo '</div>';
	}

	private static function render_salient_reference(): void {
		$elements = SCE_Salient::reference_for_ai();

		echo '<div class="sce-card">';
		echo '<h2>' . esc_html__( 'Salient elements reference', 'salient-custom-elements' ) . '</h2>';

		if ( empty( $elements ) ) {
			echo '<p>' . esc_html__( 'No nectar_* elements detected. Make sure Salient and WPBakery are active and initialized.', 'salient-custom-elements' ) . '</p></div>';
			return;
		}

		echo '<p class="description">' . sprintf(
			/* translators: %d: number of Salient elements found. */
			esc_html__( 'Extracted at runtime from WPBakery: %d elements.', 'salient-custom-elements' ),
			count( $elements )
		) . '</p>';

		foreach ( $elements as $el ) {
			$source = SCE_Reference::nectar_map_source( $el['base'] );
			echo '<details style="margin:6px 0;"><summary><strong>' . esc_html( $el['name'] ) . '</strong> <code>' . esc_html( $el['base'] ) . '</code></summary>';
			echo '<p class="description"><code>' . esc_html( $source ) . '</code></p>';
			if ( ! empty( $el['params'] ) ) {
				echo '<ul style="list-style:disc;margin:6px 0 6px 24px;">';
				foreach ( $el['params'] as $p ) {
					echo '<li><code>' . esc_html( $p['param_name'] ) . '</code> — ' . esc_html( $p['type'] ) . ( '' !== $p['heading'] ? ' (' . esc_html( $p['heading'] ) . ')' : '' ) . '</li>';
				}
				echo '</ul>';
			}
			echo '</details>';
		}
		echo '</div>';
	}
}
