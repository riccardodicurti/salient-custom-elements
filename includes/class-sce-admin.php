<?php
/**
 * Interfaccia admin: generatore + builder + review del PHP + export produzione.
 *
 * Flusso rivisto: gli elementi creati sono usabili SUBITO nell'editor (il loro
 * PHP viene generato e caricato). La tua supervisione e' la review del codice
 * generato prima dell'export di produzione.
 *
 * Permessi:
 *   - Designer (edit_pages): crea, genera, propone.
 *   - Admin (manage_options): marca "revisionato", esporta, elimina.
 *
 * @package SalientCustomElements
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SCE_Admin {

	private const SLUG         = 'sce-elements';
	private const CAP_DESIGNER = 'edit_pages';
	private const CAP_APPROVER = 'manage_options';

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_sce_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_sce_generate', array( __CLASS__, 'handle_generate' ) );
		add_action( 'admin_post_sce_status', array( __CLASS__, 'handle_status' ) );
		add_action( 'admin_post_sce_delete', array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_post_sce_download', array( __CLASS__, 'handle_download' ) );
		add_action( 'admin_post_sce_export', array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_sce_ship', array( __CLASS__, 'handle_ship' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function menu(): void {
		add_menu_page(
			__( 'Salient Custom Elements', 'salient-custom-elements' ),
			__( 'Custom Elements', 'salient-custom-elements' ),
			self::CAP_DESIGNER,
			self::SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-layout',
			58
		);
	}

	public static function assets( string $hook ): void {
		if ( false === strpos( $hook, self::SLUG ) && false === strpos( $hook, 'sce-wiki' ) ) {
			return;
		}
		wp_enqueue_style( 'sce-admin', SCE_URL . 'assets/admin.css', array(), SCE_VERSION );

		if ( false !== strpos( $hook, self::SLUG ) ) {
			wp_enqueue_script(
				'sce-admin-generate',
				SCE_URL . 'assets/admin-generate.js',
				array( 'wp-i18n' ),
				SCE_VERSION,
				true
			);
			wp_set_script_translations( 'sce-admin-generate', 'salient-custom-elements', SCE_PATH . 'languages' );

			$localize = array(
				'restUrl'      => rest_url( 'sce/v1/generate-stream' ),
				'restNonce'    => wp_create_nonce( 'wp_rest' ),
				'streamNonce'  => wp_create_nonce( 'sce_generate_stream' ),
				'generating'   => __( 'Generating…', 'salient-custom-elements' ),
				'generate'     => __( 'Generate', 'salient-custom-elements' ),
				'modifying'    => __( 'Modifying…', 'salient-custom-elements' ),
				'modify'       => __( 'Apply changes', 'salient-custom-elements' ),
				'error'        => __( 'Error', 'salient-custom-elements' ),
				'networkError' => __( 'Network error', 'salient-custom-elements' ),
				'complete'     => __( 'Complete!', 'salient-custom-elements' ),
			);

			if ( isset( $_GET['edit'] ) ) {
				$editing = SCE_Element_Store::get( (int) $_GET['edit'] );
				if ( $editing ) {
					$localize['elementId']           = (int) $editing['id'];
					$localize['currentDefinition']   = $editing;
					$localize['modifyPlaceholder']   = __( 'Describe the changes you want to make…', 'salient-custom-elements' );
					$localize['modifyIntro']         = __( 'Describe in natural language what to change. AI updates the element and the form fields refresh automatically.', 'salient-custom-elements' );
					$localize['modifyDone']          = __( 'Element updated. You can continue requesting changes or save manually.', 'salient-custom-elements' );
				}
			}

			wp_localize_script( 'sce-admin-generate', 'sceGenerate', $localize );
		}
	}

	public static function render_page(): void {
		if ( ! current_user_can( self::CAP_DESIGNER ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'salient-custom-elements' ) );
		}

		$can_approve = current_user_can( self::CAP_APPROVER );
		$editing     = isset( $_GET['edit'] ) ? SCE_Element_Store::get( (int) $_GET['edit'] ) : null;

		echo '<div class="wrap sce-wrap">';
		echo '<h1>' . esc_html__( 'Salient Custom Elements', 'salient-custom-elements' ) . '</h1>';
		echo '<div class="notice notice-info inline"><p>';
		echo esc_html__( 'Dev/staging tool. Created elements are usable immediately in the editor. Review the generated PHP, then export the production plugin and remove this generator.', 'salient-custom-elements' );
		echo '</p></div>';

		self::render_notices();
		if ( $editing ) {
			self::render_edit_toolbar( $editing );
		}
		self::render_generator( $editing );
		if ( $editing ) {
			self::render_form( $editing );
			self::render_review( $editing );
		}
		self::render_list( $can_approve );
		self::render_footer();

		echo '</div>';
	}

	/**
	 * Plugin footer with author credits and donation link.
	 */
	public static function render_footer(): void {
		echo '<div class="sce-footer">';
		echo '<p class="sce-footer__credits">';
		echo wp_kses_post(
			sprintf(
				/* translators: %s: author website URL */
				__( 'Developed by <a href="%s" target="_blank" rel="noopener noreferrer">Riccardo Di Curti</a>.', 'salient-custom-elements' ),
				esc_url( SCE_AUTHOR_URL )
			)
		);
		echo '</p>';
		printf(
			'<p><a class="button sce-donate-btn" href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
			esc_url( SCE_DONATE_URL ),
			esc_html__( 'Support development', 'salient-custom-elements' )
		);
		printf(
			'<p class="sce-footer__meta">%s</p>',
			esc_html(
				sprintf(
					/* translators: 1: plugin version, 2: license name */
					__( 'Version %1$s · %2$s', 'salient-custom-elements' ),
					SCE_VERSION,
					'GPL v2+'
				)
			)
		);
		echo '</div>';
	}

	private static function render_notices(): void {
		if ( isset( $_GET['sce_msg'] ) ) {
			$map = array(
				'saved'    => __( 'Element generated and available in the editor. The form below is pre-filled: edit and save to refine.', 'salient-custom-elements' ),
				'reviewed' => __( 'Element marked as reviewed.', 'salient-custom-elements' ),
				'deleted'  => __( 'Definition and generated file deleted.', 'salient-custom-elements' ),
				'proposed' => __( 'Element proposed for review.', 'salient-custom-elements' ),
			);
			$key = sanitize_key( wp_unslash( $_GET['sce_msg'] ) );
			if ( isset( $map[ $key ] ) ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $map[ $key ] );
				if ( 'saved' === $key && isset( $_GET['edit'] ) ) {
					$preview_url = SCE_Preview::get_url( (int) $_GET['edit'] );
					if ( $preview_url ) {
						echo ' <a href="' . esc_url( $preview_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View preview', 'salient-custom-elements' ) . '</a>';
					}
				}
				echo '</p></div>';
			}
		}
		if ( isset( $_GET['sce_err'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['sce_err'] ) ) ) . '</p></div>';
		}
	}

	private static function render_edit_toolbar( array $editing ): void {
		$id          = (int) ( $editing['id'] ?? 0 );
		$preview_url = SCE_Preview::get_url( $id );

		echo '<div class="sce-edit-toolbar">';
		echo '<div class="sce-edit-toolbar__info">';
		echo '<h2 class="sce-edit-toolbar__title">' . esc_html( $editing['name'] ?? '' ) . '</h2>';
		if ( ! empty( $editing['base'] ) ) {
			echo '<p class="sce-edit-toolbar__meta"><code>' . esc_html( $editing['base'] ) . '</code></p>';
		}
		echo '</div>';

		if ( $preview_url ) {
			printf(
				'<a class="button button-primary button-hero sce-preview-btn" href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( $preview_url ),
				esc_html__( 'Preview', 'salient-custom-elements' )
			);
		} else {
			echo '<span class="button button-primary button-hero sce-preview-btn disabled" aria-disabled="true">' . esc_html__( 'Preview unavailable', 'salient-custom-elements' ) . '</span>';
		}

		echo '</div>';
	}

	private static function render_generator( ?array $editing = null ): void {
		$saved_prompt = '';
		if ( isset( $_GET['sce_prompt'] ) ) {
			$saved_prompt = sanitize_textarea_field( rawurldecode( wp_unslash( (string) $_GET['sce_prompt'] ) ) );
		} elseif ( $editing && ! empty( $editing['generation_prompt'] ) ) {
			$saved_prompt = $editing['generation_prompt'];
		}

		echo '<div class="sce-card">';
		if ( $editing ) {
			echo '<h2>' . esc_html__( 'Original prompt', 'salient-custom-elements' ) . '</h2>';
			echo '<p class="description">' . esc_html__( 'Description used to generate this element. For further changes use the chat below the form.', 'salient-custom-elements' ) . '</p>';
			if ( '' === $saved_prompt ) {
				echo '<p class="description">' . esc_html__( 'Prompt unavailable (element created manually or before this feature).', 'salient-custom-elements' ) . '</p>';
			}
			printf(
				'<textarea rows="3" class="large-text" id="sce-prompt" readonly>%s</textarea>',
				esc_textarea( $saved_prompt )
			);
			echo '</div>';
			return;
		}

		echo '<h2>' . esc_html__( 'Generate with AI', 'salient-custom-elements' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Describe the element. AI produces a definition that follows SEO, security, accessibility, and responsive rules; then you can refine it.', 'salient-custom-elements' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" id="sce-generate-form">';
		wp_nonce_field( 'sce_generate' );
		echo '<input type="hidden" name="action" value="sce_generate" />';
		echo '<textarea name="prompt" rows="3" class="large-text" id="sce-prompt" placeholder="' . esc_attr__( 'E.g.: a hero with title, subtitle, and button, color inherited from the theme accent color', 'salient-custom-elements' ) . '">' . esc_textarea( $saved_prompt ) . '</textarea>';
		echo '<p><button type="submit" class="button button-primary" id="sce-gen-btn">' . esc_html__( 'Generate', 'salient-custom-elements' ) . '</button></p>';
		echo '</form>';
		echo '<div id="sce-chat-log" class="sce-chat-log" hidden aria-live="polite"></div>';
		echo '</div>';
	}

	private static function render_form( array $editing ): void {
		$def = $editing;

		echo '<div class="sce-card" id="sce-edit-card">';
		echo '<h2>' . esc_html__( 'Edit element', 'salient-custom-elements' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" id="sce-edit-form">';
		wp_nonce_field( 'sce_save' );
		echo '<input type="hidden" name="action" value="sce_save" />';
		echo '<input type="hidden" name="id" id="sce-id" value="' . esc_attr( (string) ( $def['id'] ?? 0 ) ) . '" />';

		printf(
			'<p><label for="sce-name">%s<br><input type="text" name="name" id="sce-name" class="regular-text" value="%s" required></label></p>',
			esc_html__( 'Name', 'salient-custom-elements' ),
			esc_attr( $def['name'] )
		);
		printf(
			'<p><label for="sce-base">%s<br><input type="text" name="base" id="sce-base" class="regular-text" value="%s" required> <span class="description">%s</span></label></p>',
			esc_html__( 'Base (shortcode tag)', 'salient-custom-elements' ),
			esc_attr( $def['base'] ),
			esc_html__( 'AI preserves it during edits; change it manually only if necessary', 'salient-custom-elements' )
		);
		printf(
			'<p><label for="sce-category">%s<br><input type="text" name="category" id="sce-category" class="regular-text" value="%s"></label></p>',
			esc_html__( 'WPBakery category', 'salient-custom-elements' ),
			esc_attr( $def['category'] )
		);
		printf(
			'<p><label for="sce-template">%s<br><textarea name="template" id="sce-template" rows="6" class="large-text code">%s</textarea></label></p>',
			esc_html__( 'Template ({{param}} and {{binding:opt-key}} tokens)', 'salient-custom-elements' ),
			esc_textarea( $def['template'] )
		);
		printf(
			'<p><label for="sce-params-json">%s<br><textarea name="params_json" id="sce-params-json" rows="6" class="large-text code">%s</textarea></label></p>',
			esc_html__( 'Parameters (JSON)', 'salient-custom-elements' ),
			esc_textarea( (string) wp_json_encode( $def['params'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) )
		);

		echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Save and generate', 'salient-custom-elements' ) . '</button></p>';
		echo '</form>';

		echo '<div class="sce-modify-chat">';
		echo '<h3>' . esc_html__( 'Edit with AI', 'salient-custom-elements' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Describe in natural language what to change. AI updates the element and the form fields refresh automatically.', 'salient-custom-elements' ) . '</p>';
		echo '<div id="sce-modify-chat-log" class="sce-chat-log sce-chat-log--modify" aria-live="polite"></div>';
		echo '<form id="sce-modify-form" class="sce-chat-input-area">';
		echo '<textarea id="sce-modify-prompt" rows="2" class="large-text" placeholder="' . esc_attr__( 'E.g.: add a parameter for the title color', 'salient-custom-elements' ) . '"></textarea>';
		echo '<p><button type="submit" class="button button-secondary" id="sce-modify-btn">' . esc_html__( 'Apply changes', 'salient-custom-elements' ) . '</button></p>';
		echo '</form>';
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Pannello di review: avvisi dell'auditor + PHP generato in sola lettura.
	 */
	private static function render_review( array $def ): void {
		$warnings = SCE_Rules::audit( $def );
		$code     = SCE_Code_Generator::read( $def['base'] );

		echo '<div class="sce-card">';
		echo '<h2>' . esc_html__( 'Generated code review', 'salient-custom-elements' ) . '</h2>';

		if ( ! empty( $warnings ) ) {
			echo '<div class="notice notice-warning inline"><p><strong>' . esc_html__( 'To fix:', 'salient-custom-elements' ) . '</strong></p><ul style="list-style:disc;margin-left:20px;">';
			foreach ( $warnings as $w ) {
				echo '<li>' . esc_html( $w ) . '</li>';
			}
			echo '</ul></div>';
		} else {
			echo '<p class="sce-ok">' . esc_html__( 'No warnings: meets the basic rules.', 'salient-custom-elements' ) . '</p>';
		}

		if ( '' !== $code ) {
			$dl = wp_nonce_url(
				add_query_arg( array( 'action' => 'sce_download', 'base' => $def['base'] ), admin_url( 'admin-post.php' ) ),
				'sce_download_' . $def['base']
			);
			echo '<p><a class="button" href="' . esc_url( $dl ) . '">' . esc_html__( 'Download PHP', 'salient-custom-elements' ) . '</a></p>';
			echo '<textarea readonly rows="16" class="large-text code" onclick="this.select()">' . esc_textarea( $code ) . '</textarea>';
		}
		echo '</div>';
	}

	private static function render_list( bool $can_approve ): void {
		$items = SCE_Element_Store::all();

		echo '<div class="sce-card">';
		echo '<div style="display:flex;justify-content:space-between;align-items:center;">';
		echo '<h2 style="margin:0;">' . esc_html__( 'Elements', 'salient-custom-elements' ) . '</h2>';
		if ( $can_approve ) {
			echo '<div>';
			$export = wp_nonce_url( add_query_arg( array( 'action' => 'sce_export' ), admin_url( 'admin-post.php' ) ), 'sce_export' );
			echo '<a class="button" href="' . esc_url( $export ) . '">' . esc_html__( 'Download zip (all elements)', 'salient-custom-elements' ) . '</a> ';
			$ship = wp_nonce_url( add_query_arg( array( 'action' => 'sce_ship' ), admin_url( 'admin-post.php' ) ), 'sce_ship' );
			echo '<a class="button button-primary" style="background:#d63638;border-color:#d63638;" href="' . esc_url( $ship ) . '" onclick="return confirm(\'' . esc_js( __( 'Package the production plugin with all elements, activate it, and remove this generator. Continue?', 'salient-custom-elements' ) ) . '\')">' . esc_html__( 'Package and remove generator', 'salient-custom-elements' ) . '</a>';
			echo '</div>';
		}
		echo '</div>';

		if ( empty( $items ) ) {
			echo '<p>' . esc_html__( 'No elements yet.', 'salient-custom-elements' ) . '</p></div>';
			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Name', 'salient-custom-elements' ) . '</th>';
		echo '<th>' . esc_html__( 'Base', 'salient-custom-elements' ) . '</th>';
		echo '<th>' . esc_html__( 'Actions', 'salient-custom-elements' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $items as $item ) {
			$id = (int) $item['id'];

			echo '<tr>';
			echo '<td>' . esc_html( $item['name'] ) . '</td>';
			echo '<td><code>' . esc_html( $item['base'] ) . '</code></td>';
			echo '<td>';

			$edit_url = add_query_arg( array( 'page' => self::SLUG, 'edit' => $id ), admin_url( 'admin.php' ) );
			echo '<a class="button button-small" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit / review', 'salient-custom-elements' ) . '</a> ';

			$preview_url = SCE_Preview::get_url( $id );
			if ( $preview_url ) {
				echo '<a class="button button-small" href="' . esc_url( $preview_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Preview', 'salient-custom-elements' ) . '</a> ';
			}

			if ( $can_approve ) {
				echo self::delete_button( $id );
			}

			echo '</td></tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	private static function action_button( int $id, string $status, string $label, string $extra_class = '' ): string {
		$url = wp_nonce_url(
			add_query_arg( array( 'action' => 'sce_status', 'id' => $id, 'status' => $status ), admin_url( 'admin-post.php' ) ),
			'sce_status_' . $id
		);
		return '<a class="button button-small ' . esc_attr( $extra_class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a> ';
	}

	private static function delete_button( int $id ): string {
		$url = wp_nonce_url(
			add_query_arg( array( 'action' => 'sce_delete', 'id' => $id ), admin_url( 'admin-post.php' ) ),
			'sce_delete_' . $id
		);
		return '<a class="button button-small button-link-delete" href="' . esc_url( $url ) . '" onclick="return confirm(\'' . esc_js( __( 'Delete definition and generated file?', 'salient-custom-elements' ) ) . '\')">' . esc_html__( 'Delete', 'salient-custom-elements' ) . '</a>';
	}

	/* ---------- Handlers ---------- */

	public static function handle_generate(): void {
		self::require_cap( self::CAP_DESIGNER );
		check_admin_referer( 'sce_generate' );

		$prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
		$result = SCE_Generator::run( $prompt );

		if ( is_wp_error( $result ) ) {
			self::redirect(
				array(
					'sce_err'    => $result->get_error_message(),
					'sce_prompt' => rawurlencode( $prompt ),
				)
			);
		}

		self::redirect( array( 'sce_msg' => 'saved', 'edit' => (int) $result['id'] ) );
	}

	public static function handle_save(): void {
		self::require_cap( self::CAP_DESIGNER );
		check_admin_referer( 'sce_save' );

		$id     = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$params = array();
		if ( isset( $_POST['params_json'] ) ) {
			$decoded = json_decode( wp_unslash( $_POST['params_json'] ), true );
			if ( is_array( $decoded ) ) {
				$params = $decoded;
			}
		}

		$existing = $id ? SCE_Element_Store::get( $id ) : null;

		$definition = array(
			'name'              => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'base'              => isset( $_POST['base'] ) ? sanitize_text_field( wp_unslash( $_POST['base'] ) ) : '',
			'category'          => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '',
			'template'          => isset( $_POST['template'] ) ? wp_kses_post( wp_unslash( $_POST['template'] ) ) : '',
			'params'            => $params,
			'status'            => $existing['status'] ?? SCE_Element_Store::STATUS_DRAFT,
			'generation_prompt' => $existing['generation_prompt'] ?? '',
		);

		$valid = SCE_Rules::validate_template( $definition['template'] );
		if ( is_wp_error( $valid ) ) {
			self::redirect( array( 'sce_err' => $valid->get_error_message(), 'edit' => $id ) );
		}

		// Se cambia la base, rimuovi il vecchio file generato.
		if ( $existing && $existing['base'] !== SCE_Element_Store::sanitize( $definition )['base'] ) {
			SCE_Code_Generator::delete( $existing['base'] );
		}

		$saved = SCE_Element_Store::save( $definition, $id ?: null );
		if ( is_wp_error( $saved ) ) {
			self::redirect( array( 'sce_err' => $saved->get_error_message() ) );
		}

		self::generate_file( (int) $saved );
		self::sync_preview( (int) $saved );
		self::redirect( array( 'sce_msg' => 'saved', 'edit' => $saved ) );
	}

	public static function handle_status(): void {
		$id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		check_admin_referer( 'sce_status_' . $id );

		if ( SCE_Element_Store::STATUS_ACTIVE === $status ) {
			self::require_cap( self::CAP_APPROVER ); // marcare revisionato e' tuo.
		} else {
			self::require_cap( self::CAP_DESIGNER );
		}

		SCE_Element_Store::set_status( $id, $status );

		$msg = SCE_Element_Store::STATUS_ACTIVE === $status ? 'reviewed' : ( SCE_Element_Store::STATUS_PENDING === $status ? 'proposed' : 'saved' );
		self::redirect( array( 'sce_msg' => $msg, 'edit' => $id ) );
	}

	public static function handle_delete(): void {
		self::require_cap( self::CAP_APPROVER );
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		check_admin_referer( 'sce_delete_' . $id );

		$def = SCE_Element_Store::get( $id );
		if ( $def ) {
			SCE_Code_Generator::delete( $def['base'] );
			SCE_Preview::delete( $id );
		}
		SCE_Element_Store::delete( $id );
		self::redirect( array( 'sce_msg' => 'deleted' ) );
	}

	public static function handle_download(): void {
		self::require_cap( self::CAP_DESIGNER );
		$base = isset( $_GET['base'] ) ? sanitize_file_name( wp_unslash( $_GET['base'] ) ) : '';
		check_admin_referer( 'sce_download_' . $base );

		$path = SCE_Code_Generator::path( $base );
		if ( ! is_file( $path ) ) {
			wp_die( esc_html__( 'File not found.', 'salient-custom-elements' ) );
		}

		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $base . '.php"' );
		readfile( $path );
		exit;
	}

	public static function handle_export(): void {
		self::require_cap( self::CAP_APPROVER );
		check_admin_referer( 'sce_export' );

		$ids = array();
		foreach ( SCE_Element_Store::all() as $def ) {
			$ids[] = (int) $def['id'];
		}

		$zip = SCE_Loader::export_zip( $ids );
		if ( is_wp_error( $zip ) ) {
			self::redirect( array( 'sce_err' => $zip->get_error_message() ) );
		}

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="salient-shipped-elements.zip"' );
		header( 'Content-Length: ' . filesize( $zip ) );
		readfile( $zip );
		@unlink( $zip );
		exit;
	}

	/**
	 * Confeziona il plugin di produzione, lo attiva e rimuove il generatore.
	 */
	public static function handle_ship(): void {
		self::require_cap( self::CAP_APPROVER );
		check_admin_referer( 'sce_ship' );

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$ids = array();
		foreach ( SCE_Element_Store::all() as $def ) {
			$ids[] = (int) $def['id'];
		}

		$main = SCE_Loader::install_production_plugin( $ids );
		if ( is_wp_error( $main ) ) {
			self::redirect( array( 'sce_err' => $main->get_error_message() ) );
		}

		$activate = activate_plugin( $main );
		if ( is_wp_error( $activate ) ) {
			self::redirect( array( 'sce_err' => $activate->get_error_message() ) );
		}

		// Disattiva e prova a rimuovere questo generatore.
		$self = plugin_basename( SCE_FILE );
		deactivate_plugins( $self );

		ob_start();
		$creds = request_filesystem_credentials( '', '', false, false, null );
		ob_end_clean();

		if ( false !== $creds && WP_Filesystem( $creds ) ) {
			delete_plugins( array( $self ) );
		}

		// Il generatore non e' piu' attivo: torna alla lista plugin.
		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

	/* ---------- Utils ---------- */

	private static function generate_file( int $id ): void {
		$def = SCE_Element_Store::get( $id );
		if ( null === $def ) {
			return;
		}
		$result = SCE_Code_Generator::write( $def );
		if ( is_wp_error( $result ) ) {
			self::redirect( array( 'sce_err' => $result->get_error_message() ) );
		}
	}

	private static function sync_preview( int $id ): void {
		$def = SCE_Element_Store::get( $id );
		if ( null === $def ) {
			return;
		}
		$result = SCE_Preview::sync( $id, $def );
		if ( is_wp_error( $result ) ) {
			self::redirect( array( 'sce_err' => $result->get_error_message() ) );
		}
	}

	private static function require_cap( string $cap ): void {
		if ( ! current_user_can( $cap ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'salient-custom-elements' ) );
		}
	}

	private static function redirect( array $args ): void {
		$args['page'] = self::SLUG;
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
}
