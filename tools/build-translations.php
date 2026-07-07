#!/usr/bin/env php
<?php
/**
 * Build POT and Italian PO/MO from extracted source strings.
 */

declare( strict_types = 1 );

$root    = dirname( __DIR__ );
$strings = array();
$raw     = shell_exec( 'php ' . escapeshellarg( __DIR__ . '/extract-strings.php' ) );
foreach ( explode( "\n---\n", trim( (string) $raw ) ) as $block ) {
	$s = trim( $block );
	$s = preg_replace( '/\n---\s*$/', '', $s );
	if ( '' !== $s ) {
		$strings[] = $s;
	}
}
sort( $strings );

$it = array(
	'AI generation is not configured yet. Connect a provider via the sce_ai_provider filter (WP 7 AI Client or API key), or build the element manually.' => 'Generazione AI non configurata. Collega un provider tramite il filtro sce_ai_provider (AI Client di WP 7 o chiave API), oppure crea l\'elemento manualmente.',
	'AI generation is not configured yet. Connect a provider via the sce_ai_provider filter (WP 7 AI Client or API key), or edit the element manually.' => 'Generazione AI non configurata. Collega un provider tramite il filtro sce_ai_provider (AI Client di WP 7 o chiave API), oppure modifica l\'elemento manualmente.',
	'AI preserves it during edits; change it manually only if necessary' => 'L\'AI lo preserva durante le modifiche; cambialo manualmente solo se necessario',
	'API for registering custom shortcodes.' => 'API per registrare shortcode personalizzati.',
	'ARIA only when needed; prefer native elements.' => 'ARIA solo quando serve; preferisci elementi nativi.',
	'Accent color' => 'Colore accent',
	'Accessibility' => 'Accessibilità',
	'Accessibility: an <img> is missing the alt attribute.' => 'Accessibilità: un\'<img> non ha l\'attributo alt.',
	'Actions' => 'Azioni',
	'Apply changes' => 'Applica modifiche',
	'Applying changes with AI…' => 'Applicazione modifiche con AI…',
	'Assistant' => 'Assistente',
	'Avoid for AI' => 'Evitare per l\'AI',
	'Avoid for AI.' => 'Evitare per l\'AI.',
	'Back to plugins' => 'Torna ai plugin',
	'Base' => 'Base',
	'Base (shortcode tag)' => 'Base (tag shortcode)',
	'Bindable Salient options' => 'Opzioni Salient collegabili',
	'Body border color' => 'Colore bordo body',
	'Body font' => 'Font body',
	'Button roundness' => 'Arrotondamento bottoni',
	'Button styling' => 'Stile bottoni',
	'Changes applied: %s.' => 'Modifiche applicate: %s.',
	'Colors' => 'Colori',
	'Colors pass through sanitize_hex_color (or rgb/hsl whitelist); image IDs through absint.' => 'I colori passano da sanitize_hex_color (o whitelist rgb/hsl); gli ID immagine da absint.',
	'Column spacing' => 'Spaziatura colonne',
	'Complete!' => 'Completato!',
	'Creating the preview page…' => 'Creazione pagina anteprima…',
	'Current value' => 'Valore attuale',
	'Custom Elements' => 'Elementi personalizzati',
	'Definition and generated file deleted.' => 'Definizione e file generato eliminati.',
	'Definition received, validating JSON…' => 'Definizione ricevuta, validazione JSON…',
	'Definition updated, validating JSON…' => 'Definizione aggiornata, validazione JSON…',
	'Delete' => 'Elimina',
	'Delete definition and generated file?' => 'Eliminare definizione e file generato?',
	'Describe in natural language what to change. AI updates the element and the form fields refresh automatically.' => 'Descrivi in linguaggio naturale cosa cambiare. L\'AI aggiorna l\'elemento e i campi del form si aggiornano automaticamente.',
	'Describe the changes to apply.' => 'Descrivi le modifiche da applicare.',
	'Describe the changes you want to make…' => 'Descrivi le modifiche che vuoi fare…',
	'Describe the element to generate.' => 'Descrivi l\'elemento da generare.',
	'Describe the element. AI produces a definition that follows SEO, security, accessibility, and responsive rules; then you can refine it.' => 'Descrivi l\'elemento. L\'AI produce una definizione che rispetta le regole SEO, sicurezza, accessibilità e responsive; poi puoi rifinirla.',
	'Description' => 'Descrizione',
	'Description used to generate this element. For further changes use the chat below the form.' => 'Descrizione usata per generare questo elemento. Per ulteriori modifiche usa la chat sotto il form.',
	'Descriptive links, never "click here"; rel="noopener" on target _blank.' => 'Link descrittivi, mai "clicca qui"; rel="noopener" su target _blank.',
	'Dev/staging tool. Created elements are usable immediately in the editor. Review the generated PHP, then export the production plugin and remove this generator.' => 'Strumento da dev/staging. Gli elementi creati sono usabili subito nell\'editor. Revisiona il PHP generato, poi esporta il plugin di produzione e rimuovi questo generatore.',
	'Developed by <a href="%s" target="_blank" rel="noopener noreferrer">Riccardo Di Curti</a>.' => 'Sviluppato da <a href="%s" target="_blank" rel="noopener noreferrer">Riccardo Di Curti</a>.',
	'Direct value of a Salient theme option.' => 'Valore diretto di un\'opzione del tema Salient.',
	'Discernible text or aria-label on buttons and links; empty alt for decorative images.' => 'Testo distinguibile o aria-label su bottoni e link; alt vuoto per immagini decorative.',
	'Download PHP' => 'Scarica PHP',
	'Download zip (all elements)' => 'Scarica zip (tutti gli elementi)',
	'E.g.: a hero with title, subtitle, and button, color inherited from the theme accent color' => 'Es.: una hero con titolo, sottotitolo e bottone, colore ereditato dall\'accent color del tema',
	'E.g.: add a parameter for the title color' => 'Es.: aggiungi un parametro per il colore del titolo',
	'Edit / review' => 'Modifica / review',
	'Edit element' => 'Modifica elemento',
	'Edit with AI' => 'Modifica con AI',
	'Element generated and available in the editor. The form below is pre-filled: edit and save to refine.' => 'Elemento generato e disponibile nell\'editor. Il form sotto è precompilato: modifica e salva per rifinire.',
	'Element marked as reviewed.' => 'Elemento marcato come revisionato.',
	'Element not found.' => 'Elemento non trovato.',
	'Element proposed for review.' => 'Elemento proposto per la revisione.',
	'Element updated. Check the form fields for details.' => 'Elemento aggiornato. Controlla i campi del form per i dettagli.',
	'Element updated. You can continue requesting changes or save manually.' => 'Elemento aggiornato. Puoi continuare a richiedere modifiche o salvare manualmente.',
	'Elements' => 'Elementi',
	'Enclosed content for shortcodes with opening and closing tags.' => 'Contenuto racchiuso per shortcode con tag di apertura e chiusura.',
	'Error' => 'Errore',
	'Every dynamic value must be escaped on output: esc_html for text, esc_attr for attributes, esc_url for URLs, wp_kses_post for rich content.' => 'Ogni valore dinamico deve essere escapato in output: esc_html per testo, esc_attr per attributi, esc_url per URL, wp_kses_post per contenuto ricco.',
	'Every file has an ABSPATH guard and prefixed functions to avoid collisions.' => 'Ogni file ha guard ABSPATH e funzioni prefissate per evitare collisioni.',
	'Example' => 'Esempio',
	'Example: %s' => 'Esempio: %s',
	'Extra color 1' => 'Colore extra 1',
	'Extra color 2' => 'Colore extra 2',
	'Extra color 3' => 'Colore extra 3',
	'Extracted at runtime from WPBakery: %d elements.' => 'Estratti a runtime da WPBakery: %d elementi.',
	'FORBIDDEN: raw CSS, JavaScript, <script> tags, <style> tags, IIFEs, window.addEventListener in the template.' => 'VIETATO: CSS grezzo, JavaScript, tag <script>, tag <style>, IIFE, window.addEventListener nel template.',
	'File not found.' => 'File non trovato.',
	'Flex/grid layouts that reflow; breakpoints consistent with Salient.' => 'Layout flex/grid che si adattano; breakpoint coerenti con Salient.',
	'Fluid type with clamp(); custom properties for gap and colors.' => 'Tipografia fluida con clamp(); custom property per gap e colori.',
	'For SCE elements: template with semantic HTML ONLY and {{param}} tokens; no raw CSS/JS.' => 'Per elementi SCE: template con SOLO HTML semantico e token {{param}}; niente CSS/JS grezzo.',
	'Full-width section image.' => 'Immagine sezione full-width.',
	'GSAP animations or custom scripts require separate fields (not yet supported): use only generated HTML/CSS.' => 'Animazioni GSAP o script custom richiedono campi separati (non ancora supportati): usa solo HTML/CSS generato.',
	'Generate' => 'Genera',
	'Generate with AI' => 'Genera con AI',
	'Generated code review' => 'Review codice generato',
	'Generating the definition with AI…' => 'Generazione definizione con AI…',
	'Generating…' => 'Generazione in corso…',
	'Global Sections and Salient page builder.' => 'Global Sections e page builder Salient.',
	'Golden examples (Salient)' => 'Esempi golden (Salient)',
	'HTML Template' => 'Template HTML',
	'Hero CTA (example)' => 'Hero CTA (esempio)',
	'Images with max-width:100% and height:auto.' => 'Immagini con max-width:100% e height:auto.',
	'Images with meaningful alt, loading="lazy", decoding="async", and width/height to prevent CLS.' => 'Immagini con alt significativo, loading="lazy", decoding="async", e width/height per evitare CLS.',
	'Inputs go through shortcode_atts with explicit defaults; no extract().' => 'Gli input passano da shortcode_atts con default espliciti; niente extract().',
	'Insufficient permissions.' => 'Permessi insufficienti.',
	'Interactive elements keyboard-focusable with visible focus (:focus-visible).' => 'Elementi interattivi focusabili da tastiera con focus visibile (:focus-visible).',
	'Invalid nonce.' => 'Nonce non valido.',
	'Key' => 'Chiave',
	'Knowledge base WPBakery.' => 'Knowledge base WPBakery.',
	'Layout' => 'Layout',
	'Loading element "%s" and preparing context…' => 'Caricamento elemento "%s" e preparazione contesto…',
	'Malformed HTML template: closing tag </{{%1$s}}> without opening <{{%1$s}}>. If you paste HTML code, ask the AI to convert it into a valid template with paired dynamic tags.' => 'Template HTML malformato: tag di chiusura </{{%1$s}}> senza apertura <{{%1$s}}>. Se incolli codice HTML, chiedi all\'AI di convertirlo in un template valido con tag dinamici accoppiati.',
	'Malformed HTML template: opening tag <{{%1$s}}> without closing </{{%1$s}}>.' => 'Template HTML malformato: tag di apertura <{{%1$s}}> senza chiusura </{{%1$s}}>.',
	'Max container width' => 'Larghezza max container',
	'Missing base, unable to generate.' => 'Base mancante, impossibile generare.',
	'Missing dependencies' => 'Dipendenze mancanti',
	'Mobile-first, fluid units (rem, %, clamp()), no fixed widths that break the layout.' => 'Mobile-first, unità fluide (rem, %, clamp()), niente larghezze fisse che rompono il layout.',
	'Modifying…' => 'Modifica in corso…',
	'Multiple selection.' => 'Selezione multipla.',
	'Name' => 'Nome',
	'Name and base (shortcode tag) are required.' => 'Nome e base (tag shortcode) sono obbligatori.',
	'Navigation font' => 'Font navigazione',
	'Network error' => 'Errore di rete',
	'Never use eval, create_function, dynamic includes, or unescaped output.' => 'Non usare eval, create_function, include dinamici o output non escapato.',
	'New request:' => 'Nuova richiesta:',
	'No AI model configured in the AI plugin.' => 'Nessun modello AI configurato nel plugin AI.',
	'No elements to package.' => 'Nessun elemento da confezionare.',
	'No elements yet.' => 'Nessun elemento ancora.',
	'No informational text injected only via CSS.' => 'Nessun testo informativo iniettato solo via CSS.',
	'No nectar_* elements detected. Make sure Salient and WPBakery are active and initialized.' => 'Nessun elemento nectar_* rilevato. Verifica che Salient e WPBakery siano attivi e inizializzati.',
	'No warnings: meets the basic rules.' => 'Nessun avviso: rispetta le regole base.',
	'Note' => 'Nota',
	'Numeric slider.' => 'Slider numerico.',
	'Official WPBakery support.' => 'Supporto ufficiale WPBakery.',
	'Official resources' => 'Risorse ufficiali',
	'Official theme documentation.' => 'Documentazione ufficiale del tema.',
	'One logical heading per element, configurable level among h2, h3, h4, without skipping levels.' => 'Un heading logico per elemento, livello configurabile tra h2, h3, h4, senza saltare livelli.',
	'One or more required dependencies are missing:' => 'Mancano una o più dipendenze richieste:',
	'Optional' => 'Opzionale',
	'Original prompt' => 'Prompt originale',
	'Overall background' => 'Sfondo generale',
	'Overall font color' => 'Colore font generale',
	'PHP Zip extension not available.' => 'Estensione PHP Zip non disponibile.',
	'Package and remove generator' => 'Confeziona e rimuovi il generatore',
	'Package the production plugin with all elements, activate it, and remove this generator. Continue?' => 'Confezionare il plugin di produzione con tutti gli elementi, attivarlo e rimuovere questo generatore. Continuare?',
	'Parameters (JSON)' => 'Parametri (JSON)',
	'Preparing Salient context and rules…' => 'Preparazione contesto Salient e regole…',
	'Preview' => 'Anteprima',
	'Preview unavailable' => 'Anteprima non disponibile',
	'Preview: %s' => 'Anteprima: %s',
	'Production rules' => 'Regole di produzione',
	'Prompt unavailable (element created manually or before this feature).' => 'Prompt non disponibile (elemento creato manualmente o prima di questa funzione).',
	'Real patterns from nectar_maps that AI draws inspiration from.' => 'Pattern reali da nectar_maps da cui l\'AI trae ispirazione.',
	'Recommended' => 'Consigliato',
	'Recommended for generated elements.' => 'Consigliato per elementi generati.',
	'Respect prefers-reduced-motion for every animation.' => 'Rispetta prefers-reduced-motion per ogni animazione.',
	'Responsive' => 'Responsive',
	'Responsive margins/padding.' => 'Margini/padding responsive.',
	'Responsive padding' => 'Padding responsive',
	'Responsive scoped CSS is generated automatically by the plugin; do not duplicate it in the template.' => 'Il CSS responsive scoped è generato automaticamente dal plugin; non duplicarlo nel template.',
	'Return ONLY a valid JSON object conforming to output_schema. No text, markdown, or PHP. The template MUST contain ONLY semantic HTML with {{param}} tokens: raw CSS, JavaScript, script/style tags, and IIFEs are FORBIDDEN. Use only recommended param_types. For theme colors use salient_option or {{binding:accent-color}}. Follow golden_elements patterns. Respect production_rules.' => 'Restituisci SOLO un oggetto JSON valido conforme a output_schema. Niente testo, markdown o PHP. Il template DEVE contenere SOLO HTML semantico con token {{param}}: CSS grezzo, JavaScript, tag script/style e IIFE sono VIETATI. Usa solo param_types consigliati. Per i colori tema usa salient_option o {{binding:accent-color}}. Segui i pattern golden_elements. Rispetta production_rules.',
	'SEO' => 'SEO',
	'SEO: no heading (h2-h4) in the template. Add one for page structure.' => 'SEO: nessun heading (h2-h4) nel template. Aggiungine uno per la struttura della pagina.',
	'SEO: only div/span, no semantic tags. Consider section/article/figure.' => 'SEO: solo div/span, nessun tag semantico. Considera section/article/figure.',
	'Salient Custom' => 'Salient Custom',
	'Salient Custom Elements' => 'Salient Custom Elements',
	'Salient Custom Elements active: remove before production' => 'Salient Custom Elements attivo: rimuovi prima della produzione',
	'Salient Docs' => 'Documentazione Salient',
	'Salient Page Builder / Global Sections' => 'Salient Page Builder / Global Sections',
	'Salient custom param types' => 'Tipi param Salient personalizzati',
	'Salient elements reference' => 'Riferimento elementi Salient',
	'Salient elements use vc_lean_map() with files in salient-core/includes/nectar_maps/{base}.php.' => 'Gli elementi Salient usano vc_lean_map() con file in salient-core/includes/nectar_maps/{base}.php.',
	'Salient overrides vc_row and vc_column: do not use standard WPB container maps.' => 'Salient sovrascrive vc_row e vc_column: non usare le mappe container WPB standard.',
	'Salient theme not active' => 'Tema Salient non attivo',
	'Salient vc_map patterns' => 'Pattern vc_map Salient',
	'Sample text for preview.' => 'Testo di esempio per l\'anteprima.',
	'Save and generate' => 'Salva e genera',
	'Saved and generated. Element available in the editor (reload WPBakery).' => 'Salvato e generato. Elemento disponibile nell\'editor (ricarica WPBakery).',
	'Saving changes and regenerating PHP…' => 'Salvataggio modifiche e rigenerazione PHP…',
	'Saving the element and generating PHP…' => 'Salvataggio elemento e generazione PHP…',
	'Security' => 'Sicurezza',
	'Security: link with target=_blank without rel="noopener".' => 'Sicurezza: link con target=_blank senza rel="noopener".',
	'Security: on* attributes (inline handlers) in the template. Remove them.' => 'Sicurezza: attributi on* (handler inline) nel template. Rimuovili.',
	'Semantic HTML5: section/article/header/figure/nav instead of generic divs.' => 'HTML5 semantico: section/article/header/figure/nav invece di div generici.',
	'Shared reference for designers and the AI generator. Rules are applied to generated code and enforced on the model.' => 'Riferimento condiviso per designer e generatore AI. Le regole sono applicate al codice generato e imposte al modello.',
	'Status' => 'Stato',
	'Structure: return array( name, base, category, icon, params => [...] ).' => 'Struttura: return array( name, base, category, icon, params => [...] ).',
	'Support development' => 'Supporta lo sviluppo',
	'Template ({{param}} and {{binding:opt-key}} tokens)' => 'Template (token {{param}} e {{binding:opt-key}})',
	'Template tokens' => 'Token template',
	'The AI model is temporarily overloaded. Wait a few minutes and try again. If the problem persists, switch models in the AI plugin settings (e.g. change from gemini-2.5-flash to gemini-2.0-flash).' => 'Il modello AI è temporaneamente sovraccarico. Attendi qualche minuto e riprova. Se il problema persiste, cambia modello nelle impostazioni del plugin AI (es. passa da gemini-2.5-flash a gemini-2.0-flash).',
	'The HTML template is required.' => 'Il template HTML è obbligatorio.',
	'The model did not return valid JSON.' => 'Il modello non ha restituito JSON valido.',
	'The template cannot contain JavaScript. Remove inline scripts or IIFEs: CSS/JS is generated automatically by the plugin.' => 'Il template non può contenere JavaScript. Rimuovi script inline o IIFE: CSS/JS è generato automaticamente dal plugin.',
	'The template cannot contain raw CSS. Responsive CSS is generated automatically by the plugin.' => 'Il template non può contenere CSS grezzo. Il CSS responsive è generato automaticamente dal plugin.',
	'The template cannot contain script or style tags. Use semantic HTML only.' => 'Il template non può contenere tag script o style. Usa solo HTML semantico.',
	'The template contains ONLY semantic HTML with {{param_name}} and {{binding:opt-key}} tokens.' => 'Il template contiene SOLO HTML semantico con token {{param_name}} e {{binding:opt-key}}.',
	'Theme color dropdown with swatches.' => 'Dropdown colori tema con campioni.',
	'Theme colors in dropdowns: accent-color, extra-color-1, extra-color-gradient-1 (lowercase).' => 'Colori tema nei dropdown: accent-color, extra-color-1, extra-color-gradient-1 (minuscolo).',
	'Theme gradient 1' => 'Gradiente tema 1',
	'Theme gradient 2' => 'Gradiente tema 2',
	'Theme skin' => 'Skin tema',
	'To fix:' => 'Da correggere:',
	'Too complex for AI generation.' => 'Troppo complesso per la generazione AI.',
	'Touch targets at least 44x44px; no information conveyed by color alone; AA contrast.' => 'Target touch almeno 44x44px; nessuna informazione veicolata solo dal colore; contrasto AA.',
	'Type' => 'Tipo',
	'Typography' => 'Tipografia',
	'Unable to activate Salient Custom Elements' => 'Impossibile attivare Salient Custom Elements',
	'Unable to create the plugin folder.' => 'Impossibile creare la cartella del plugin.',
	'Unable to create the zip file.' => 'Impossibile creare il file zip.',
	'Unable to write the generated file. Check uploads permissions.' => 'Impossibile scrivere il file generato. Verifica i permessi su uploads.',
	'Updating the preview page…' => 'Aggiornamento pagina anteprima…',
	'Use group (e.g. Design Options) and dependency for conditional show/hide.' => 'Usa group (es. Design Options) e dependency per show/hide condizionale.',
	'Use only when necessary.' => 'Usa solo quando necessario.',
	'User' => 'Utente',
	'Valid structure: %1$s (%2$s)' => 'Struttura valida: %1$s (%2$s)',
	'Value of a parameter, escaped according to its type.' => 'Valore di un parametro, escapato secondo il tipo.',
	'Values read in real time from the active theme settings. Use them as salient_option on a parameter or as {{binding:key}}.' => 'Valori letti in tempo reale dalle impostazioni del tema attivo. Usali come salient_option su un parametro o come {{binding:key}}.',
	'Version %1$s · %2$s' => 'Versione %1$s · %2$s',
	'GPL v2 or later' => 'GPL v2 o successive',
	'View preview' => 'Vedi anteprima',
	'Visual shape/underline selection.' => 'Selezione forma/sottolineatura visiva.',
	'WPBakery Knowledge Base' => 'Knowledge Base WPBakery',
	'WPBakery Page Builder not active' => 'WPBakery Page Builder non attivo',
	'WPBakery Support' => 'Supporto WPBakery',
	'WPBakery category' => 'Categoria WPBakery',
	'WPBakery param types' => 'Tipi param WPBakery',
	'WPBakery vc_map API' => 'API vc_map WPBakery',
	'Wiki and rules' => 'Wiki e regole',
	'You are editing an existing Salient Custom Elements element. Return ONLY a valid JSON object conforming to output_schema with the ENTIRE updated definition. Do not add text, markdown, or PHP. Preserve the base field (shortcode tag) and status unless the user explicitly requests a change. Apply only the requested changes while staying consistent with production_rules and golden_elements. If the user pastes HTML/CSS/JS code: convert it to semantic HTML ONLY with {{param}} tokens; remove CSS and JavaScript from the template; for dynamic tags always use paired tags, e.g. <{{heading_tag}}>{{title}}</{{heading_tag}}>.' => 'Stai modificando un elemento Salient Custom Elements esistente. Restituisci SOLO un oggetto JSON valido conforme a output_schema con l\'INTERA definizione aggiornata. Non aggiungere testo, markdown o PHP. Preserva il campo base (tag shortcode) e status salvo richiesta esplicita. Applica solo le modifiche richieste rispettando production_rules e golden_elements. Se l\'utente incolla codice HTML/CSS/JS: convertilo in SOLO HTML semantico con token {{param}}; rimuovi CSS e JavaScript dal template; per i tag dinamici usa sempre tag accoppiati, es. <{{heading_tag}}>{{title}}</{{heading_tag}}>.',
	'category updated' => 'categoria aggiornata',
	'missing dependencies, functionality disabled.' => 'dipendenze mancanti, funzionalità disattivate.',
	'name updated' => 'nome aggiornato',
	'parameters updated' => 'parametri aggiornati',
	'template updated' => 'template aggiornato',
);

function po_quote( string $s ): string {
	$s = str_replace( array( '\\', '"', "\n", "\r", "\t" ), array( '\\\\', '\\"', '\\n', '', '\\t' ), $s );
	return '"' . $s . '"';
}

function build_po_header( string $lang = '' ): string {
	$header  = "# Copyright (C) 2026 Riccardo Di Curti\n";
	$header .= "# This file is distributed under the same license as the Salient Custom Elements package.\n";
	$header .= "msgid \"\"\n";
	$header .= "msgstr \"\"\n";
	$header .= "\"Project-Id-Version: Salient Custom Elements 0.2.0\\n\"\n";
	$header .= "\"Report-Msgid-Bugs-To: https://riccardodicurti.it\\n\"\n";
	$header .= "\"POT-Creation-Date: 2026-07-07 12:00+0000\\n\"\n";
	$header .= "\"PO-Revision-Date: 2026-07-07 12:00+0000\\n\"\n";
	$header .= "\"Last-Translator: Riccardo Di Curti <https://riccardodicurti.it>\\n\"\n";
	$header .= "\"Language-Team: Italian\\n\"\n";
	if ( '' !== $lang ) {
		$header .= "\"Language: it_IT\\n\"\n";
	}
	$header .= "\"MIME-Version: 1.0\\n\"\n";
	$header .= "\"Content-Type: text/plain; charset=UTF-8\\n\"\n";
	$header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
	$header .= "\"Plural-Forms: nplurals=2; plural=(n != 1);\\n\"\n\n";
	return $header;
}

function build_entries( array $strings, array $it, bool $translate ): string {
	$out = '';
	foreach ( $strings as $msgid ) {
		$out .= 'msgid ' . po_quote( $msgid ) . "\n";
		if ( $translate ) {
			$msgstr = $it[ $msgid ] ?? $msgid;
			$out .= 'msgstr ' . po_quote( $msgstr ) . "\n\n";
		} else {
			$out .= "msgstr \"\"\n\n";
		}
	}
	return $out;
}

$pot_path = $root . '/languages/salient-custom-elements.pot';
$po_path  = $root . '/languages/salient-custom-elements-it_IT.po';
$mo_path  = $root . '/languages/salient-custom-elements-it_IT.mo';

file_put_contents( $pot_path, build_po_header() . build_entries( $strings, $it, false ) );
file_put_contents( $po_path, build_po_header( 'it_IT' ) . build_entries( $strings, $it, true ) );

$missing = array();
foreach ( $strings as $s ) {
	if ( ! isset( $it[ $s ] ) ) {
		$missing[] = $s;
	}
}
if ( ! empty( $missing ) ) {
	fwrite( STDERR, 'Missing IT translations: ' . count( $missing ) . "\n" );
	foreach ( $missing as $m ) {
		fwrite( STDERR, " - $m\n" );
	}
}

$msgfmt = trim( (string) shell_exec( 'command -v msgfmt' ) );
if ( '' !== $msgfmt ) {
	passthru( escapeshellcmd( $msgfmt ) . ' -o ' . escapeshellarg( $mo_path ) . ' ' . escapeshellarg( $po_path ), $code );
	exit( 0 === $code ? 0 : 1 );
}

// Fallback: compile with PHP if msgfmt unavailable.
if ( ! class_exists( 'PO' ) ) {
	require_once dirname( __DIR__, 5 ) . '/wp-includes/pomo/po.php';
}
if ( class_exists( 'PO' ) ) {
	$po = new PO();
	if ( $po->import_from_file( $po_path ) ) {
		$mo = new MO();
		$mo->entries = $po->entries;
		foreach ( $mo->entries as $entry ) {
			if ( ! $entry->singular ) {
				continue;
			}
			$entry->translations = array( $entry->translations[0] ?? $it[ $entry->singular ] ?? $entry->singular );
		}
		$mo->export_to_file( $mo_path );
		echo "Built POT, PO, and MO (" . count( $strings ) . " strings)\n";
		exit( 0 );
	}
}

echo "Built POT and PO only; msgfmt not available (" . count( $strings ) . " strings)\n";
exit( empty( $missing ) ? 0 : 1 );
