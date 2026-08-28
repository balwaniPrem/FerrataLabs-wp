<?php
/**
 * Contact form delivery - a PHP port of app/(marketing)/contact/actions.ts.
 *
 * Same three fields, same validation (including the business-email rule and the
 * honeypot), same promise: it never tells someone we received their details when
 * nothing was sent. Delivery is wp_mail(); on 10Web that is the server's mailer unless
 * an SMTP plugin overrides it, so verify a real submission arrives before launch.
 *
 * Where mail cannot be sent, the submission is still written to the database as a
 * private ferrata_lead post, so nothing is lost while email is being sorted out.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const FERRATA_CONTACT_ACTION = 'ferrata_contact';

/** Free-domain list, deliberately short - catch the obvious, don't police the world. */
function ferrata_free_domains(): array {
	return array(
		'gmail.com',
		'googlemail.com',
		'yahoo.com',
		'yahoo.co.uk',
		'outlook.com',
		'hotmail.com',
		'hotmail.co.uk',
		'live.com',
		'msn.com',
		'aol.com',
		'icloud.com',
		'me.com',
		'mail.com',
		'gmx.com',
		'proton.me',
		'protonmail.com',
		'yandex.com',
		'zoho.com',
	);
}

/** Where submissions land. Override with the FERRATA_CONTACT_TO constant in wp-config.php. */
function ferrata_contact_to(): string {
	if ( defined( 'FERRATA_CONTACT_TO' ) && FERRATA_CONTACT_TO ) {
		return (string) FERRATA_CONTACT_TO;
	}
	return 'hello@ferratalabs.ai';
}

function ferrata_validate( string $name, string $email, string $phone ): array {
	$errors = array();

	$len = mb_strlen( $name );
	if ( $len < 2 ) {
		$errors['name'] = 'Please enter your full name.';
	} elseif ( $len > 120 ) {
		$errors['name'] = 'That name is too long.';
	}

	if ( ! is_email( $email ) ) {
		$errors['email'] = 'Please enter a valid email address.';
	} else {
		$domain = strtolower( (string) substr( strrchr( $email, '@' ) ?: '', 1 ) );
		if ( in_array( $domain, ferrata_free_domains(), true ) ) {
			$errors['email'] = 'Please use your work email address.';
		}
	}

	$digits = preg_replace( '/[^\d]/', '', $phone );
	if ( strlen( (string) $digits ) < 7 || strlen( (string) $digits ) > 15 ) {
		$errors['phone'] = 'Please enter a phone number we can reach you on.';
	}

	return $errors;
}

add_action( 'admin_post_nopriv_' . FERRATA_CONTACT_ACTION, 'ferrata_handle_contact' );
add_action( 'admin_post_' . FERRATA_CONTACT_ACTION, 'ferrata_handle_contact' );
function ferrata_handle_contact(): void {
	$back = home_url( '/contact/' );

	if ( ! isset( $_POST['ferrata_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ferrata_nonce'] ) ), FERRATA_CONTACT_ACTION ) ) {
		wp_safe_redirect( add_query_arg( 'err', 'expired', $back ) );
		exit;
	}

	// Honeypot: hidden from people, irresistible to bots. Silently accept and drop.
	if ( ! empty( $_POST['company_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'sent', '1', $back ) );
		exit;
	}

	$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );

	$errors = ferrata_validate( $name, $email, $phone );
	if ( $errors ) {
		$args = array( 'err' => 'fields' );
		foreach ( array_keys( $errors ) as $field ) {
			$args[ 'e_' . $field ] = '1';
		}
		$args['v_name']  = rawurlencode( $name );
		$args['v_email'] = rawurlencode( $email );
		$args['v_phone'] = rawurlencode( $phone );
		wp_safe_redirect( add_query_arg( $args, $back ) );
		exit;
	}

	// Store first. Email can fail; a lead should not be lost because of it.
	$lead_id = wp_insert_post(
		array(
			'post_type'    => 'ferrata_lead',
			'post_status'  => 'private',
			'post_title'   => $name . ' - ' . $email,
			'post_content' => "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\nWhen: " . current_time( 'mysql' ),
		)
	);

	$body = implode(
		"\n",
		array(
			'New discovery call request - ' . home_url( '/' ),
			'',
			'Name:  ' . $name,
			'Email: ' . $email,
			'Phone: ' . $phone,
		)
	);

	$sent = wp_mail(
		ferrata_contact_to(),
		'Discovery call request - ' . $name,
		$body,
		array( 'Reply-To: ' . $name . ' <' . $email . '>' )
	);

	$stored = $lead_id && ! is_wp_error( $lead_id );

	// A mailer outage is an operational problem, not the visitor's. Flag it on the
	// lead so it is obvious in the admin list rather than failing silently.
	if ( $stored && ! $sent ) {
		wp_update_post(
			array(
				'ID'         => $lead_id,
				'post_title' => $name . ' - ' . $email . ' [not emailed]',
			)
		);
	}

	// The enquiry is recorded either way, so a stored lead is genuine receipt and
	// the visitor is told so. Only losing both channels reports a failure.
	// A distinct URL, so a conversion is a pageview rather than a query string the
	// analytics goal has to sniff. Failure stays on the form with its error state.
	if ( $sent || $stored ) {
		wp_safe_redirect( home_url( '/thank-you/' ) );
		exit;
	}

	wp_safe_redirect( add_query_arg( array( 'err' => 'mail' ), $back ) );
	exit;
}

/** Leads are visible in the admin so a failed email is never a lost lead. */
add_action( 'init', 'ferrata_register_lead_type' );
function ferrata_register_lead_type(): void {
	register_post_type(
		'ferrata_lead',
		array(
			'labels'          => array(
				'name'          => 'Leads',
				'singular_name' => 'Lead',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-email-alt',
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		)
	);
}
