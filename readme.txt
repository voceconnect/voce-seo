=== Voce SEO ===
Contributors: voceplatforms, prettyboymp
Tags: SEO
Requires at least: 3.7.0
Tested up to: 3.8.1
Stable tag: 0.2.6
License: GPLv2 or later

An SEO plugin taking things from both WP SEO and All in One SEO but leaving out the VIP incompatible pieces.

== Description ==

Adds filterable SEO and Social fields to all publicly queryable post types and applies them to the header of the site
to improve discoverability.

== Changelog ==

= Version 0.2.5 =
* Separate filters for og:description and twitter:description
* Add separate field for twitter description
* Change logic for twitter and facebook description to: respective separate meta text field falls back to voce seo generic meta text field to excerpt.

= Version 0.2.4 =
* Refactor og:title and twitter:title to be filterable and use a more appropriate title based on the queried object.

= Version 0.2.2 =
* Fix bug in 'wp_title' filter affecting archive pages.

= Version 0.2.1 =
* Adding metabox to the page post type as well as publicly_queryable post types.
* Had to include page manually since it is not set as publicly queryable by default.

= Version 0.2.0 =
* Adding composer/dependencies

= Version 0.1.1 =
* Changing name of version constants.

= Version 0.1.0 =
* Initial release

== Installation ==

1. Install the Voce Seo plugin folder directly into the `wp-content/plugins/` directory.
1. Activate the plugin via the `Plugins` menu in the WordPress admin.