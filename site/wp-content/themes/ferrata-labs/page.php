<?php
/**
 * Every ported page renders through here. The body markup lives in parts/*.php and is
 * chosen by page URI; a page with no matching part falls back to its editor content so
 * new pages can still be added the ordinary WordPress way.
 *
 * The Pledge dashboard is the one page with no marketing chrome - CLAUDE.md §12.
 */

$ferrata_part = ferrata_part_name();
$ferrata_bare = ( 'pledge' === $ferrata_part );

if ( $ferrata_bare ) {
	get_header( 'bare' );
} else {
	get_header();
}

if ( $ferrata_part && locate_template( 'parts/' . $ferrata_part . '.php' ) ) {
	get_template_part( 'parts/' . $ferrata_part );
} else {
	while ( have_posts() ) {
		the_post();
		?>
		<header class="phead"><div class="wrap"><h1><?php the_title(); ?></h1></div></header>
		<section class="sec"><div class="wrap"><div class="prose"><?php the_content(); ?></div></div></section>
		<?php
	}
}

if ( $ferrata_bare ) {
	get_footer( 'bare' );
} else {
	get_footer();
}
