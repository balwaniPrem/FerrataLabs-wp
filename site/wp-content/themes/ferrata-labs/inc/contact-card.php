<?php
/**
 * The contact card - a PHP port of ContactForm.tsx. Markup, classes and copy are
 * unchanged; the React state machine becomes query-string state, which means the form
 * works with JavaScript disabled exactly as the server action did.
 */

$ferrata_sent  = isset( $_GET['sent'] );
$ferrata_err   = isset( $_GET['err'] ) ? sanitize_key( wp_unslash( $_GET['err'] ) ) : '';
$ferrata_email = 'hello@ferratalabs.ai';

if ( $ferrata_sent ) :
	?>
	<div class="card">
		<div class="form-ok">
			<h4>Got it - thank you.</h4>
			<p>
				We&rsquo;ll come back to you within one business day to find a time. If it&rsquo;s
				urgent, email <a href="mailto:<?php echo esc_attr( $ferrata_email ); ?>"><?php echo esc_html( $ferrata_email ); ?></a>
				and it&rsquo;ll reach the same place.
			</p>
		</div>
	</div>
	<?php
	return;
endif;

$ferrata_field_errs = array(
	'name'  => isset( $_GET['e_name'] ) ? 'Please enter your full name.' : '',
	'email' => isset( $_GET['e_email'] ) ? 'Please enter a valid business email address.' : '',
	'phone' => isset( $_GET['e_phone'] ) ? 'Please enter a phone number we can reach you on.' : '',
);
$ferrata_values      = array(
	'name'  => isset( $_GET['v_name'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['v_name'] ) ) ) : '',
	'email' => isset( $_GET['v_email'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['v_email'] ) ) ) : '',
	'phone' => isset( $_GET['v_phone'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['v_phone'] ) ) ) : '',
);

$ferrata_top_error = '';
if ( 'mail' === $ferrata_err ) {
	$ferrata_top_error = 'We couldn&rsquo;t send that just now. Email ' . $ferrata_email . ' and it will reach the same place.';
} elseif ( 'expired' === $ferrata_err ) {
	$ferrata_top_error = 'That form had been open a while and expired. Please send it again.';
} elseif ( 'fields' === $ferrata_err ) {
	$ferrata_top_error = 'Please check the fields marked below.';
}

/** Echoes an attribute only when there is an error on that field. */
$ferrata_invalid = static function ( string $field ) use ( $ferrata_field_errs ): void {
	if ( $ferrata_field_errs[ $field ] ) {
		printf( ' aria-invalid="true" aria-describedby="err-%s"', esc_attr( $field ) );
	}
};
?>
<div class="card">
	<h3>Request a call</h3>
	<p class="sub">Three fields. We&rsquo;ll come back within one business day with times.</p>

	<?php if ( $ferrata_top_error ) : ?>
		<p class="form-err"><?php echo wp_kses_post( $ferrata_top_error ); ?></p>
	<?php endif; ?>

	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" novalidate>
		<input type="hidden" name="action" value="<?php echo esc_attr( FERRATA_CONTACT_ACTION ); ?>" />
		<?php wp_nonce_field( FERRATA_CONTACT_ACTION, 'ferrata_nonce' ); ?>

		<p class="req-note">Fields marked <span class="req" aria-hidden="true">*</span> are required.</p>

		<div class="field">
			<label for="name">Full name <span class="req" aria-hidden="true">*</span></label>
			<input id="name" name="name" type="text" autocomplete="name" required
				value="<?php echo esc_attr( $ferrata_values['name'] ); ?>"<?php $ferrata_invalid( 'name' ); ?> />
			<?php if ( $ferrata_field_errs['name'] ) : ?>
				<p class="err" id="err-name"><?php echo esc_html( $ferrata_field_errs['name'] ); ?></p>
			<?php endif; ?>
		</div>

		<div class="field">
			<label for="email">Business email <span class="req" aria-hidden="true">*</span></label>
			<input id="email" name="email" type="email" autocomplete="email" required
				value="<?php echo esc_attr( $ferrata_values['email'] ); ?>"<?php $ferrata_invalid( 'email' ); ?> />
			<?php if ( $ferrata_field_errs['email'] ) : ?>
				<p class="err" id="err-email"><?php echo esc_html( $ferrata_field_errs['email'] ); ?></p>
			<?php endif; ?>
		</div>

		<div class="field">
			<label for="phone">Phone number <span class="req" aria-hidden="true">*</span></label>
			<input id="phone" name="phone" type="tel" autocomplete="tel" required
				value="<?php echo esc_attr( $ferrata_values['phone'] ); ?>"<?php $ferrata_invalid( 'phone' ); ?> />
			<?php if ( $ferrata_field_errs['phone'] ) : ?>
				<p class="err" id="err-phone"><?php echo esc_html( $ferrata_field_errs['phone'] ); ?></p>
			<?php endif; ?>
		</div>

		<?php /* honeypot - hidden from people, irresistible to bots */ ?>
		<div class="hp" aria-hidden="true">
			<label for="company_website">Company website</label>
			<input id="company_website" name="company_website" type="text" tabindex="-1" autocomplete="off" />
		</div>

		<button type="submit" class="btn btn-lg" style="width:100%">Request a call</button>

		<p class="form-note">
			We use these details to contact you about your enquiry and nothing else. No list,
			no sequence, no reselling.
		</p>
	</form>

	<ul style="margin-top:22px">
		<li>No deck, no discovery questionnaire</li>
		<li>Straight to the workflow</li>
		<li>Range on cost before you leave the call</li>
	</ul>
	<p class="meta">
		Or email <a href="mailto:<?php echo esc_attr( $ferrata_email ); ?>" style="color:var(--accent)"><?php echo esc_html( $ferrata_email ); ?></a>
	</p>
</div>
