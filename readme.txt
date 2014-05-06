=== Voce SEO ===
Contributors: voceplatforms, prettyboymp
Tags: SEO
Requires at least: 3.7.0
Tested up to: 3.9
Stable tag: 0.3.3
License: GPLv2 or later

An SEO plugin taking things from both WP SEO and All in One SEO but leaving out the VIP incompatible pieces.

== Description ==

Adds filterable SEO and Social fields to all publicly queryable post types and applies them to the header of the site
to improve discoverability.

== Changelog ==

= Version 0.3.3 =
* Escape output. Sanitize input. Remove `vseo_sanitize_meta_text()` and replace callbacks to it with `sanitize_text_field()`

= Version 0.3.2 =
* Change file permissions to remove execute and write

= Version 0.3.1 =
* Set image to full size for og:image
* Use og:description on homepage


= Version 0.3.0 =
* Adding ability to set separate Facebook, Twitter and SEO title

= Version 0.2.9 =
* Adding ability to set title and description meta for taxonomy terms, set in the taxonomy term admin page

= Version 0.2.8 =
* Fixing issue where tag attributes would not be separated.

= Version 0.2.7 =
* Reworking logic to create meta/link tags; an associative array is generated of all the meta/link tags and is now filterable.
* OG and Twitter tags can now be filtered/disabled.

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
