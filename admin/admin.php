<?php

class VSEO_Admin {
	public static function init() {
		require_once(__DIR__ . '/field-callbacks.php');
		VSEO_Metabox::init();		
	}
}

class VSEO_Metabox {
	public static function init() {
		add_action('add_meta_boxes', function($post_type) {
				if(get_post_type_object($post_type)->publicly_queryable) {
					add_meta_box('vseo_meta', 'SEO Settings', array( 'VSEO_Metabox', 'meta_box' ), 
						$post_type, 'normal');
				}
		});
		
		add_action('save_post', array(__CLASS__, 'on_save_post'));
	}
	
	public static function meta_box($post) {
		$post_type = get_post_type($post);
		$tabs = self::get_metabox_tabs($post_type);
		$vseo_meta = (array) get_post_meta( $post->ID, 'vseo_meta', true );
		?>
		<div class="vseo-metabox-tabs-div">
			<ul class="vseo-metabox-tabs" id="vseo-metabox-tabs">
				<?php 
				foreach($tabs as $tab_id => $tab) {
					sprintf('<li class="vseo-%1$s"><a class="vseo_tablink" href="#vseo_%1$s">%2$s</a></li>',
						$tab_id, esc_html($tab['label']));
				}
				?>
			</ul>
			<?php foreach($tabs as $tab_id => $tab) : ?>
				<div class="vseotab" id="vseo-<?php echo $tab_id ?>">
					<?php foreach(self::get_metabox_fields($tab_id, $post_type) as $field_id => $field): ?>
					<p>
						<label><?php echo $field['title']; ?></label>
						<?php echo call_user_func_array( $field['display_callback'], array(
							'vseo' . $field_id,
							isset( $vseo_meta[$field_id] ) ? $vseo_meta[$field_id] : null,
							isset( $field['args'] ) ? $field['args'] : array( ) )
						); ?>
					</p>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php	
		wp_nonce_field('vseo_update_meta', 'vseo_nonce');
	}
	
	public static function on_save_post($post_id) {
		if(wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
			return $post_id;
		}
		if(isset($_REQUEST['vseo_nonce']) && wp_verify_nonce($_REQUEST['vseo_nonce'], 'vseo_update_meta')) {
			$post_type = get_post_type($post_id);
			$tabs = self::get_metabox_tabs($post_type);
			$vseo_meta = (array) get_post_meta( $post_id, 'vseo_meta', true );
			
			foreach($tabs as $tab_id => $tab) {
				foreach(self::get_metabox_fields($tab_id, $post_type) as $field_id => $field) {
					if(isset($field['sanitize_callback'])) {
						$vseo_meta[$field_id] = call_user_func_array($field['sanitize_callback'], array(
							isset($_REQUEST['vseo' . $field_id]) ? $_REQUEST['vseo' . $field_id] : null,
							$field['args']
							));
					}
				}
			}
			update_post_meta($post_id, 'vseo_meta', $vseo_meta);
		}
	}
	
	private function get_metabox_tabs($post_type) {
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
		return apply_filters('vseo_metabox_tabs', $metabox_tabs, $post_type);
	}
	
	private function get_metabox_fields($tab, $post_type) {
		$tab_fields = array(
			'general' => array(
				'title' => array(
					"display_callback" => "vseo_field_text",
					'sanitize_callback' => 'vseo_sanitize_meta_text',
					'args' => array(
						"description" => '<span class="description">Title display in search engines is limited to 70 chars, <span id="vseo_title-length"></span> chars left.</span>',
					),
					"title" => "SEO Title",
				),
				'description' => array(
					"display_callback" => "vseo_field_textarea",
					'sanitize_callback' => 'vseo_sanitize_meta_text',
					'args' => array(
						"description" => '<span class="description">The <code>meta</code> description will be limited to 140 chars, <span id="vseo_description-length"></span> chars left</span>',
					),
					"title" => "Meta Description",
				),
			),
			'advanced' => array(
				'robots-noindex' => array(
					"display_callback" => "vseo_field_select",
					'sanitize_callback' => 'vseo_sanitize_select',
					'args' => array(
						'options' => array(
							'0' => 'Default for theme/post type',
							'index' => 'index',
							'noindex' => 'noindex'
						),
						'default' => '0',
					),
					"title" => "Meta Robots Index",
				),
				'robots-nofollow' => array(
					"display_callback" => "vseo_field_select",
					'sanitize_callback' => 'vseo_sanitize_select',
					'args' => array(
						'options' => array(
							'0' => 'Default for theme/post type',
							'follow' => 'follow',
							'nofollow' => 'nofollow'
						),
						'default' => '0',
					),
					"title" => "Meta Robots Follow",
				),
				'canonical' => array(
					"display_callback" => "vseo_field_text",
					'sanitize_callback' => 'vseo_sanitize_url',
					'args' => array(
						"description" => '<span class="description">The canonical URL that this page should point to, leave empty to default to permalink.</span>',
					),
					"title" => "Canonical URL",
				),
				'redirect' => array(
					"display_callback" => "vseo_field_text",
					'sanitize_callback' => 'vseo_sanitize_url',
					'args' => array(
						"description" => '<span class="description">The URL that this page should redirect to.</span>',
					),
					"title" => "301 Redirect URL",
				),
				
			),
			'social' => array(
				'og_description' => array(
					"display_callback" => "vseo_field_textarea",
					'sanitize_callback' => 'vseo_sanitize_meta_text',
					'args' => array(
						"description" => '<span class="description">If you don\'t want to use the meta description for sharing the post on Facebook but want another description there, write it here.</span>',
					),
					"title" => "Facebook Description",
				),
				
			),
		);
		
		return apply_filters('vseo_metabox_fields', isset($tab_fields[$tab]) ? 
			$tab_fields[$tab] : array(), $tab, $post_type);
		
	}
	
	
}