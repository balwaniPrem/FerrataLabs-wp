<?php
/**
 * Fallback template. The site is a set of static pages, so this only ever renders if
 * something asks for an archive or the blog index. Front page routes to parts/home.php.
 */

get_header();

if ( is_front_page() ) {
	get_template_part( 'parts/home' );
} else {
	?>
	<header class="phead"><div class="wrap"><h1><?php echo esc_html( wp_get_document_title() ); ?></h1></div></header>
	<section class="sec"><div class="wrap"><div class="prose">
	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			?>
			<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
			<?php the_excerpt(); ?>
			<?php
		}
	} else {
		echo '<p>Nothing here yet.</p>';
	}
	?>
	</div></section>
	<?php
}

get_footer();
