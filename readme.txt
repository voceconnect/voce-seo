=== Voce SEO ===
Contributors: voceplatforms, prettyboymp, matstars, kevinlangleyjr, smccafferty
Tags: SEO
Requires at least: 3.7.0
Tested up to: 4.2.4
Stable tag: 0.6.3
License: GPLv2 or later

An SEO plugin taking things from both WP SEO and All in One SEO but leaving out the VIP incompatible pieces.

== Description ==

Adds filterable SEO and Social fields to all publicly queryable post types and applies them to the header of the site to improve discoverability.

== Installation ==

1. Install the Voce Seo plugin folder directly into the `wp-content/plugins/` directory.
1. Activate the plugin via the `Plugins` menu in the WordPress admin.

== Installation ==

=== As standard plugin: ===
> See [Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

=== As theme or plugin dependency: ===
> After dropping the plugin into the containing theme or plugin, add the following:
```php
if( ! class_exists( 'VSEO' ) ) {
    require_once( $path_to_voce_seo . '/voce-seo.php' );
}
```

== Changelog ==
= Version 0.6.3 =
* Removing error control operator

= Version 0.6.2 =
* Removing usage of grunt-voce-plugins in favor of the individually used node modules

= Version 0.6.1 =
* Fixing non-static method deprecated notice

= Version 0.6.0 =
* Adding more flexible support for other single post types
* Adding keywords support.

= Version 0.5.4 =
* Adding `get_vseo_meta` filter

= Version 0.5.3 =
* Adding escaping per VIP

= Version 0.5.2 =
* Strict comparisons
* Escaping
* Meta output validation

= Version 0.5.0 =
* Testing with WordPress 4.2.1
* Adding handling to vseo meta for term splitting

= Version 0.4.0 =
* Testing with WordPress 4.1
* Removing build files
* Updating dependencies

= Version 0.3.9 =
* Only add meta box if post_type_object is valid

= Version 0.3.8 =
* Escape translated strings.

= Version 0.3.7 =
* Don't use `$_REQUEST`, be explicit.

= Version 0.3.6 =
* Add `vseo_twitterimage` filter to filter the twitter:image property.

= Version 0.3.5 =
* No change. Syncing with wordpress.org version bump needed for SVN permissions fix.

= Version 0.3.4 =
* Remove 301 Redirect meta element, which was deprecated

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
