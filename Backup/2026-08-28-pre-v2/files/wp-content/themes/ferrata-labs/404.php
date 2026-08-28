<?php
/**
 * 404 - written in the §7 voice: plain, specific, no apology theatre.
 */

get_header();
?>
<header class="phead">
	<div class="wrap">
		<p class="eyebrow">404</p>
		<h1>That page isn&rsquo;t here.</h1>
		<p>It may have moved, or the link may be wrong. The four places worth going instead:</p>
	</div>
</header>
<section class="sec">
	<div class="wrap">
		<div class="prose">
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">The overview</a> - what we build and who for</li>
				<li><a href="<?php echo esc_url( home_url( '/work/' ) ); ?>">The work</a> - the six agents in detail</li>
				<li><a href="<?php echo esc_url( home_url( '/how-it-works/' ) ); ?>">How it runs</a> - the four steps, at depth</li>
				<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Book a discovery call</a> - thirty minutes, no deck</li>
			</ul>
		</div>
	</div>
</section>
<?php
get_footer();
