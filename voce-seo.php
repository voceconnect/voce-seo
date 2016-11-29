<?php

/*
  Plugin Name: Voce SEO
  Version: 0.6.3
  Plugin URI: http://voceconnect.com/
  Description: An SEO plugin taking things from both WP SEO and All in One SEO but leaving out the VIP incompatible pieces.
  Author: Voce Platforms
  Author URI: http://voceconnect.com/
 */

class VSEO {

	const DB_VERSION = '0.2';

	public static function init() {

		if ( true === file_exists( dirname(__FILE__) . '/vendor/autoload.php' ) ) {
			include( dirname( __FILE__ ) . '/vendor/autoload.php' );
		}

		self::upgrade_check();

		if( is_admin() ) {
			require_once( __DIR__ . '/admin/admin.php' );
			VSEO_Admin::init();
		}

		Voce_Settings_API::GetInstance()
				->add_page( 'SEO Settings', 'SEO Settings', 'vseo', 'manage_options', '', 'options-general.php' )
				->add_group( 'General', 'vseo-general' )
				->add_setting( 'Add nodp', 'nodp', array(
						'display_callback' => 'vs_display_checkbox',
						'sanitize_callbacks' => array( 'vs_sanitize_checkbox' ),
						'description' => 'Prevents search engines from using the DMOZ description for pages from this site in the search results.'
				) )->group
				->add_setting( 'Add noydir', 'noydir', array(
						'display_callback' => 'vs_display_checkbox',
						'sanitize_callbacks' => array( 'vs_sanitize_checkbox' ),
						'description' => 'Prevents search engines from using the Yahoo! directory description for pages from this site in the search results.'
				) );

		remove_action( 'wp_head', 'rel_canonical' );

		//add namespaces
		add_filter( 'language_attributes', function ( $output ) {
			$prefixes = array(
					'og' => 'http://ogp.me/ns#',
					'fb' => 'http://ogp.me/ns/fb#',
					'article' => 'http://ogp.me/ns/article#'
			);
			$prefixes = apply_filters( 'opengraph_prefixes', $prefixes );

			$prefix_str = '';
			foreach( $prefixes as $k => $v ) {
				$prefix_str .= $k . ': ' . $v . ' ';
			}
			$prefix_str = trim( $prefix_str );

			if( preg_match( '/(prefix\s*=\s*[\"|\'])/i', $output ) ) {
				$output = preg_replace( '/(prefix\s*=\s*[\"|\'])/i', '${1}' . $prefix_str, $output );
			} else {
				$output .= ' prefix="' . $prefix_str . '"';
			}
			return $output;
		} );

		add_filter( 'wp_title', array( __CLASS__, 'seo_title' ), 10, 3 );

		add_action( 'wp_head', array( __CLASS__, 'on_wp_head' ) );
	}

	static function seo_title( $title, $sep, $seplocation ) {
		$new_title = '';
		$t_sep = '%WP_TITILE_SEP%';
		$queried_object = get_queried_object();
		$queried_post_type = get_post_type_object( get_post_type( $queried_object ) );

		if( self::is_singular_viewable_post_query() ) {

			$post_id = get_queried_object_id();
			$vseo_meta = (array) get_post_meta( $post_id, 'vseo_meta', true );

			if( ! empty( $vseo_meta[ 'title' ] ) ) {

				$new_title = apply_filters( 'single_post_title', $vseo_meta[ 'title' ], $queried_object );

			}

		} elseif( is_tax() || is_category() || is_tag() ) {
			$new_title = self::get_term_seo_title();
		}

		if( ! empty( $new_title ) ) {
			$title = $new_title;

			if( ! empty( $title ) ) {
				$prefix = " $sep ";
			}

			// Determines position of the separator and direction of the breadcrumb
			if( 'right' === $seplocation ) { // sep on right, so reverse the order
				$title_array = explode( $t_sep, $title );
				$title_array = array_reverse( $title_array );
				$title = implode( " $sep ", $title_array ) . $prefix;
			} else {
				$title_array = explode( $t_sep, $title );
				$title = $prefix . implode( " $sep ", $title_array );
			}
		}

		return $title;
	}

	private static function get_term_seo_title() {
		$queried_object = get_queried_object();
		$term_id = $queried_object->term_id;
		$taxonomy = $queried_object->taxonomy;
		$option_key = $taxonomy . '_' . $term_id;
		$term_meta = get_option( 'vseo_term_meta' );
		if( ! isset( $term_meta[ $option_key ][ 'title' ] ) ) {
			return;
		}
		$seo_title = $term_meta[ $option_key ][ 'title' ];
		$title_filter = 'single_cat_title';
		if( is_tax() ) {
			$title_filter = 'single_term_filter';
		} elseif( is_tag() ) {
			$title_filter = 'single_tag_filter';
		}
		$new_title = apply_filters( $title_filter, $seo_title );
		return $new_title;
	}

	private static function upgrade_check() {
		$db_version = get_option( 'VSEO_Version', '0.0' );
		if( $db_version < 0.1 ) {
			Voce_Settings_API::GetInstance()->set_setting( 'robots-nodp', 'vseo-general', true );
			Voce_Settings_API::GetInstance()->set_setting( 'robots-noydir', 'vseo-general', true );
		}

		if( $db_version < 0.2 && function_exists( 'wp_get_split_term' ) ) {
			$term_meta = $updated_term_meta = get_option( 'vseo_term_meta' );
			if( is_array( $term_meta ) ) {
				foreach( $term_meta as $taxonomy_term => $term_data ) {
					$tax_term_arr = explode( '_', $taxonomy_term );
					if( is_array( $tax_term_arr ) && ! empty( $tax_term_arr ) ) {
						$term_id = array_pop( $tax_term_arr );
						$taxonomy = implode( '_', $tax_term_arr );
						$new_term_id = wp_get_split_term( $term_id, $taxonomy );
						if( $new_term_id !== false ) {
							unset( $updated_term_meta[ $taxonomy_term ] );
							$updated_term_meta[ sprintf( '%s_%s', $taxonomy, $new_term_id ) ] = $term_data;
						}
					}
				}
				update_option( 'vseo_term_meta', $updated_term_meta );
			}
		}

		update_option( 'VSEO_Version', self::DB_VERSION );
	}

	public static function generate_facebook_seo_data( $meta_objects = array() ) {
		if( $canonical = self::get_canonical_url() ) {
			$meta_objects = self::create_meta_object( 'og:url', 'meta', array( 'property' => 'og:url', 'content' => esc_url( $canonical ) ), $meta_objects );
		}

		$meta_objects = self::create_meta_object( 'og:site_name', 'meta', array( 'property' => 'og:site_name', 'content' => esc_attr( get_bloginfo( 'name' ) ) ), $meta_objects );
		$meta_objects = self::create_meta_object( 'og:locale', 'meta', array( 'property' => 'og:locale', 'content' => 'en_us' ), $meta_objects );

		$description = self::get_meta_description();
		$queried_object = get_queried_object();
		if( isset( $queried_object->post_type ) || ( is_tax() || is_category() || is_tag() ) || is_home() || is_front_page() ) {
			$og_description = self::get_seo_meta( 'og_description', get_queried_object_id() );
			if( ! $og_description ) {
				$og_description = $description;
			}
			if( $og_description ) {
				$meta_objects = self::create_meta_object( 'og:description', 'meta', array( 'property' => 'og:description', 'content' => esc_attr( $og_description ) ), $meta_objects );
			}
		}
		$meta_objects = self::create_meta_object( 'og:title', 'meta', array( 'property' => 'og:title', 'content' => esc_attr( self::get_social_title( 'og_title' ) ) ), $meta_objects );
		$meta_objects = self::create_meta_object( 'og:type', 'meta', array( 'property' => 'og:type', 'content' => apply_filters( 'vseo_ogtype', 'article' ) ), $meta_objects );

		if( $image = self::get_meta_image() ) {
			$meta_objects = self::create_meta_object( 'og:image', 'meta', array( 'property' => 'og:image', 'content' => esc_attr( $image ) ), $meta_objects );
		}

		return $meta_objects;
	}

	public static function generate_twitter_seo_data( $meta_objects = array() ) {
		if( $canonical = self::get_canonical_url() ) {
			$meta_objects = self::create_meta_object( 'twitter:url', 'meta', array( 'name' => 'twitter:url', 'content' => esc_url( $canonical ) ), $meta_objects );
		}

		$description = self::get_meta_description();
		$queried_object = get_queried_object();
		if( isset( $queried_object->post_type ) || ( is_tax() || is_category() || is_tag() ) ) {
			$twitter_description = self::get_seo_meta( 'twitter_description', get_queried_object_id() );
			if( ! $twitter_description ) {
				$twitter_description = $description;
			}
			$meta_objects = self::create_meta_object( 'twitter:description', 'meta', array( 'name' => 'twitter:description', 'content' => esc_attr( $twitter_description ) ), $meta_objects );
		}

		$meta_objects = self::create_meta_object( 'twitter:title', 'meta', array( 'name' => 'twitter:title', 'content' => esc_attr( self::get_social_title( 'twitter_title' ) ) ), $meta_objects );

		$meta_objects = self::create_meta_object( 'twitter:card', 'meta', array( 'name' => 'twitter:card', 'content' => esc_attr( apply_filters( 'vseo_twittercard', 'summary' ) ) ), $meta_objects );

		if( $image = self::get_meta_image() ) {
			$meta_objects = self::create_meta_object( 'twitter:image', 'meta', array( 'name' => 'twitter:image', 'content' => esc_attr( apply_filters( 'vseo_twitterimage', $image ) ) ), $meta_objects );
		}

		return $meta_objects;
	}

	private static function create_meta_object( $key, $type, $attributes = array(), $meta_objects = array() ) {
		$meta_objects[ $key ] = array(
				'type' => $type,
				'attributes' => $attributes
		);

		return $meta_objects;
	}

	public static function output_meta_objects_html( $meta_objects = array() ) {
		$html = '';
		$allowed_tags = array(
				'meta' => array(
						'charset' => true,
						'content' => true,
						'http-equiv' => true,
						'name' => true,
						'property' => true
				),
				'link' => array(
						'href' => true,
						'rel' => true,
						'media' => true,
						'hreflang' => true,
						'type' => true,
						'sizes' => true
				),
		);

		foreach( $meta_objects as $meta_object => $properties ) {
			$atts_string = '';
			$element = ! empty( $properties[ 'type' ] ) ? $properties[ 'type' ] : false;
			$attributes = ! empty( $properties[ 'attributes' ] ) ? $properties[ 'attributes' ] : false;

			if( $element && $attributes && is_array( $attributes ) ) {

				foreach( $attributes as $att => $value ) {
					$atts_string .= sprintf( '%s="%s" ', $att, $value );
				}

				$html .= sprintf( '<%s %s/>' . PHP_EOL, $element, $atts_string );
			}
		}

		echo wp_kses( $html, $allowed_tags );
	}

	public static function on_wp_head() {
		$meta_objects = array();

		do_action( 'voce_seo_before_wp_head' );

		echo "<!-- voce_seo -->\n";

		$meta_objects = self::robots_meta();

		if( $canonical = self::get_canonical_url() ) {
			$meta_objects = self::create_meta_object( 'canonical', 'link', array( 'rel' => 'canonical', 'href' => esc_url( $canonical ) ), $meta_objects );
		}

		if( $description = self::get_meta_description() ) {
			$meta_objects = self::create_meta_object( 'description', 'meta', array( 'name' => 'description', 'content' => esc_attr( $description ) ), $meta_objects );
		}

		if( $keywords = self::get_meta_keywords() ) {
			$meta_objects = self::create_meta_object( 'keywords', 'meta', array( 'name' => 'keywords', 'content' => esc_attr( $keywords ) ), $meta_objects );
		}

		if( apply_filters( 'vseo_use_facebook_meta', true ) ) {
			$meta_objects = self::generate_facebook_seo_data( $meta_objects );
		}

		if( apply_filters( 'vseo_use_twitter_meta', true ) ) {
			$meta_objects = self::generate_twitter_seo_data( $meta_objects );
		}

		$meta_objects = apply_filters( 'vseo_meta_objects', $meta_objects );

		self::output_meta_objects_html( $meta_objects );

		echo '<!-- end voce_seo -->' . "\n";

		do_action( 'voce_seo_after_wp_head' );
	}

	public static function get_social_title( $meta_key ) {
		$title = '';
		if( is_home() || is_front_page() ) {
			$title = get_bloginfo( 'name' );
		} else {
			if( is_author() ) {
				$author = get_queried_object();
				$title = $author->display_name;
			} else {
				if( self::is_singular_viewable_post_query() ) {
					$post = get_queried_object();
					$title = self::get_seo_meta( $meta_key, $post->ID );
					if( ! $title ) {
						$vseo_meta = (array) get_post_meta( $post->ID, 'vseo_meta', true );;
						if( array_key_exists( 'title', $vseo_meta ) ) {
							$title = $vseo_meta[ 'title' ];
						}
						if( ! $title ) {
							$title = empty( $post->post_title ) ? ' ' : wp_kses( $post->post_title, array() );
						}
					}
				} else {
					if( is_tax() || is_category() || is_tag() ) {
						$title = self::get_term_seo_title();
					} else {
						$title = '';
					}
				}
			}
		}

		return apply_filters( 'vseo_ogtitle', $title );
	}

	public static function get_seo_meta( $key, $post_id = 0 ) {
		if( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$vseo_meta = (array) get_post_meta( $post_id, 'vseo_meta', true );

		return isset( $vseo_meta[ $key ] ) ? $vseo_meta[ $key ] : null;
	}

	public static function get_meta_description() {
		if( get_query_var( 'paged' ) && get_query_var( 'paged' ) > 1 ) {
			return '';
		}

		$description = '';

		$queried_object = get_queried_object();

		if( isset( $queried_object->post_type ) ) {
			if( ! ( $description = self::get_seo_meta( 'description', get_queried_object_id() ) ) ) {
				//get description from excerpt/content
				$description = empty( $queried_object->post_excerpt ) ? $queried_object->post_content : $queried_object->post_excerpt;
				$description = str_replace( ']]>', ']]&gt;', $description );
				$description = preg_replace( '|\[(.+?)\](.+?\[/\\1\])?|s', '', $description );
				$description = strip_tags( $description );
				$max = 250;
				if( $max < strlen( $description ) ) {
					while( $description[ $max ] !== ' ' && $max > 40 ) {
						$max--;
					}
				}
				$description = substr( $description, 0, $max );
				$description = trim( stripcslashes( $description ) );

				$description = preg_replace( "/\s\s+/u", " ", $description );
			}
		} else {
			if( is_tax() || is_category() || is_tag() ) {
				$queried_object = get_queried_object();
				$term_id = $queried_object->term_id;
				$taxonomy = $queried_object->taxonomy;
				$description = term_description( $term_id, $taxonomy );

			} elseif( is_search() ) {
				$description = '';
			} else {
				$description = get_bloginfo( 'description', 'display' );
			}
		}

		$description = apply_filters( 'seo_meta_description', trim( $description ) );

		return strip_tags( stripslashes( $description ) );
	}

	public static function get_meta_keywords() {
		$keywords = '';
		if( self::is_singular_viewable_post_query() ) {
			$keywords = self::get_seo_meta( 'keywords', get_queried_object_id() );
		}
		return apply_filters( 'seo_meta_keywords', trim( $keywords ) );
	}

	private static function robots_meta() {
		global $wp_query;

		$queried_object = get_queried_object();

		$robots_defaults = array(
				'index' => 'index',
				'follow' => 'follow',
				'other' => array(),
		);

		//use this to replace the defaults, these values will be overwritten by post meta if set
		$robots = apply_filters( 'vseo_robots_defaults', $robots_defaults );

		if( isset( $queried_object->post_type ) ) {
			if( $follow = self::get_seo_meta( 'robots-nofollow', get_queried_object_id() ) ) {
				$robots[ 'follow' ] = $follow;
			}
			if( $index = self::get_seo_meta( 'robots-noindex', get_queried_object_id() ) ) {
				$robots[ 'index' ] = $index;
			}
		} else {
			if( is_search() || is_archive() ) {
				$robots[ 'index' ] = 'noindex';
			}
		}

		foreach( array( 'nodp', 'noydir' ) as $robot ) {
			if( Voce_Settings_API::GetInstance()->get_setting( $robot, 'vseo-general' ) ) {
				$robots[ 'other' ][] = $robot;
			}
		}

		//final filter to force values
		$robots = apply_filters( 'vseo_robots', $robots );
		$robots = array_intersect_key( $robots, $robots_defaults );

		if( isset( $robots[ 'other' ] ) && is_array( $robots[ 'other' ] ) ) {
			$other = array_unique( $robots[ 'other' ] );
			unset( $robots[ 'other' ] );
			$robots = array_merge( $robots, $other );
		}

		$robotsstr = implode( ',', $robots );

		if( $robotsstr !== '' ) {
			return self::create_meta_object( 'robots', 'meta', array( 'name' => 'robots', 'content' => esc_attr( $robotsstr ) ) );
		} else {
			return array();
		}
	}

	private static function get_canonical_url() {
		global $wp_rewrite;
		$queried_object = get_queried_object();

		$canonical = '';

		if( isset( $queried_object->post_type ) ) {
			if( ! ( $canonical = self::get_seo_meta( 'canonical', get_queried_object_id() ) ) || is_null( $canonical ) ) {
				$canonical = get_permalink( $queried_object->ID );
				// Fix paginated pages
				if( get_query_var( 'page' ) > 1 ) {
					$canonical = user_trailingslashit( trailingslashit( $canonical ) . get_query_var( 'page' ) );
				}
			}
		} else {
			if( is_search() ) {
				$canonical = get_search_link();
			} else {
				if( is_front_page() ) {
					$canonical = home_url( '/' );
				} else {
					if( is_home() && 'page' === get_option( 'show_on_front' ) ) {
						$canonical = get_permalink( get_option( 'page_for_posts' ) );
					} else {
						if( is_tax() || is_tag() || is_category() ) {
							if( function_exists( 'wpcom_vip_get_term_link' ) ) {
								$canonical = wpcom_vip_get_term_link( $queried_object, $queried_object->taxonomy );
							} else {
								$canonical = get_term_link( $queried_object, $queried_object->taxonomy );
							}
						} else {
							if( function_exists( 'get_post_type_archive_link' ) && is_post_type_archive() ) {
								$canonical = get_post_type_archive_link( get_post_type() );
							} else {
								if( is_author() ) {
									$canonical = get_author_posts_url( get_query_var( 'author' ), get_query_var( 'author_name' ) );
								} else {
									if( is_archive() ) {
										if( is_date() ) {
											if( is_day() ) {
												$canonical = get_day_link( get_query_var( 'year' ), get_query_var( 'monthnum' ), get_query_var( 'day' ) );
											} else {
												if( is_month() ) {
													$canonical = get_month_link( get_query_var( 'year' ), get_query_var( 'monthnum' ) );
												} else {
													if( is_year() ) {
														$canonical = get_year_link( get_query_var( 'year' ) );
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}

			if( $canonical && get_query_var( 'paged' ) > 1 ) {
				$canonical = user_trailingslashit( trailingslashit( $canonical ) . trailingslashit( $wp_rewrite->pagination_base ) . get_query_var( 'paged' ) );
			}
		}

		return apply_filters( 'vseo_canonical_url', $canonical );

	}

	private static function get_meta_image() {
		$queried_object = get_queried_object();

		$img = '';

		if( isset( $queried_object->post_type ) ) {
			if( has_post_thumbnail( get_queried_object_id() ) ) {
				$img = wp_get_attachment_image_src( get_post_thumbnail_id( get_queried_object_id() ), apply_filters( 'vseo_image_size', 'full' ) );
				if( ! empty( $img[ 0 ] ) ) {
					$img = $img[ 0 ];
				}
			}
		}

		return apply_filters( 'vseo_meta_image', $img );
	}

	private static function is_singular_viewable_post_query() {
		$queried_object = get_queried_object();
		if( is_a( $queried_object, 'WP_Post' ) ) {
			$post_type_object = get_post_type_object( get_post_type( $queried_object ) );
			return $post_type_object->publicly_queryable || ( $post_type_object->_builtin && $post_type_object->public );
		}
		return false;
	}
}

add_action( 'init', array( 'VSEO', 'init' ) );
