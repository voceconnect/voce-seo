<?php

class VSEO_Admin {

	public static function init() {
		require_once( __DIR__ . '/field-callbacks.php' );
		VSEO_Metabox::init();
		VSEO_Taxonomy::init();
	}

}

class VSEO_Metabox {

	public static function init() {
		add_action( 'add_meta_boxes', function ( $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			if( $post_type_object && $post_type_object->publicly_queryable || $post_type === 'page' ) {
				add_meta_box( 'vseo_meta', 'SEO Settings', array( 'VSEO_Metabox', 'meta_box' ), $post_type, 'advanced' );
			}
		}, 99 );

		add_action( 'save_post', array( __CLASS__, 'on_save_post' ) );
	}

	public static function meta_box( $post ) {
		$post_type = get_post_type( $post );
		$tabs = self::get_metabox_tabs( $post_type );
		$vseo_meta = ( array ) get_post_meta( $post->ID, 'vseo_meta', true );
		?>
		<div class="vseo-metabox-tabs-div">
			<ul class="vseo-metabox-tabs" id="vseo-metabox-tabs">
				<?php
				foreach( $tabs as $tab_id => $tab ) {
					sprintf( '<li class="vseo-%1$s"><a class="vseo_tablink" href="#vseo_%1$s">%2$s</a></li>', esc_attr( $tab_id ), esc_html( $tab[ 'label' ] ) );
				}
				?>
			</ul>
			<?php foreach( $tabs as $tab_id => $tab ) : ?>
				<div class="vseotab" id="vseo-<?php echo esc_attr( $tab_id ); ?>">
					<?php foreach( self::get_metabox_fields( $tab_id, $post_type ) as $field_id => $field ): ?>
						<p>
							<label><?php echo esc_html( $field[ 'title' ] ); ?></label>
							<?php
							echo call_user_func_array( $field[ 'display_callback' ], array(
											'vseo' . $field_id,
											isset( $vseo_meta[ $field_id ] ) ? $vseo_meta[ $field_id ] : null,
											isset( $field[ 'args' ] ) ? $field[ 'args' ] : array() )
							);
							?>
						</p>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		wp_nonce_field( 'vseo_update_meta', 'vseo_nonce' );
	}

	public static function on_save_post( $post_id ) {
		if( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return $post_id;
		}
		if( isset( $_POST[ 'vseo_nonce' ] ) && wp_verify_nonce( $_POST[ 'vseo_nonce' ], 'vseo_update_meta' ) ) {
			$post_type = get_post_type( $post_id );
			$tabs = self::get_metabox_tabs( $post_type );
			$vseo_meta = ( array ) get_post_meta( $post_id, 'vseo_meta', true );

			foreach( $tabs as $tab_id => $tab ) {
				foreach( self::get_metabox_fields( $tab_id, $post_type ) as $field_id => $field ) {
					if( isset( $field[ 'sanitize_callback' ] ) ) {
						$vseo_meta[ $field_id ] = call_user_func_array( $field[ 'sanitize_callback' ], array(
								isset( $_POST[ 'vseo' . $field_id ] ) ? $_POST[ 'vseo' . $field_id ] : null,
								$field[ 'args' ]
						) );
					}
				}
			}
			update_post_meta( $post_id, 'vseo_meta', $vseo_meta );
		}
	}

	private static function get_metabox_tabs( $post_type ) {
		$metabox_tabs = array(
				'general' => array(
						'label' => 'General'
				),
				'advanced' => array(
						'label' => 'Advanced'
				),
				'social' => array(
						'label' => 'Social'
				)
		);
		return apply_filters( 'vseo_metabox_tabs', $metabox_tabs, $post_type );
	}

	private static function get_metabox_fields( $tab, $post_type ) {
		$tab_fields = array(
				'general' => array(
						'title' => array(
								'display_callback' => 'vseo_field_text',
								'sanitize_callback' => 'sanitize_text_field',
								'args' => array(
										'description' => '<span class="description">Title display in search engines is limited to 70 chars, <span id="vseo_title-length"></span> chars left.</span>',
								),
								'title' => 'SEO Title',
						),
						'description' => array(
								'display_callback' => 'vseo_field_textarea',
								'sanitize_callback' => 'sanitize_text_field',
								'args' => array(
										'description' => '<span class="description">The <code>meta</code> description will be limited to 140 chars, <span id="vseo_description-length"></span> chars left</span>',
								),
								'title' => 'Meta Description',
						),
						'keywords' => array(
								'display_callback' => 'vseo_field_text',
								'sanitize_callback' => 'sanitize_text_field',
								'args' => array(
										'description' => '<span class="description">The comma separated <code>meta</code> key words to help determine the page topics.</span>',
								),
								'title' => 'Keywords',
						)
				),
				'advanced' => array(
						'robots-noindex' => array(
								'display_callback' => 'vseo_field_select',
								'sanitize_callback' => 'vseo_sanitize_select',
								'args' => array(
										'options' => array(
												'0' => 'Default for theme/post type',
												'index' => 'index',
												'noindex' => 'noindex'
										),
										'default' => '0',
								),
								'title' => 'Meta Robots Index',
						),
						'robots-nofollow' => array(
								'display_callback' => 'vseo_field_select',
								'sanitize_callback' => 'vseo_sanitize_select',
								'args' => array(
										'options' => array(
												'0' => 'Default for theme/post type',
												'follow' => 'follow',
												'nofollow' => 'nofollow'
										),
										'default' => '0',
								),
								'title' => 'Meta Robots Follow',
						),
						'canonical' => array(
								'display_callback' => 'vseo_field_text',
								'sanitize_callback' => 'vseo_sanitize_url',
								'args' => array(
										'description' => '<span class="description">The canonical URL that this page should point to, leave empty to default to permalink.</span>',
								),
								'title' => 'Canonical URL',
						),
				),
				'social' => array(
						'og_title' => array(
								'display_callback' => 'vseo_field_text',
								'sanitize_callback' => 'sanitize_text_field',
								'args' => array(
										'description' => '<span class="description">If you don\'t want to use the post or SEO title for sharing the post on Facebook/Open Graph but want another title there, write it here.</span>',
								),
								'title' => 'Facebook/Open Graph Title',
						),
						'og_description' => array(
								'display_callback' => 'vseo_field_textarea',
								'sanitize_callback' => 'sanitize_text_field',
								'args' => array(
										'description' => '<span class="description">If you don\'t want to use the meta description for sharing the post on Facebook/Open Graph but want another description there, write it here.</span>',
								),
								'title' => 'Facebook/Open Graph Description',
						),
						'twitter_title' => array(
								'display_callback' => 'vseo_field_text',
								'sanitize_callback' => 'sanitize_text_field',
								'args' => array(
										'description' => '<span class="description">If you don\'t want to use the post or SEO title for sharing the post on Twitter but want another title there, write it here.</span>',
								),
								'title' => 'Twitter Title',
						),
						'twitter_description' => array(
								'display_callback' => 'vseo_field_textarea',
								'sanitize_callback' => 'sanitize_text_field',
								'args' => array(
										'description' => '<span class="description">If you don\'t want to use the meta title for sharing the post on Facebook/Open Graph but want another title there, write it here.</span>',
								),
								'title' => 'Twitter Description',
						),
				),
		);

		if( ! apply_filters( 'vseo_use_facebook_meta', true ) ) {
			unset( $tab_fields[ 'social' ][ 'og_description' ] );
		}

		if( ! apply_filters( 'vseo_use_twitter_meta', true ) ) {
			unset( $tab_fields[ 'social' ][ 'twitter_description' ] );
		}


		return apply_filters( 'vseo_metabox_fields', isset( $tab_fields[ $tab ] ) ?
				$tab_fields[ $tab ] : array(), $tab, $post_type );
	}

}

class VSEO_Taxonomy {

	public static $option_key = 'vseo_term_meta';

	public static function init() {
		$taxonomies = get_taxonomies( '', 'names' );
		foreach( $taxonomies as $taxonomy ) {
			add_action( $taxonomy . '_add_form_fields', array( __CLASS__, 'add_new_meta_field' ), 10, 1 );
			add_action( $taxonomy . '_edit_form_fields', array( __CLASS__, 'edit_meta_field' ), 10, 2 );
			add_action( 'edited_' . $taxonomy, array( __CLASS__, 'save_meta' ), 10, 2 );
			add_action( 'create_' . $taxonomy, array( __CLASS__, 'save_meta' ), 10, 2 );
		}
		add_action( 'split_shared_term', array( __CLASS__, 'term_split_handling' ), 10, 4 );
	}

	public static function add_new_meta_field( $taxonomy ) {
		?>
		<div class="form-field">
			<label for="term_meta[title]"><?php esc_html_e( 'SEO title', 'voce_seo' ); ?></label>
			<input type="text" name="term_meta[title]" id="term_meta[title]" value="">
			<p class="description"><?php esc_html_e( 'Blank for default', 'voce_seo' ); ?></p>
		</div>
		<input type="hidden" value="<?php echo esc_attr( $taxonomy ); ?>" name="voce_seo_taxonomy">
		<?php
		wp_nonce_field( 'voce_seo_term', 'voce_seo_term' );
	}

	public static function edit_meta_field( $term, $taxonomy ) {
		$term_id = $term->term_id;
		$term_meta = get_option( self::$option_key );
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="term_meta[title]"><?php esc_html_e( 'SEO Title', 'voce_seo' ); ?></label>
			</th>
			<td>
				<input type="text" name="term_meta[title]" id="term_meta[title]"
							 value="<?php echo isset( $term_meta[ $taxonomy . '_' . $term_id ][ 'title' ] ) ? esc_attr( $term_meta[ $taxonomy . '_' . $term_id ][ 'title' ] ) : ''; ?>">
			</td>
		</tr>
		<input type="hidden" value="<?php echo esc_attr( $taxonomy ); ?>" name="voce_seo_taxonomy">
		<?php
		wp_nonce_field( 'voce_seo_term', 'voce_seo_term' );
	}

	public static function save_meta( $term_id ) {
		if(
				isset( $_POST[ 'term_meta' ] ) &&
				check_admin_referer( 'voce_seo_term', 'voce_seo_term' )
		) {
			$_POST = array_map( 'stripslashes_deep', $_POST ); //prevent escaping from option value http://codex.wordpress.org/Function_Reference/stripslashes_deep
			$taxonomy = $_POST[ 'voce_seo_taxonomy' ];

			$term_meta = get_option( self::$option_key );
			$cat_keys = array_keys( $_POST[ 'term_meta' ] );
			$meta_data = array();
			foreach( $cat_keys as $key ) {
				if( isset( $_POST[ 'term_meta' ][ $key ] ) ) {
					$meta_data[ $key ] = sanitize_text_field( $_POST[ 'term_meta' ][ $key ] );
				}
			}
			$term_meta[ $taxonomy . '_' . $term_id ] = $meta_data;
			update_option( self::$option_key, $term_meta );
		}
	}

	/**
	 * Handling the splitting of terms with WordPress 4.2
	 *
	 * @param type $old_term_id
	 * @param type $new_term_id
	 * @param type $term_taxonomy_id
	 * @param type $taxonomy
	 */
	public static function term_split_handling( $old_term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {
		$vseo_term_meta = get_option( self::$option_key );

		if( isset( $vseo_term_meta[ $taxonomy . '_' . $old_term_id ] ) ) {
			$vseo_term_meta[ $taxonomy . '_' . $new_term_id ] = $vseo_term_meta[ $taxonomy . '_' . $old_term_id ];
			unset( $vseo_term_meta[ $taxonomy . '_' . $old_term_id ] );
			update_option( self::$option_key, $vseo_term_meta );
		}
	}

}