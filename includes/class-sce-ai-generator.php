<?php
/**
 * Generatore AI: da una descrizione in linguaggio naturale a una definizione-dato.
 *
 * L'AI qui produce SOLO una struttura JSON (la definizione), mai codice PHP.
 * Questo file espone il punto di aggancio; la chiamata reale al modello va
 * cablata sull'AI Client nativo di WordPress 7 (hub Connectors) oppure su una
 * chiave API inserita dall'utente. Finche' non e' cablata, ritorna un WP_Error
 * chiaro cosi' la UI resta usabile (il designer puo' comunque costruire a mano).
 *
 * @package SalientCustomElements
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SCE_AI_Generator {

	/**
	 * Genera una definizione da un prompt.
	 *
	 * @param string $prompt Descrizione dell'elemento voluto dal designer.
	 * @return array|WP_Error Definizione pronta per lo store, o errore.
	 */
	public static function generate( string $prompt ) {
		$prompt = trim( $prompt );
		if ( '' === $prompt ) {
			return new WP_Error( 'sce_empty_prompt', __( 'Describe the element to generate.', 'salient-custom-elements' ) );
		}

		// Punto di aggancio: se un provider e' cablato, usalo.
		$provider = apply_filters( 'sce_ai_provider', null );
		if ( is_callable( $provider ) ) {
			$definition = $provider( $prompt, self::system_context() );
			if ( is_wp_error( $definition ) ) {
				return $definition;
			}
			return self::validate_ai_output( $definition );
		}

		// Nessun provider cablato ancora.
		return new WP_Error(
			'sce_ai_not_configured',
			__( 'AI generation is not configured yet. Connect a provider via the sce_ai_provider filter (WP 7 AI Client or API key), or build the element manually.', 'salient-custom-elements' )
		);
	}

	/**
	 * Modifica una definizione esistente tramite AI.
	 *
	 * @param array<string,mixed>                         $current_definition Definizione attuale.
	 * @param array<int,array{role:string,content:string}> $history           Cronologia conversazione.
	 * @return array|WP_Error
	 */
	public static function modify( string $prompt, array $current_definition, array $history = array() ) {
		$prompt = trim( $prompt );
		if ( '' === $prompt ) {
			return new WP_Error( 'sce_empty_prompt', __( 'Describe the changes to apply.', 'salient-custom-elements' ) );
		}

		$provider = apply_filters( 'sce_ai_provider', null );
		if ( is_callable( $provider ) ) {
			$context    = self::modify_system_context( $current_definition, $history );
			$full_prompt = self::build_modify_prompt( $prompt, $history );
			$definition = $provider( $full_prompt, $context );
			if ( is_wp_error( $definition ) ) {
				return $definition;
			}
			return self::validate_ai_modify_output( $definition, $current_definition );
		}

		return new WP_Error(
			'sce_ai_not_configured',
			__( 'AI generation is not configured yet. Connect a provider via the sce_ai_provider filter (WP 7 AI Client or API key), or edit the element manually.', 'salient-custom-elements' )
		);
	}

	/**
	 * Contesto di sistema per la modifica di un elemento esistente.
	 *
	 * @param array<string,mixed>                         $current_definition Definizione attuale.
	 * @param array<int,array{role:string,content:string}> $history           Cronologia conversazione.
	 * @return array<string,mixed>
	 */
	public static function modify_system_context( array $current_definition, array $history = array() ): array {
		$context = self::system_context();

		$context['current_element'] = array(
			'name'     => $current_definition['name'] ?? '',
			'base'     => $current_definition['base'] ?? '',
			'category' => $current_definition['category'] ?? '',
			'icon'     => $current_definition['icon'] ?? '',
			'status'   => $current_definition['status'] ?? SCE_Element_Store::STATUS_DRAFT,
			'template' => $current_definition['template'] ?? '',
			'styles'   => $current_definition['styles'] ?? '',
			'scripts'  => $current_definition['scripts'] ?? '',
			'params'   => $current_definition['params'] ?? array(),
			'bindings' => $current_definition['bindings'] ?? array(),
		);
		$context['conversation']    = $history;
		$context['instructions']    = __(
			'You are editing an existing Salient Custom Elements element. Return ONLY a valid JSON object conforming to output_schema with the ENTIRE updated definition. Do not add text, markdown, or PHP. Preserve the base field (shortcode tag) and status unless the user explicitly requests a change. Apply only the requested changes while staying consistent with production_rules and golden_elements. The template must contain ONLY semantic HTML with {{param}} tokens. Put CSS in the styles field and JavaScript in the scripts field — never in the template. For dynamic tags always use paired tags, e.g. <{{heading_tag}}>{{title}}</{{heading_tag}}>. Scope CSS under .sce-{base} or classes used in the template.',
			'salient-custom-elements'
		);

		return $context;
	}

	/**
	 * @param array<int,array{role:string,content:string}> $history Cronologia conversazione.
	 */
	private static function build_modify_prompt( string $prompt, array $history ): string {
		if ( empty( $history ) ) {
			return $prompt;
		}

		$parts = array( 'Conversation history:' );
		foreach ( $history as $message ) {
			$label = 'assistant' === ( $message['role'] ?? '' )
				? __( 'Assistant', 'salient-custom-elements' )
				: __( 'User', 'salient-custom-elements' );
			$parts[] = $label . ': ' . ( $message['content'] ?? '' );
		}
		$parts[] = "\n" . __( 'New request:', 'salient-custom-elements' ) . "\n" . $prompt;

		return implode( "\n", $parts );
	}

	/**
	 * Contesto di sistema per il modello: schema di output atteso + riferimento
	 * Salient estratto a runtime. Questo e' cio' che rende gli elementi generati
	 * "Salient-native": diamo al modello i pattern reali dell'install.
	 *
	 * @return array<string,mixed>
	 */
	public static function system_context(): array {
		$ref = class_exists( 'SCE_Reference' ) ? SCE_Reference::context_for_ai() : array();

		return array(
			'output_schema'      => self::output_schema(),
			'salient_elements'   => SCE_Salient::reference_for_ai(),
			'bindable_options'   => $ref['bindable_options'] ?? SCE_Salient::bindable_options(),
			'production_rules'   => SCE_Rules::ruleset(),
			'documentation'      => $ref['documentation'] ?? array(),
			'param_types'        => $ref['param_types'] ?? array(),
			'vc_map_patterns'    => $ref['vc_map_patterns'] ?? array(),
			'golden_elements'    => $ref['golden_elements'] ?? array(),
			'instructions'       => __(
				'Return ONLY a valid JSON object conforming to output_schema. No text, markdown, or PHP. The template MUST contain ONLY semantic HTML with {{param}} tokens — no CSS or JS in the template. Put element CSS in the styles field and JavaScript in the scripts field. Base responsive CSS is injected automatically; extend it in styles scoped to your classes. Use only recommended param_types. For theme colors use salient_option or {{binding:accent-color}}. Follow golden_elements patterns. Respect production_rules.',
				'salient-custom-elements'
			),
		);
	}

	/**
	 * Schema JSON della definizione che l'AI deve produrre.
	 *
	 * @return array<string,mixed>
	 */
	private static function output_schema(): array {
		return array(
			'name'     => 'string, etichetta umana',
			'base'     => 'string, tag shortcode univoco, solo minuscole e underscore, prefissato sce_',
			'category' => 'string, categoria WPBakery',
			'icon'     => 'string, classe icona opzionale',
			'template' => 'string, HTML with {{param_name}} and {{binding:opt-key}} and optional {{content}}',
			'styles'   => 'string, scoped CSS for this element (no style tags). Use classes from the template.',
			'scripts'  => 'string, optional JavaScript (no script tags). Enqueued once in footer.',
			'params'   => 'array di { type, heading, param_name, std, group, description, salient_option }',
			'bindings' => 'oggetto token => chiave opzione Salient',
		);
	}

	/**
	 * Valida e normalizza l'output del modello prima di passarlo allo store.
	 *
	 * @param mixed $definition Output grezzo del provider.
	 * @return array|WP_Error
	 */
	private static function validate_ai_output( $definition ) {
		if ( is_string( $definition ) ) {
			$decoded = json_decode( $definition, true );
			$definition = is_array( $decoded ) ? $decoded : null;
		}

		if ( ! is_array( $definition ) ) {
			return new WP_Error( 'sce_ai_bad_output', __( 'The model did not return valid JSON.', 'salient-custom-elements' ) );
		}

		$template = (string) ( $definition['template'] ?? '' );
		$template = SCE_Rules::normalize_template( $template );
		$definition['template'] = $template;
		$valid    = SCE_Rules::validate_template( $template );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$styles  = SCE_Rules::sanitize_asset_code( (string) ( $definition['styles'] ?? '' ), 'css' );
		$scripts = SCE_Rules::sanitize_asset_code( (string) ( $definition['scripts'] ?? '' ), 'js' );
		$valid_styles = SCE_Rules::validate_styles( $styles );
		if ( is_wp_error( $valid_styles ) ) {
			return $valid_styles;
		}
		$valid_scripts = SCE_Rules::validate_scripts( $scripts );
		if ( is_wp_error( $valid_scripts ) ) {
			return $valid_scripts;
		}
		$definition['styles']  = $styles;
		$definition['scripts'] = $scripts;

		// Forza lo stato iniziale a draft: nulla va live senza la tua approvazione.
		$definition['status'] = SCE_Element_Store::STATUS_DRAFT;

		return SCE_Element_Store::sanitize( $definition );
	}

	/**
	 * Valida e normalizza l'output del modello per una modifica.
	 *
	 * @param mixed               $definition Output grezzo del provider.
	 * @param array<string,mixed> $current    Definizione esistente da preservare.
	 * @return array|WP_Error
	 */
	private static function validate_ai_modify_output( $definition, array $current ) {
		if ( is_string( $definition ) ) {
			$decoded    = json_decode( $definition, true );
			$definition = is_array( $decoded ) ? $decoded : null;
		}

		if ( ! is_array( $definition ) ) {
			return new WP_Error( 'sce_ai_bad_output', __( 'The model did not return valid JSON.', 'salient-custom-elements' ) );
		}

		$template = (string) ( $definition['template'] ?? '' );
		$template = SCE_Rules::normalize_template( $template );
		$definition['template'] = $template;
		$valid    = SCE_Rules::validate_template( $template );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$styles  = SCE_Rules::sanitize_asset_code( (string) ( $definition['styles'] ?? '' ), 'css' );
		$scripts = SCE_Rules::sanitize_asset_code( (string) ( $definition['scripts'] ?? '' ), 'js' );
		$valid_styles = SCE_Rules::validate_styles( $styles );
		if ( is_wp_error( $valid_styles ) ) {
			return $valid_styles;
		}
		$valid_scripts = SCE_Rules::validate_scripts( $scripts );
		if ( is_wp_error( $valid_scripts ) ) {
			return $valid_scripts;
		}
		$definition['styles']  = $styles;
		$definition['scripts'] = $scripts;

		$definition['base']   = $current['base'] ?? ( $definition['base'] ?? '' );
		$definition['status'] = $current['status'] ?? SCE_Element_Store::STATUS_DRAFT;
		$definition['generation_prompt'] = $current['generation_prompt'] ?? '';

		return SCE_Element_Store::sanitize( $definition );
	}
}
