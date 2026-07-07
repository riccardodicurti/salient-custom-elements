<?php
/**
 * Archivio delle definizioni-dato degli elementi.
 *
 * Ogni elemento e' una definizione salvata come custom post type, NON come file
 * PHP. Il flusso di stato implementa la tua supervisione:
 *   draft   -> il designer sta lavorando
 *   pending -> il designer lo propone per la pubblicazione
 *   active  -> approvato dall'admin, registrato in WPBakery
 *
 * @package SalientCustomElements
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SCE_Element_Store {

	public const POST_TYPE = 'sce_element';
	public const META      = '_sce_definition';

	public const STATUS_DRAFT   = 'draft';
	public const STATUS_PENDING = 'pending';
	public const STATUS_ACTIVE  = 'active';

	/**
	 * Registra il custom post type interno (non pubblico).
	 */
	public static function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array( 'name' => __( 'Custom Elements', 'salient-custom-elements' ) ),
				'public'          => false,
				'show_ui'         => false,
				'show_in_rest'    => false,
				'capability_type' => 'post',
				'supports'        => array( 'title', 'author' ),
			)
		);
	}

	/**
	 * Tutte le definizioni, opzionalmente filtrate per stato.
	 *
	 * @param string|null $status Uno degli stati, o null per tutti.
	 * @return array<int,array> Lista di definizioni (con chiave 'id').
	 */
	public static function all( ?string $status = null ): array {
		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 200,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		$out = array();
		foreach ( $query->posts as $post ) {
			$def = self::hydrate( $post );
			if ( null === $status || ( $def['status'] ?? '' ) === $status ) {
				$out[] = $def;
			}
		}
		return $out;
	}

	/**
	 * Solo le definizioni attive: quelle che vanno registrate in WPBakery.
	 *
	 * @return array<int,array>
	 */
	public static function active(): array {
		return self::all( self::STATUS_ACTIVE );
	}

	/**
	 * Carica una singola definizione per ID.
	 *
	 * @return array|null
	 */
	public static function get( int $id ): ?array {
		$post = get_post( $id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}
		return self::hydrate( $post );
	}

	/**
	 * Crea o aggiorna una definizione.
	 *
	 * @param array    $definition Struttura elemento (vedi schema in save()).
	 * @param int|null $id         ID esistente, o null per crearne uno nuovo.
	 * @return int|WP_Error ID salvato oppure errore.
	 */
	public static function save( array $definition, ?int $id = null ) {
		$definition = self::sanitize( $definition );

		if ( '' === $definition['base'] || '' === $definition['name'] ) {
			return new WP_Error( 'sce_invalid', __( 'Name and base (shortcode tag) are required.', 'salient-custom-elements' ) );
		}

		$postarr = array(
			'ID'          => $id ?: 0,
			'post_type'   => self::POST_TYPE,
			'post_status' => 'publish',
			'post_title'  => $definition['name'],
		);

		$saved_id = $id ? wp_update_post( $postarr, true ) : wp_insert_post( $postarr, true );
		if ( is_wp_error( $saved_id ) ) {
			return $saved_id;
		}

		update_post_meta( (int) $saved_id, self::META, wp_slash( wp_json_encode( $definition ) ) );
		return (int) $saved_id;
	}

	/**
	 * Cambia lo stato di una definizione (usato dal gate di approvazione).
	 */
	public static function set_status( int $id, string $status ): bool {
		$def = self::get( $id );
		if ( null === $def ) {
			return false;
		}
		$allowed = array( self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_ACTIVE );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}
		$def['status'] = $status;
		return ! is_wp_error( self::save( $def, $id ) );
	}

	public static function delete( int $id ): bool {
		return (bool) wp_delete_post( $id, true );
	}

	/**
	 * Ricostruisce la definizione da un post.
	 */
	private static function hydrate( WP_Post $post ): array {
		$raw = get_post_meta( $post->ID, self::META, true );
		$def = is_string( $raw ) ? json_decode( $raw, true ) : array();
		if ( ! is_array( $def ) ) {
			$def = array();
		}
		$def          = self::sanitize( $def );
		$def['id']    = $post->ID;
		$def['name']  = $post->post_title ?: $def['name'];
		return $def;
	}

	/**
	 * Normalizza/valida la struttura di una definizione.
	 *
	 * Schema:
	 *   name      string   etichetta umana
	 *   base      string   tag shortcode univoco (es. sce_hero_cta)
	 *   category  string   categoria nella griglia WPBakery
	 *   icon      string   classe icona o URL
	 *   status    string   draft|pending|active
	 *   template  string   HTML con token {{param_name}} e {{binding:opt-key}}
	 *   styles    string   CSS scoped dell'elemento (no tag style)
	 *   scripts   string   JavaScript dell'elemento (no tag script)
	 *   params    array    lista di parametri vc_map
	 *   bindings  array    token => chiave opzione Salient
	 *   generation_prompt string prompt usato per la generazione iniziale
	 */
	public static function sanitize( array $def ): array {
		$base = sanitize_key( $def['base'] ?? '' );
		// Il tag shortcode non puo' contenere trattini nel modo classico: usiamo underscore.
		$base = str_replace( '-', '_', $base );

		$status  = $def['status'] ?? self::STATUS_DRAFT;
		$allowed = array( self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_ACTIVE );
		if ( ! in_array( $status, $allowed, true ) ) {
			$status = self::STATUS_DRAFT;
		}

		$params = array();
		if ( ! empty( $def['params'] ) && is_array( $def['params'] ) ) {
			foreach ( $def['params'] as $p ) {
				if ( empty( $p['param_name'] ) ) {
					continue;
				}
				$params[] = array(
					'type'          => sanitize_text_field( $p['type'] ?? 'textfield' ),
					'heading'       => sanitize_text_field( $p['heading'] ?? '' ),
					'param_name'    => sanitize_key( $p['param_name'] ),
					'value'         => is_array( $p['value'] ?? '' ) ? array_map( 'sanitize_text_field', $p['value'] ) : sanitize_text_field( $p['value'] ?? '' ),
					'std'           => sanitize_text_field( $p['std'] ?? '' ),
					'group'         => sanitize_text_field( $p['group'] ?? '' ),
					'description'   => sanitize_text_field( $p['description'] ?? '' ),
					'salient_option' => sanitize_text_field( $p['salient_option'] ?? '' ),
				);
			}
		}

		$bindings = array();
		if ( ! empty( $def['bindings'] ) && is_array( $def['bindings'] ) ) {
			foreach ( $def['bindings'] as $token => $opt ) {
				$bindings[ sanitize_key( $token ) ] = sanitize_text_field( $opt );
			}
		}

		return array(
			'name'              => sanitize_text_field( $def['name'] ?? '' ),
			'base'              => $base,
			'category'          => sanitize_text_field( $def['category'] ?? __( 'Salient Custom', 'salient-custom-elements' ) ),
			'icon'              => sanitize_text_field( $def['icon'] ?? '' ),
			'status'            => $status,
			'template'          => SCE_Rules::normalize_template(
				SCE_Rules::sanitize_template( (string) ( $def['template'] ?? '' ) )
			),
			'styles'            => SCE_Rules::sanitize_asset_code( (string) ( $def['styles'] ?? '' ), 'css' ),
			'scripts'           => SCE_Rules::sanitize_asset_code( (string) ( $def['scripts'] ?? '' ), 'js' ),
			'params'            => $params,
			'bindings'          => $bindings,
			'generation_prompt' => sanitize_textarea_field( $def['generation_prompt'] ?? '' ),
		);
	}
}
