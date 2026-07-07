<?php
/**
 * Orchestrazione generazione elemento (POST + SSE).
 *
 * @package SalientCustomElements
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SCE_Generator {

	/**
	 * Esegue il flusso completo di generazione.
	 *
	 * @param callable|null $on_step function( string $event, array $data ): void
	 * @return array|WP_Error Definizione salvata con chiavi id, edit_url, preview_url.
	 */
	public static function run( string $prompt, ?callable $on_step = null ) {
		$emit = static function ( string $event, array $data = array() ) use ( $on_step ): void {
			if ( is_callable( $on_step ) ) {
				$on_step( $event, $data );
			}
		};

		$prompt = trim( $prompt );
		if ( '' === $prompt ) {
			return new WP_Error( 'sce_empty_prompt', __( 'Describe the element to generate.', 'salient-custom-elements' ) );
		}

		$emit( 'context', array( 'message' => __( 'Preparing Salient context and rules…', 'salient-custom-elements' ) ) );

		$emit( 'ai_start', array( 'message' => __( 'Generating the definition with AI…', 'salient-custom-elements' ) ) );
		$definition = SCE_AI_Generator::generate( $prompt );

		if ( is_wp_error( $definition ) ) {
			$emit( 'error', array( 'message' => $definition->get_error_message() ) );
			return $definition;
		}

		$emit(
			'ai_done',
			array(
				'message' => __( 'Definition received, validating JSON…', 'salient-custom-elements' ),
				'name'    => $definition['name'] ?? '',
				'base'    => $definition['base'] ?? '',
			)
		);

		$emit(
			'validate',
			array(
				'message' => sprintf(
					/* translators: 1: element name, 2: shortcode base */
					__( 'Valid structure: %1$s (%2$s)', 'salient-custom-elements' ),
					$definition['name'] ?? '',
					$definition['base'] ?? ''
				),
			)
		);

		$emit( 'save', array( 'message' => __( 'Saving the element and generating PHP…', 'salient-custom-elements' ) ) );
		$definition['generation_prompt'] = $prompt;
		$saved = SCE_Element_Store::save( $definition );
		if ( is_wp_error( $saved ) ) {
			$emit( 'error', array( 'message' => $saved->get_error_message() ) );
			return $saved;
		}

		$id = (int) $saved;
		$file_result = SCE_Code_Generator::write( SCE_Element_Store::get( $id ) );
		if ( is_wp_error( $file_result ) ) {
			$emit( 'error', array( 'message' => $file_result->get_error_message() ) );
			return $file_result;
		}

		$emit( 'preview', array( 'message' => __( 'Creating the preview page…', 'salient-custom-elements' ) ) );
		$def = SCE_Element_Store::get( $id );
		if ( $def ) {
			$preview = SCE_Preview::sync( $id, $def );
			if ( is_wp_error( $preview ) ) {
				$emit( 'error', array( 'message' => $preview->get_error_message() ) );
				return $preview;
			}
		}

		$edit_url    = add_query_arg(
			array(
				'page'   => 'sce-elements',
				'edit'   => $id,
				'sce_msg' => 'saved',
			),
			admin_url( 'admin.php' )
		);
		$preview_url = SCE_Preview::get_url( $id );

		$emit(
			'done',
			array(
				'message'     => __( 'Complete!', 'salient-custom-elements' ),
				'id'          => $id,
				'edit_url'    => $edit_url,
				'preview_url' => $preview_url,
			)
		);

		return array(
			'id'          => $id,
			'definition'  => $def,
			'edit_url'    => $edit_url,
			'preview_url' => $preview_url,
		);
	}

	/**
	 * Modifica un elemento esistente tramite AI (multi-turno).
	 *
	 * @param array<int,array{role:string,content:string}> $history Cronologia messaggi.
	 * @param callable|null                               $on_step function( string $event, array $data ): void
	 * @return array|WP_Error
	 */
	public static function modify( int $element_id, string $prompt, array $history = array(), ?callable $on_step = null ) {
		$emit = static function ( string $event, array $data = array() ) use ( $on_step ): void {
			if ( is_callable( $on_step ) ) {
				$on_step( $event, $data );
			}
		};

		$prompt = trim( $prompt );
		if ( '' === $prompt ) {
			return new WP_Error( 'sce_empty_prompt', __( 'Describe the changes to apply.', 'salient-custom-elements' ) );
		}

		$existing = SCE_Element_Store::get( $element_id );
		if ( null === $existing ) {
			return new WP_Error( 'sce_not_found', __( 'Element not found.', 'salient-custom-elements' ) );
		}

		$emit(
			'context',
			array(
				'message' => sprintf(
					/* translators: %s: element name */
					__( 'Loading element "%s" and preparing context…', 'salient-custom-elements' ),
					$existing['name'] ?? ''
				),
			)
		);

		$emit( 'ai_start', array( 'message' => __( 'Applying changes with AI…', 'salient-custom-elements' ) ) );
		$definition = SCE_AI_Generator::modify( $prompt, $existing, $history );

		if ( is_wp_error( $definition ) ) {
			$emit( 'error', array( 'message' => $definition->get_error_message() ) );
			return $definition;
		}

		$emit(
			'ai_done',
			array(
				'message' => __( 'Definition updated, validating JSON…', 'salient-custom-elements' ),
				'name'    => $definition['name'] ?? '',
				'base'    => $definition['base'] ?? '',
			)
		);

		$emit(
			'validate',
			array(
				'message' => sprintf(
					/* translators: 1: element name, 2: shortcode base */
					__( 'Valid structure: %1$s (%2$s)', 'salient-custom-elements' ),
					$definition['name'] ?? '',
					$definition['base'] ?? ''
				),
			)
		);

		$emit( 'save', array( 'message' => __( 'Saving changes and regenerating PHP…', 'salient-custom-elements' ) ) );
		$saved = SCE_Element_Store::save( $definition, $element_id );
		if ( is_wp_error( $saved ) ) {
			$emit( 'error', array( 'message' => $saved->get_error_message() ) );
			return $saved;
		}

		$id          = (int) $saved;
		$file_result = SCE_Code_Generator::write( SCE_Element_Store::get( $id ) );
		if ( is_wp_error( $file_result ) ) {
			$emit( 'error', array( 'message' => $file_result->get_error_message() ) );
			return $file_result;
		}

		$emit( 'preview', array( 'message' => __( 'Updating the preview page…', 'salient-custom-elements' ) ) );
		$def = SCE_Element_Store::get( $id );
		if ( $def ) {
			$preview = SCE_Preview::sync( $id, $def );
			if ( is_wp_error( $preview ) ) {
				$emit( 'error', array( 'message' => $preview->get_error_message() ) );
				return $preview;
			}
		}

		$edit_url    = add_query_arg(
			array(
				'page' => 'sce-elements',
				'edit' => $id,
			),
			admin_url( 'admin.php' )
		);
		$preview_url = SCE_Preview::get_url( $id );
		$summary     = self::modify_summary( $existing, $def ?? $definition );

		$emit(
			'done',
			array(
				'message'     => $summary,
				'id'          => $id,
				'edit_url'    => $edit_url,
				'preview_url' => $preview_url,
				'definition'  => $def ?? $definition,
				'mode'        => 'modify',
			)
		);

		return array(
			'id'          => $id,
			'definition'  => $def ?? $definition,
			'edit_url'    => $edit_url,
			'preview_url' => $preview_url,
			'summary'     => $summary,
		);
	}

	/**
	 * @param array<string,mixed> $before Definizione precedente.
	 * @param array<string,mixed> $after  Definizione aggiornata.
	 */
	private static function modify_summary( array $before, array $after ): string {
		$changes = array();

		if ( ( $before['name'] ?? '' ) !== ( $after['name'] ?? '' ) ) {
			$changes[] = __( 'name updated', 'salient-custom-elements' );
		}
		if ( ( $before['category'] ?? '' ) !== ( $after['category'] ?? '' ) ) {
			$changes[] = __( 'category updated', 'salient-custom-elements' );
		}
		if ( ( $before['template'] ?? '' ) !== ( $after['template'] ?? '' ) ) {
			$changes[] = __( 'template updated', 'salient-custom-elements' );
		}
		if ( ( $before['styles'] ?? '' ) !== ( $after['styles'] ?? '' ) ) {
			$changes[] = __( 'styles updated', 'salient-custom-elements' );
		}
		if ( ( $before['scripts'] ?? '' ) !== ( $after['scripts'] ?? '' ) ) {
			$changes[] = __( 'scripts updated', 'salient-custom-elements' );
		}

		$before_params = wp_json_encode( $before['params'] ?? array() );
		$after_params  = wp_json_encode( $after['params'] ?? array() );
		if ( $before_params !== $after_params ) {
			$changes[] = __( 'parameters updated', 'salient-custom-elements' );
		}

		if ( empty( $changes ) ) {
			return __( 'Element updated. Check the form fields for details.', 'salient-custom-elements' );
		}

		return sprintf(
			/* translators: %s: comma-separated list of changed parts */
			__( 'Changes applied: %s.', 'salient-custom-elements' ),
			implode( ', ', $changes )
		);
	}
}
