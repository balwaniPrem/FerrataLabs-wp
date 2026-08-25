<?php
/**
 * Product shell - no marketing chrome, no rail, no nav. CLAUDE.md §12.
 * noindex is emitted by ferrata_noindex() in functions.php.
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="h-full">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<?php wp_head(); ?>
</head>
<body <?php body_class( 'min-h-full flex flex-col app-shell' ); ?>>
