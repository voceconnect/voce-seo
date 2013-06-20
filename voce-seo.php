<?php

/**
 * An SEO plugin taking things from both WP SEO and All in One SEO
 * but leaving out the VIP incompatible pieces.
 * @author Michael Pretty (prettyboymp)
 * @version 0.1
 */

class VSEO {
	
	const VERSION = '0.1';
	
	public static function init() {
		
		voce_load_plugin('voce-settings-api');
		
		self::upgrade_check();
		
		if(  is_admin() ) {
			require_once (__DIR__ . '/admin/admin.php');
			VSEO_Admin::init();
		}
		
		Voce_Settings_API::GetInstance()
			->add_page('SEO Settings', 'SEO Settings', 'vseo', 'manage_options', '', 'options-general.php')
				->add_group('General', 'vseo-general')
					->add_setting('Add nodp', 'nodp', array(
						'display_callback' => 'vs_display_checkbox',
						'sanitize_callbacks' => array('vs_sanitize_checkbox'),
						'description' => 'Prevents search engines from using the DMOZ description for pages from this site in the search results.'
						))->group
					->add_setting('Add noydir', 'noydir', array(
						'display_callback' => 'vs_display_checkbox',
						'sanitize_callbacks' => array('vs_sanitize_checkbox'),
						'description' => 'Prevents search engines from using the Yahoo! directory description for pages from this site in the search results.'
						));
		
		remove_action( 'wp_head', 'rel_canonical' );
		
		//add namespaces
		add_filter( 'language_attributes', function($output ) {
				$prefixes = array(
					'og' => 'http://ogp.me/ns#',
					'fb' => 'http://ogp.me/ns/fb#',
					'article' => 'http://ogp.me/ns/article#'
				);
				$prefixes = apply_filters( 'opengraph_prefixes', $prefixes );

				$prefix_str = '';
				foreach ( $prefixes as $k => $v ) {
					$prefix_str .= $k . ': ' . $v . ' ';
				}
				$prefix_str = trim( $prefix_str );

				if ( preg_match( '/(prefix\s*=\s*[\"|\'])/i', $output ) ) {
					$output = preg_replace( '/(prefix\s*=\s*[\"|\'])/i', '${1}' . $prefix_str, $output );
				} else {
					$output .= ' prefix="' . $prefix_str . '"';
				}
				return $output;
			} );
		
		add_filter( 'wp_title', array( __CLASS__, 'seo_title' ), 10, 3);

		add_action('wp_head', array(__CLASS__, 'on_wp_head'));
	}

	static function seo_title($title, $sep, $seplocation) {
		$new_title = '';
		$t_sep = '%WP_TITILE_SEP%';
		$queried_object = get_queried_object();

		if($queried_object && get_post_type($queried_object)) {
			$post_id = get_queried_object_id();
			$vseo_meta = (array) get_post_meta($post_id, 'vseo_meta', true);

			$post_title = empty($vseo_meta['title']) ? get_the_title($post_id) : $vseo_meta['title'];

			$new_title = apply_filters('single_post_title', $post_title, $queried_object);
		}
		if ( !empty( $new_title ) ) {
			$title = $new_title;

			if ( !empty( $title ) )
				$prefix = " $sep ";

			// Determines position of the separator and direction of the breadcrumb
			if ( 'right' == $seplocation ) { // sep on right, so reverse the order
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
	
	private static function upgrade_check() {
		$current_version = get_option('VSEO_Version', '0.0');
		if($current_version < 0.1) {
			Voce_Settings_API::GetInstance()->set_setting('robots-nodp', 'vseo-general', true);
			Voce_Settings_API::GetInstance()->set_setting('robots-noydir', 'vseo-general', true);
		}
		
		update_option('VSEO_Version', self::VERSION);
	}
	
	public static function on_wp_head() {
		$queried_object = get_queried_object();

		do_action( 'voce_seo_before_wp_head' );

		echo "<!-- voce_seo -->\n";
		if($canonical = self::get_canonical_url()) {
			printf('<link rel="canonical" href="%s" />'.chr(10), esc_url($canonical));
			printf('<meta name="twitter:url" content="%s" />'.chr(10), esc_attr($canonical));
			printf('<meta property="og:url" content="%s" />'.chr(10), esc_attr($canonical));
		}
		
		printf('<meta property="og:site_name" content="%s"/>', esc_attr(get_bloginfo( 'name' )));
		echo '<meta property="og:locale" content="en_us"/>';

		self::robots_meta();
		
		if($description = self::get_meta_description() ) {
			printf('<meta name="description" content="%s" />'.chr(10), esc_attr($description));
		}
		
		if ( isset( $queried_object->post_type ) ) {
			if ( $og_description = self::get_seo_meta( 'og_description', get_queried_object_id() ) ) {
				printf( '<meta property="og:description" content="%s" />' . chr( 10 ), $og_description );
				printf('<meta name="twitter:description" content="%s" />'.chr(10), esc_attr($og_description));
			}
			
		}

		remove_filter( 'wp_title', array( __CLASS__, 'seo_title' ), 10, 3);
		printf('<meta property="og:title" content="%s" />'.chr(10), esc_attr(trim(wp_title('', false))));
		printf('<meta name="twitter:title" content="%s" />'.chr(10), esc_attr(trim(wp_title('', false))));
		add_filter( 'wp_title', array( __CLASS__, 'seo_title' ), 10, 3);

		printf('<meta property="og:type" content="%s"/>'.chr(10), apply_filters('vseo_ogtype', 'article'));
		printf('<meta name="twitter:card" content="%s" />'.chr(10), apply_filters('vseo_ogtype', 'summary'));

		
		if($image = self::get_meta_image()) {
			printf('<meta name="twitter:image" content="%s" />'.chr(10), esc_attr($image));
			printf('<meta property="og:image" content="%s" />'.chr(10), esc_attr($image));
		}
		echo '<!-- end voce_seo -->';

		do_action( 'voce_seo_after_wp_head' );
	}
	
	public static function get_seo_meta($key, $post_id = 0) {
		if(!$post_id) $post_id = get_the_ID ( );
		
		$vseo_meta = (array) get_post_meta($post_id, 'vseo_meta', true);
		
		return isset($vseo_meta[$key]) ? $vseo_meta[$key] : null;
	}
	
	public static function get_meta_description() {
		if ( get_query_var( 'paged' ) && get_query_var( 'paged' ) > 1 )
			return '';
		
		$description = '';

		$queried_object = get_queried_object();
		
		if ( isset($queried_object->post_type) ) {
			if(!($description = self::get_seo_meta( 'description', get_queried_object_id()))) {
				//get description from excerpt/content
				$description = empty($queried_object->post_excerpt) ? $queried_object->post_content : $queried_object->post_excerpt;
				$description = str_replace(']]>', ']]&gt;', $description);
				$description = preg_replace( '|\[(.+?)\](.+?\[/\\1\])?|s', '', $description );
				$description = strip_tags($description);
				$max = 250;
				if ($max < strlen($description)) {
					while($description[$max] != ' ' && $max > 40) {
						$max--;
					}
				}
				$description = substr($description, 0, $max);
				$description = trim(stripcslashes($description));

				$description = preg_replace( "/\s\s+/u", " ", $description );
			}
		} else {
			if ( is_search() ) {
				$description = '';
			} else {
				$description = get_bloginfo( 'description', 'display' );
			}
		}

		$description = apply_filters( 'seo_meta_description', trim( $description ) );

		return strip_tags( stripslashes( $description ) );
	}
	
	private static function robots_meta() {
		global $wp_query;

		$queried_object = get_queried_object();
		
		$robots_defaults = array(
			'index'  => 'index',
			'follow' => 'follow',
			'other' => array(),
		);
		
		//use this to replace the defaults, these values will be overwritten by post meta if set
		$robots = apply_filters('vseo_robots_defaults', $robots_defaults);

		if ( isset($queried_object->post_type) ) {
			if ( $follow = self::get_seo_meta('robots-nofollow', get_queried_object_id()) ) {
				$robots['follow'] = $follow;
			}
			if ( $index = self::get_seo_meta('robots-noindex', get_queried_object_id()) ) {
				$robots['index'] = $index;
			}
		} else {
			if ( is_search() || is_archive() ) {
				$robots['index'] = 'noindex';
			} 
		}

		foreach ( array( 'nodp', 'noydir' ) as $robot ) {
			if ( Voce_Settings_API::GetInstance()->get_setting( $robot, 'vseo-general' ) ) {
				$robots['other'][] = $robot;
			}
		}

		//final filter to force values
		$robots = apply_filters('vseo_robots', $robots);
		$robots = array_intersect_key($robots, $robots_defaults);

		if ( isset($robots['other']) && is_array($robots['other']) ) {
			$other = array_unique( $robots['other'] );
			unset( $robots['other'] );
			$robots = array_merge($robots, $other);
		}

		$robotsstr = implode(',', $robots );

		if ( $robotsstr != '' ) {
			echo '<meta name="robots" content="' . esc_attr( $robotsstr ) . '"/>' . "\n";
		}
	}
	
	private static function get_canonical_url() {
		global $wp_rewrite;
		$queried_object = get_queried_object();
		
		$canonical = '';
		
		if ( isset($queried_object->post_type) ) {
			if ( !($canonical = self::get_seo_meta( 'canonical', get_queried_object_id() ) ) || is_null( $canonical ) ) {
				$canonical = get_permalink( $queried_object->ID );
				// Fix paginated pages
				if ( get_query_var( 'page' ) > 1 ) {
					$canonical = user_trailingslashit( trailingslashit( $canonical ) . get_query_var( 'page' ) );
				}
			}
		} else {
			if ( is_search() ) {
				$canonical = get_search_link();
			} else if ( is_front_page() ) {
				$canonical = home_url( '/' );
			} else if ( is_home() && 'page' == get_option( 'show_on_front' ) ) {
				$canonical = get_permalink( get_option( 'page_for_posts' ) );
			} else if ( is_tax() || is_tag() || is_category() ) {
				$canonical = get_term_link( $queried_object, $queried_object->taxonomy );
			} else if ( function_exists( 'get_post_type_archive_link' ) && is_post_type_archive() ) {
				$canonical = get_post_type_archive_link( get_post_type() );
			} else if ( is_author() ) {
				$canonical = get_author_posts_url( get_query_var( 'author' ), get_query_var( 'author_name' ) );
			} else if ( is_archive() ) {
				if ( is_date() ) {
					if ( is_day() ) {
						$canonical = get_day_link( get_query_var( 'year' ), get_query_var( 'monthnum' ), get_query_var( 'day' ) );
					} else if ( is_month() ) {
						$canonical = get_month_link( get_query_var( 'year' ), get_query_var( 'monthnum' ) );
					} else if ( is_year() ) {
						$canonical = get_year_link( get_query_var( 'year' ) );
					}
				}
			}

			if ( $canonical && get_query_var( 'paged' ) > 1 ) {
				$canonical = user_trailingslashit( trailingslashit( $canonical ) . trailingslashit( $wp_rewrite->pagination_base ) . get_query_var( 'paged' ) );
			}
		}

		return apply_filters( 'vseo_canonical_url', $canonical );

	}
	
	private static function get_meta_image() {
		$queried_object = get_queried_object();
		
		$img = '';
		
		if ( isset($queried_object->post_type) ) {
			if(  has_post_thumbnail( get_queried_object_id() )) {
				$img = wp_get_attachment_image_src(get_post_thumbnail_id( get_queried_object_id(), 'medium'));
				$img = $img[0];
			}
		}
		
		return apply_filters('vseo_meta_image', $img);
	}
}
add_action('init', array('VSEO', 'init'));