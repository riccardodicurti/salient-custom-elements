<?php
/**
 * REST API: generazione con SSE.
 *
 * @package SalientCustomElements
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SCE_Rest {

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes(): void {
		register_rest_route(
			'sce/v1',
			'/generate-stream',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'generate_stream' ),
				'permission_callback' => array( __CLASS__, 'can_generate' ),
				'args'                => array(
					'prompt' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'nonce'  => array(
						'required' => true,
						'type'     => 'string',
					),
					'element_id' => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'messages' => array(
						'required' => false,
						'type'     => 'array',
						'default'  => array(),
					),
				),
			)
		);
	}

	public static function can_generate(): bool {
		return current_user_can( 'edit_pages' );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 */
	public static function generate_stream( WP_REST_Request $request ) {
		if ( ! wp_verify_nonce( (string) $request->get_param( 'nonce' ), 'sce_generate_stream' ) ) {
			return new WP_Error( 'sce_bad_nonce', __( 'Invalid nonce.', 'salient-custom-elements' ), array( 'status' => 403 ) );
		}

		$prompt     = (string) $request->get_param( 'prompt' );
		$element_id = (int) $request->get_param( 'element_id' );
		$messages   = $request->get_param( 'messages' );
		$history    = self::sanitize_messages( is_array( $messages ) ? $messages : array() );

		nocache_headers();
		header( 'Content-Type: text/event-stream; charset=utf-8' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'X-Accel-Buffering: no' );

		if ( ob_get_level() ) {
			ob_end_clean();
		}

		$send = static function ( string $event, array $data ): void {
			echo 'event: ' . esc_attr( $event ) . "\n";
			echo 'data: ' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) . "\n\n";
			if ( function_exists( 'wp_ob_end_flush_all' ) ) {
				wp_ob_end_flush_all();
			}
			flush();
		};

		if ( $element_id > 0 ) {
			$result = SCE_Generator::modify(
				$element_id,
				$prompt,
				$history,
				static function ( string $event, array $data ) use ( $send ): void {
					$send( $event, $data );
				}
			);
		} else {
			$result = SCE_Generator::run(
				$prompt,
				static function ( string $event, array $data ) use ( $send ): void {
					$send( $event, $data );
				}
			);
		}

		exit;
	}

	/**
	 * @param array<int,array<string,mixed>> $messages Messaggi chat dal client.
	 * @return array<int,array{role:string,content:string}>
	 */
	private static function sanitize_messages( array $messages ): array {
		$history = array();

		foreach ( $messages as $message ) {
			if ( ! is_array( $message ) || empty( $message['role'] ) || ! isset( $message['content'] ) ) {
				continue;
			}

			$role = (string) $message['role'];
			if ( ! in_array( $role, array( 'user', 'assistant' ), true ) ) {
				continue;
			}

			$content = sanitize_textarea_field( (string) $message['content'] );
			if ( '' === $content ) {
				continue;
			}

			$history[] = array(
				'role'    => $role,
				'content' => $content,
			);
		}

		return $history;
	}
}
