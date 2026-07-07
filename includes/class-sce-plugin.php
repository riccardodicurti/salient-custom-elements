<?php
/**
 * Bootstrap del plugin (generatore dev/staging).
 *
 * @package SalientCustomElements
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SCE_Plugin {

	private static ?SCE_Plugin $instance = null;

	public static function instance(): SCE_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Il CPT deve esistere prima di WPBakery (init@9).
		add_action( 'init', array( 'SCE_Element_Store', 'register_post_type' ), 1 );

		// Carica subito i file generati: elementi usabili all'istante.
		SCE_Loader::load();

		SCE_Admin::init();
		SCE_Wiki::init();
		SCE_Rest::init();

		// Promemoria rosso finche' il generatore e' attivo.
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_alert' ), 999 );
		add_action( 'wp_head', array( $this, 'admin_bar_alert_css' ) );
		add_action( 'admin_head', array( $this, 'admin_bar_alert_css' ) );

		add_action( 'init', array( $this, 'maybe_seed_example' ), 20 );

		add_filter(
			'sce_ai_provider',
			static function () {
				if ( ! function_exists( 'WordPress\\AI\\get_ai_service' ) ) {
					return null;
				}
				return array( SCE_Plugin::class, 'run_ai_provider' );
			}
		);
	}

	/**
	 * Provider AI con retry automatico su errori temporanei del modello.
	 *
	 * @param array<string,mixed> $context Contesto di sistema per il modello.
	 * @return string|array|WP_Error
	 */
	public static function run_ai_provider( string $prompt, array $context ) {
		try {
			$service = \WordPress\AI\get_ai_service();
			$system  = wp_json_encode( $context, JSON_UNESCAPED_UNICODE );
			$builder = $service->create_textgen_prompt(
				$prompt,
				array(
					'system_instruction' => $system,
				)
			);
			if ( ! $builder->is_supported_for_text_generation() ) {
				return new WP_Error(
					'sce_no_model',
					__( 'No AI model configured in the AI plugin.', 'salient-custom-elements' )
				);
			}

			return self::generate_text_with_retry( $builder );
		} catch ( Throwable $e ) {
			return new WP_Error(
				'sce_ai_exception',
				self::friendly_ai_error( $e->getMessage() )
			);
		}
	}

	/**
	 * @param object $builder Builder textgen del plugin AI.
	 * @return string|array|WP_Error
	 */
	private static function generate_text_with_retry( object $builder, int $max_attempts = 3 ) {
		$attempt    = 0;
		$last_error = '';

		while ( $attempt < $max_attempts ) {
			try {
				$result = $builder->generate_text();

				if ( is_wp_error( $result ) ) {
					$last_error = $result->get_error_message();
					if ( self::is_transient_ai_error( $last_error ) && $attempt < $max_attempts - 1 ) {
						++$attempt;
						sleep( (int) pow( 2, $attempt ) );
						continue;
					}

					return new WP_Error(
						$result->get_error_code(),
						self::friendly_ai_error( $last_error )
					);
				}

				return $result;
			} catch ( Throwable $e ) {
				$last_error = $e->getMessage();
				if ( self::is_transient_ai_error( $last_error ) && $attempt < $max_attempts - 1 ) {
					++$attempt;
					sleep( (int) pow( 2, $attempt ) );
					continue;
				}

				return new WP_Error(
					'sce_ai_exception',
					self::friendly_ai_error( $last_error )
				);
			}
		}

		return new WP_Error(
			'sce_ai_exception',
			self::friendly_ai_error( $last_error )
		);
	}

	private static function is_transient_ai_error( string $message ): bool {
		$needles = array(
			'503',
			'502',
			'429',
			'high demand',
			'Service Unavailable',
			'RESOURCE_EXHAUSTED',
			'overloaded',
			'temporarily unavailable',
			'rate limit',
			'Too Many Requests',
		);

		foreach ( $needles as $needle ) {
			if ( false !== stripos( $message, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	private static function friendly_ai_error( string $message ): string {
		if ( self::is_transient_ai_error( $message ) ) {
			return __(
				'The AI model is temporarily overloaded. Wait a few minutes and try again. If the problem persists, switch models in the AI plugin settings (e.g. change from gemini-2.5-flash to gemini-2.0-flash).',
				'salient-custom-elements'
			);
		}

		return $message;
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'salient-custom-elements', false, dirname( plugin_basename( SCE_FILE ) ) . '/languages' );
	}

	/**
	 * Nodo rosso nella admin bar: ricorda di rimuovere il generatore.
	 */
	public function admin_bar_alert( $wp_admin_bar ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$wp_admin_bar->add_node(
			array(
				'id'    => 'sce-remove-warning',
				'title' => '⚠ ' . __( 'Salient Custom Elements active: remove before production', 'salient-custom-elements' ),
				'href'  => admin_url( 'admin.php?page=sce-elements' ),
				'meta'  => array( 'class' => 'sce-remove-warning' ),
			)
		);
	}

	public function admin_bar_alert_css(): void {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<style>'
			. '#wpadminbar .sce-remove-warning > .ab-item{background:#d63638!important;color:#fff!important;font-weight:600}'
			. '#wpadminbar .sce-remove-warning:hover > .ab-item{background:#b32d2e!important;color:#fff!important}'
			. '</style>';
	}

	/**
	 * Alla prima esecuzione crea un elemento di esempio e ne genera il PHP.
	 */
	public function maybe_seed_example(): void {
		if ( get_option( 'sce_seeded' ) ) {
			return;
		}
		update_option( 'sce_seeded', 1 );

		$definition = array(
			'name'     => __( 'Hero CTA (example)', 'salient-custom-elements' ),
			'base'     => 'sce_hero_cta',
			'category' => __( 'Salient Custom', 'salient-custom-elements' ),
			'status'   => SCE_Element_Store::STATUS_DRAFT,
			'template' => '<header class="sce-hero_cta__head">'
				. '<h2 class="sce-hero_cta__title">{{title}}</h2>'
				. '<p class="sce-hero_cta__subtitle">{{subtitle}}</p>'
				. '</header>'
				. '<a class="sce-hero_cta__btn" href="{{button_url}}" style="background:{{button_color}}">{{button_label}}</a>',
			'params'   => array(
				array( 'type' => 'textfield', 'heading' => 'Title', 'param_name' => 'title', 'std' => 'Your title' ),
				array( 'type' => 'textarea', 'heading' => 'Subtitle', 'param_name' => 'subtitle', 'std' => '' ),
				array( 'type' => 'href', 'heading' => 'Button URL', 'param_name' => 'button_url', 'std' => '#' ),
				array( 'type' => 'textfield', 'heading' => 'Button text', 'param_name' => 'button_label', 'std' => 'Discover more' ),
				array( 'type' => 'colorpicker', 'heading' => 'Button color (empty = Salient accent)', 'param_name' => 'button_color', 'std' => '', 'salient_option' => 'accent-color' ),
			),
			'bindings' => array(),
		);

		$id = SCE_Element_Store::save( $definition );
		if ( ! is_wp_error( $id ) ) {
			SCE_Code_Generator::write( SCE_Element_Store::get( (int) $id ) );
		}
	}
}
