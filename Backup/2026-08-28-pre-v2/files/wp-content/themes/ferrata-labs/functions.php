<?php
/**
 * Ferrata Labs theme bootstrap.
 *
 * The site is a port of the Next.js build. Page bodies are static markup stored in
 * parts/*.php and selected by page URI — the WordPress editor is deliberately not the
 * source of truth for the ported pages, because the markup carries the design system
 * (CLAUDE.md §4) and a visual editor would degrade it. Pages created later that have no
 * matching part fall back to the_content(), so the site stays extensible.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FERRATA_VERSION', '1.1.3' );

/** Page URI => part file. The URI is what get_page_uri() returns, e.g. "agents/sterling". */
function ferrata_routes(): array {
	$routes = array(
		'work'         => 'work',
		'how-it-works' => 'how-it-works',
		'about'        => 'about',
		'contact'      => 'contact',
		'pledge'       => 'pledge',
		'thank-you'    => 'thank-you',
	);
	foreach ( array( 'sterling', 'clark', 'tally', 'chandler', 'swift', 'quill' ) as $s ) {
		$routes[ 'agents/' . $s ] = 'agents-' . $s;
	}
	foreach ( array(
		'financial-services',
		'food-and-beverage',
		'construction',
		'manufacturing',
		'venture-and-private-capital',
		'logistics-and-supply-chain',
	) as $s ) {
		$routes[ 'industries/' . $s ] = 'industries-' . $s;
	}
	return $routes;
}

/** Titles and descriptions lifted from the Next.js metadata exports. */
function ferrata_meta(): array {
	static $meta = null;
	if ( null === $meta ) {
		$file = get_theme_file_path( 'inc/meta.json' );
		$meta = is_readable( $file )
			? (array) json_decode( (string) file_get_contents( $file ), true )
			: array();
	}
	return $meta;
}

/** Which part should render for the current request. */
function ferrata_part_name(): string {
	if ( is_front_page() ) {
		return 'home';
	}
	if ( ! is_page() ) {
		return '';
	}
	$uri    = get_page_uri( get_queried_object_id() );
	$routes = ferrata_routes();
	return $routes[ $uri ] ?? '';
}


/* ------------------------------------------------------------------ setup */

add_action( 'after_setup_theme', 'ferrata_setup' );
function ferrata_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'search-form', 'style', 'script' ) );
	add_theme_support( 'automatic-feed-links' );
	remove_theme_support( 'core-block-patterns' );
}

add_action( 'wp_enqueue_scripts', 'ferrata_assets' );
function ferrata_assets(): void {
	// Archivo 400/500/600/800, IBM Plex Sans 400/500/600, IBM Plex Mono 400/500 — CLAUDE.md §4.
	wp_enqueue_style(
		'ferrata-fonts',
		get_theme_file_uri( 'assets/fonts.css' ),
		array(),
		FERRATA_VERSION
	);
	wp_enqueue_style( 'ferrata-site', get_theme_file_uri( 'assets/site.css' ), array( 'ferrata-fonts' ), FERRATA_VERSION );
	wp_enqueue_style( 'ferrata-theme', get_theme_file_uri( 'assets/theme.css' ), array( 'ferrata-site' ), FERRATA_VERSION );

	wp_enqueue_script( 'ferrata-site', get_theme_file_uri( 'assets/site.js' ), array(), FERRATA_VERSION, true );

	// No blocks are rendered on the ported pages, so the block stylesheet is dead weight.
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}

add_action( 'wp_head', 'ferrata_head_extras', 1 );
function ferrata_head_extras(): void {
	printf(
		'<link rel="icon" href="%s" sizes="any" type="image/svg+xml" />' . "\n",
		esc_url( add_query_arg( 'ver', FERRATA_VERSION, get_theme_file_uri( 'assets/icon.svg' ) ) )
	);
	printf(
		'<link rel="apple-touch-icon" href="%s" sizes="180x180" />' . "\n",
		esc_url( add_query_arg( 'ver', FERRATA_VERSION, get_theme_file_uri( 'assets/apple-icon.png' ) ) )
	);

	$part = ferrata_part_name();
	$meta = ferrata_meta();

	// CLAUDE.md §12 — the product route group is unlisted. Anything under it stays out of the index.
	if ( 'pledge' === $part || 'thank-you' === $part ) {
		echo '<meta name="robots" content="noindex, nofollow, noarchive, nocache" />' . "\n";
		return;
	}

	if ( $part && ! empty( $meta[ $part ]['desc'] ) ) {
		$desc  = $meta[ $part ]['desc'];
		$title = $meta[ $part ]['title'] ?? '';
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
		printf( '<meta property="og:type" content="website" />' . "\n" );
		printf( '<meta property="og:site_name" content="Ferrata Labs" />' . "\n" );
		printf( '<meta property="og:locale" content="en_US" />' . "\n" );
		printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
		printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $desc ) );
		printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( ferrata_current_url() ) );
		printf( '<meta name="twitter:card" content="summary_large_image" />' . "\n" );
		printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $title ) );
		printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $desc ) );
	}
}

function ferrata_current_url(): string {
	$id = get_queried_object_id();
	return $id ? (string) get_permalink( $id ) : home_url( '/' );
}

/** Use the exact titles from the Next.js metadata rather than "Page — Site Name". */
add_filter( 'pre_get_document_title', 'ferrata_document_title' );
function ferrata_document_title( string $title ): string {
	$part = ferrata_part_name();
	$meta = ferrata_meta();
	if ( $part && ! empty( $meta[ $part ]['title'] ) ) {
		return $meta[ $part ]['title'];
	}
	return $title;
}

/** Keep the unlisted product out of sitemaps as well as out of the index. */
add_filter( 'wp_sitemaps_posts_query_args', 'ferrata_sitemap_exclude', 10, 2 );
function ferrata_sitemap_exclude( array $args, string $post_type ): array {
	if ( 'page' !== $post_type ) {
		return $args;
	}
	foreach ( array( 'pledge', 'thank-you' ) as $ferrata_hidden ) {
		$page = get_page_by_path( $ferrata_hidden );
		if ( $page ) {
			$args['post__not_in'] = array_merge( $args['post__not_in'] ?? array(), array( $page->ID ) );
		}
	}
	return $args;
}

/**
 * /agents/ and /industries/ exist only as parents so the child URLs work. The Next.js
 * site had no index route for either, so send visitors to the page that does the job.
 */
add_action( 'template_redirect', 'ferrata_parent_redirects' );
function ferrata_parent_redirects(): void {
	if ( ! is_page() ) {
		return;
	}
	$uri = get_page_uri( get_queried_object_id() );
	if ( in_array( $uri, array( 'agents', 'industries' ), true ) ) {
		wp_safe_redirect( home_url( '/work/' ), 301 );
		exit;
	}
}


/* ------------------------------------------------------------------ nav helpers */

/** Echoes aria-current="page" — the accent underline in the nav is driven by it. */
function ferrata_current( string $uri ): void {
	if ( is_page() && get_page_uri( get_queried_object_id() ) === $uri ) {
		echo ' aria-current="page"';
	}
}

function ferrata_in_solutions(): bool {
	if ( ! is_page() ) {
		return false;
	}
	$uri = get_page_uri( get_queried_object_id() );
	return str_starts_with( (string) $uri, 'agents' ) || str_starts_with( (string) $uri, 'industries' );
}


/* ------------------------------------------------------------------ contact form */

require_once get_theme_file_path( 'inc/contact-handler.php' );


/* ------------------------------------------------------------------ first-run setup */

/**
 * Creating twenty pages by hand in the admin is twenty chances to mistype a slug, and a
 * mistyped slug silently breaks a nav link. So activation provisions them.
 */
add_action( 'after_switch_theme', 'ferrata_provision' );
function ferrata_provision(): void {
	$pages = array(
		// uri => [title, parent uri]
		'work'         => array( 'The work', '' ),
		'how-it-works' => array( 'How it runs', '' ),
		'about'        => array( 'Who we are', '' ),
		'contact'      => array( 'Book a discovery call', '' ),
		'pledge'       => array( 'Pledge', '' ),
		'thank-you'    => array( 'Thank you', '' ),
		'agents'       => array( 'Agents', '' ),
		'industries'   => array( 'Industries', '' ),
	);
	$agents = array(
		'sterling'  => 'Sterling',
		'clark'     => 'Clark',
		'tally'     => 'Tally',
		'chandler'  => 'Chandler',
		'swift'     => 'Swift',
		'quill'     => 'Quill',
	);
	$industries = array(
		'financial-services'          => 'Financial services',
		'food-and-beverage'           => 'Food & beverage',
		'construction'                => 'Construction',
		'manufacturing'               => 'Manufacturing',
		'venture-and-private-capital' => 'Venture & private capital',
		'logistics-and-supply-chain'  => 'Logistics & supply chain',
	);
	foreach ( $agents as $slug => $title ) {
		$pages[ 'agents/' . $slug ] = array( $title, 'agents' );
	}
	foreach ( $industries as $slug => $title ) {
		$pages[ 'industries/' . $slug ] = array( $title, 'industries' );
	}

	// Home first, so it can be set as the front page.
	$home = get_page_by_path( 'home' );
	if ( ! $home ) {
		$home_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Home',
				'post_name'   => 'home',
			)
		);
	} else {
		$home_id = $home->ID;
	}

	$ids = array();
	foreach ( $pages as $uri => $spec ) {
		list( $title, $parent_uri ) = $spec;
		if ( get_page_by_path( $uri ) ) {
			continue;
		}
		$slug   = basename( $uri );
		$parent = 0;
		if ( $parent_uri ) {
			$parent = $ids[ $parent_uri ] ?? 0;
			if ( ! $parent ) {
				$p      = get_page_by_path( $parent_uri );
				$parent = $p ? $p->ID : 0;
			}
		}
		$id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_name'   => $slug,
				'post_parent' => $parent,
			)
		);
		if ( ! is_wp_error( $id ) ) {
			$ids[ $uri ] = $id;
		}
	}

	if ( $home_id && ! is_wp_error( $home_id ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_id );
	}

	// The WordPress starter content is not ours and would be crawlable.
	foreach ( array( 'sample-page', 'hello-world' ) as $junk ) {
		$post = get_page_by_path( $junk, OBJECT, array( 'page', 'post' ) );
		if ( $post ) {
			wp_trash_post( $post->ID );
		}
	}

	update_option( 'blogname', 'Ferrata Labs' );
	update_option( 'blogdescription', 'Enterprise AI agents that do the actual work' );

	// Pretty permalinks are required: every internal link is /slug/.
	if ( '' === get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	flush_rewrite_rules();

	set_transient( 'ferrata_just_activated', 1, 300 );
}

/** The three things activation cannot do for you. */
add_action( 'admin_notices', 'ferrata_activation_notice' );
function ferrata_activation_notice(): void {
	if ( ! get_transient( 'ferrata_just_activated' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	delete_transient( 'ferrata_just_activated' );
	?>
	<div class="notice notice-info">
		<p><strong>Ferrata Labs theme activated.</strong> All 20 pages were created, the homepage is set and permalinks are set to /%postname%/. Three things left:</p>
		<ol>
			<li>Send a test submission through <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">the contact form</a> and confirm it arrives. Submissions are also saved under <strong>Leads</strong> in this menu, so nothing is lost if mail is misconfigured.</li>
			<li>Under <em>Settings &rarr; Reading</em>, confirm search engines are <strong>not</strong> discouraged.</li>
			<li>If a caching or optimisation plugin is active, disable its CSS/JS minification first and re-enable it one setting at a time &mdash; the design depends on a single hand-tuned stylesheet.</li>
		</ol>
	</div>
	<?php
}

/**
 * Mail is handed to the mail transport by this web server, not by Google, so it must
 * present an address on this domain — the SPF record authorises this host for it.
 * WordPress would otherwise send as wordpress@<server hostname>, which fails
 * alignment and is filed as spam.
 */
add_filter( 'wp_mail_from', 'ferrata_mail_from' );
function ferrata_mail_from(): string {
	return 'hello@ferratalabs.ai';
}

add_filter( 'wp_mail_from_name', 'ferrata_mail_from_name' );
function ferrata_mail_from_name(): string {
	return 'Ferrata Labs';
}
