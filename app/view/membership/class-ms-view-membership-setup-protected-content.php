<?php

class MS_View_Membership_Setup_Protected_Content extends MS_View {

	protected $data;

	public function to_html() {
		$tabs = $this->data['tabs'];

		if ( ! empty( $this->data['initial_setup'] ) ) {
			$description = array(
				__( 'Hello and welcome to Protected Content by WPMU DEV.', MS_TEXT_DOMAIN ),
				__( 'Let\'s begin by setting up the content you want to protect. Please select at least 1 page or category to protect.', MS_TEXT_DOMAIN ),
			);
		}
		else {
			$description = array(
				__( 'Choose what content of your site is protected.', MS_TEXT_DOMAIN ),
				__( 'Unprotected Content is available for everyone while Protected Content can be assigned to a Membership.', MS_TEXT_DOMAIN ),
			);
		}

		/** Render tabbed interface. */
		?>
		<div class="ms-wrap wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Select Content to Protect', MS_TEXT_DOMAIN ),
					'title_icon_class' => 'fa fa-pencil-square',
					'desc' => $description,
				)
			);

			$active_tab = $this->data['active_tab'];
			MS_Helper_Html::html_admin_vertical_tabs( $tabs, $active_tab );

			/** Call the appropriate form to render. */
			$render_callback = apply_filters(
				'ms_view_membership_setup_protected_content_render_tab_callback',
				array( $this, 'render_tab_' . str_replace( '-', '_', $active_tab ) ),
				$active_tab, $this->data
			);
			call_user_func( $render_callback );
			?>
		</div>
		<?php
	}


	/* ====================================================================== *
	 *                               CATEGORY
	 * ====================================================================== */

	public function render_tab_category() {
		$membership = $this->data['membership'];
		$fields = $this->get_tab_category_fields();
		$title = array();
		$desc = array();

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$title['category'] = __( 'Categories', MS_TEXT_DOMAIN );
			$desc['category'] = __( 'The easiest way to restrict content is by setting up a category that you can then use to mark content you want restricted.', MS_TEXT_DOMAIN );
		}
		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			$title['cpt_group'] = __( 'Custom Post Types', MS_TEXT_DOMAIN );
			$desc['cpt_group'] = __( 'You can choose Custom Post Type(s) to be restricted (eg. Products or Events).', MS_TEXT_DOMAIN );
		}

		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header(
				array( 'title' => implode( ' & ', $title ), 'desc' => $desc )
			); ?>
			<div class="ms-separator"></div>

			<?php if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) : ?>
				<div class="ms-half space">
					<div class="inside">
						<?php MS_Helper_Html::html_element( $fields['category'] ); ?>
						<?php MS_Helper_Html::html_separator( 'vertical' ); ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) : ?>
				<div class="ms-half">
					<div class="inside">
						<?php MS_Helper_Html::html_element( $fields['cpt'] ); ?>
					</div>
				</div>
			<?php endif; ?>

		</div>
		<?php
		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
	}

	public function get_tab_category_fields() {
		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		$rule_cat = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CATEGORY );
		$rule_cpt = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP );

		$fields = array(
			'category' => array(
				'id' => 'category',
				'type' => MS_Helper_Html::INPUT_TYPE_TAG_SELECT,
				'title' => __( 'Protect Categories:', MS_TEXT_DOMAIN ),
				'title_selected' => '<i class="ms-img ms-img-lock"></i> ' . __( 'Protected Categories', MS_TEXT_DOMAIN ),
				'value' => $rule_cat->rule_value,
				'field_options' => $rule_cat->get_content_array(),
				'data_placeholder' => __( 'Choose a category', MS_TEXT_DOMAIN ),
				'empty_text' => __( 'No Categories available', MS_TEXT_DOMAIN ),
				'button_text' => __( 'Protect Category', MS_TEXT_DOMAIN ),
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => MS_Model_Rule::RULE_TYPE_CATEGORY,
					'value' => 1,
					'_wpnonce' => $nonce,
					'action' => $action,
				),
			),

			'cpt' => array(
				'id' => 'cpt_group',
				'type' => MS_Helper_Html::INPUT_TYPE_TAG_SELECT,
				'title' => __( 'Protect Custom Post Types (CPTs):', MS_TEXT_DOMAIN ),
				'title_selected' => '<i class="ms-img ms-img-lock"></i> ' . __( 'Protected Custom Post Types', MS_TEXT_DOMAIN ),
				'value' => $rule_cpt->rule_value,
				'field_options' => $rule_cpt->get_content_array(),
				'data_placeholder' => __( 'Choose a CPT', MS_TEXT_DOMAIN ),
				'empty_text' => __( 'No Custom Post Types available', MS_TEXT_DOMAIN ),
				'button_text' => __( 'Protect CPT', MS_TEXT_DOMAIN ),
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP,
					'value' => 1,
					'_wpnonce' => $nonce,
					'action' => $action,
				),
			),

			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['step'],
			),
		);

		return apply_filters( 'ms_view_membership_setup_protected_content_get_category_fields', $fields );
	}


	/* ====================================================================== *
	 *                               PAGE
	 * ====================================================================== */

	public function render_tab_page() {
		$fields = $this->get_control_fields();
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_PAGE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Page( $rule, $membership );
		$rule_list_table->prepare_items();

		$edit_link = array(
			'id' => 'page_rule_edit',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => __( 'Manage Protected Pages', MS_TEXT_DOMAIN ),
			'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_PAGE ),
		);

		$title = __( 'Pages ', MS_TEXT_DOMAIN );
		if ( empty( $this->data['protected_content'] ) ) {
			$desc = sprintf(
				__( 'Give access to following Pages to %s members.', MS_TEXT_DOMAIN ),
				$this->data['membership']->name
			);
		}
		else {
			$desc = __( 'Protect the following Pages to members only.', MS_TEXT_DOMAIN );
		}

		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
			<div class="ms-separator"></div>

			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->search_box( __( 'Search Pages', MS_TEXT_DOMAIN ), 'search' ); ?>
				<?php $rule_list_table->display(); ?>
				<?php if ( empty( $this->data['protected_content'] ) ) : ?>
					<div class="ms-protection-edit-link">
						<?php MS_Helper_Html::html_element( $edit_link ); ?>
					</div>
				<?php endif; ?>
			</form>
		</div>
		<?php
		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
	}

	/* ====================================================================== *
	 *                               POSTS
	 * ====================================================================== */

	public function render_tab_post() {
		$fields = $this->get_control_fields();
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_POST );
		$rule_list_table = new MS_Helper_List_Table_Rule_Post( $rule, $membership );
		$rule_list_table->prepare_items();

		$edit_link = array(
			'id' => 'page_rule_edit',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => __( 'Manage Protected Posts', MS_TEXT_DOMAIN ),
			'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_POST ),
		);

		$title = __( 'Posts ', MS_TEXT_DOMAIN );
		if ( empty( $this->data['protected_content'] ) ) {
			$desc = sprintf(
				__( 'Give access to following Posts to %s members.', MS_TEXT_DOMAIN ),
				$this->data['membership']->name
			);
		}
		else {
			$desc = __( 'Protect the following Posts to members only. ', MS_TEXT_DOMAIN );
		}

		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
			<div class="ms-separator"></div>

			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->search_box( __( 'Search Posts', MS_TEXT_DOMAIN ), 'search' ); ?>
				<?php $rule_list_table->display(); ?>
				<?php if ( empty( $this->data['protected_content'] ) ) : ?>
					<div class="ms-protection-edit-link">
						<?php MS_Helper_Html::html_element( $edit_link ); ?>
					</div>
				<?php endif; ?>
			</form>
		</div>
		<?php
		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
	}

	/* ====================================================================== *
	 *                               CUSTOM POST TYPE
	 * ====================================================================== */

	public function render_tab_cpt() {
		$fields = $this->get_control_fields();
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Custom_Post_Type( $rule, $membership );
		$rule_list_table->prepare_items();

		$edit_link = array(
			'id' => 'page_rule_edit',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => __( 'Manage Protected Custom Post Types', MS_TEXT_DOMAIN ),
			'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE ),
		);

		$title = __( 'Custom Post Types ', MS_TEXT_DOMAIN );
		if ( empty( $this->data['protected_content'] ) ) {
			$desc = sprintf(
				__( 'Give access to following Custom Post Types to %s members.', MS_TEXT_DOMAIN ),
				$this->data['membership']->name
			);
		}
		else {
			$desc = __( 'Protect the following Custom Post Types to members only. ', MS_TEXT_DOMAIN );
		}

		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
			<div class="ms-separator"></div>

			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->search_box( __( 'Search Posts', MS_TEXT_DOMAIN ), 'search' ); ?>
				<?php $rule_list_table->display(); ?>
			</form>
			<?php if ( empty( $this->data['protected_content'] ) ): ?>
				<div class="ms-protection-edit-link">
					<?php MS_Helper_Html::html_element( $edit_link ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
	}

	/* ====================================================================== *
	 *                               COMMENT, MORE, MENU
	 * ====================================================================== */

	/**
	 * Render tab content for:
	 * Comments, More tag, Menus
	 *
	 * @since  1.0.0
	 */
	public function render_tab_comment() {
		$fields = $this->get_tab_comment_fields();
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( 'menu' );
		$rule_list_table = new MS_Helper_List_Table_Rule_Menu( $rule, $membership, $this->data['menu_id'] );
		$rule_list_table->prepare_items();

		$title = __( 'Comments, More Tag & Menus', MS_TEXT_DOMAIN );
		if ( empty( $this->data['protected_content'] ) ) {
			$desc = sprintf(
				__( 'Give access to protected Comments, More Tag & Menus to %s members.', MS_TEXT_DOMAIN ),
				$this->data['membership']->name
			);
		}
		else {
			$desc = __( 'Protect the Comments, More Tag & Menus to members only. ', MS_TEXT_DOMAIN );
		}

		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
			<div class="ms-separator"></div>

			<div class="ms-rule-wrapper">
				<?php MS_Helper_Html::html_element( $fields['comment'] ); ?>
				<?php if ( empty( $this->data['protected_content'] ) ) : ?>
					<div class="ms-protection-edit-link">
						<?php MS_Helper_Html::html_element( $fields['comment_rule_edit'] ); ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="ms-rule-wrapper">
				<?php MS_Helper_Html::html_element( $fields['more_tag'] ); ?>
				<?php if ( empty( $this->data['protected_content'] ) ) : ?>
					<div class="ms-protection-edit-link">
						<?php MS_Helper_Html::html_element( $fields['more_tag_rule_edit'] ); ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="ms-list-table-wrapper">
				<form id="ms-menu-form" method="post">
					<?php MS_Helper_Html::html_element( $fields['menu_id'] ); ?>
				</form>
				<?php $rule_list_table->display(); ?>
				<?php if ( empty( $this->data['protected_content'] ) ) : ?>
					<div class="ms-protection-edit-link">
						<?php MS_Helper_Html::html_element( $fields['menu_rule_edit'] ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
	}

	/**
	 * Prepare tab fields for:
	 * Comments, More tag, Menus
	 *
	 * @since  1.0.0
	 */
	public function get_tab_comment_fields() {
		$membership = $this->data['membership'];
		$nonce = wp_create_nonce( $this->data['action'] );
		$action = $this->data['action'];
		$rule_more_tag = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG );
		$rule_comment = $membership->get_rule( MS_Model_Rule::RULE_TYPE_COMMENT );
		$comments_desc = ( $this->data['protected_content'] )
			? __( 'Visitors', MS_TEXT_DOMAIN )
			: __( 'Members', MS_TEXT_DOMAIN );
		$desc = ( $this->data['protected_content'] )
			? __( 'Only Members', MS_TEXT_DOMAIN )
			: __( 'Members', MS_TEXT_DOMAIN );

		$fields = array(
			'comment' => array(
					'id' => 'comment',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'title' => __( 'Comments:', MS_TEXT_DOMAIN ),
					'desc' => sprintf( __( '%s have:', MS_TEXT_DOMAIN ), $comments_desc ),
					'value' => $rule_comment->get_rule_value( MS_Model_Rule_Comment::CONTENT_ID ),
					'field_options' => $rule_comment->get_content_array(),
					'class' => 'chosen-select',
					'data_ms' => array(
							'membership_id' => $membership->id,
							'rule_type' => MS_Model_Rule::RULE_TYPE_COMMENT,
							'rule_ids' => MS_Model_Rule_Comment::CONTENT_ID,
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			),
			'comment_rule_edit' => array(
					'id' => 'comment_rule_edit',
					'type' => MS_Helper_Html::TYPE_HTML_LINK,
					'value' => __( 'Edit Comments Restrictions', MS_TEXT_DOMAIN ),
					'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_COMMENT ),
			),
			'more_tag' => array(
					'id' => 'more_tag',
					'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
					'title' => __( 'More Tag:', MS_TEXT_DOMAIN ),
					'desc' => sprintf( __( '%s can read full post (beyond the More Tag):', MS_TEXT_DOMAIN ), $desc ),
					'value' => $rule_more_tag->get_rule_value( MS_Model_Rule_More::CONTENT_ID ) ? 1 : 0,
					'field_options' => $rule_more_tag->get_options_array(),
					'class' => 'ms-more-tag ms-ajax-update',
					'data_ms' => array(
							'membership_id' => $membership->id,
							'rule_type' => MS_Model_Rule::RULE_TYPE_MORE_TAG,
							'rule_ids' => MS_Model_Rule_More::CONTENT_ID,
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			),
			'more_tag_rule_edit' => array(
					'id' => 'more_tag_rule_edit',
					'type' => MS_Helper_Html::TYPE_HTML_LINK,
					'value' => __( 'Edit More Tag Restrictions', MS_TEXT_DOMAIN ),
					'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_MORE_TAG ),
			),
			'menu_id' => array(
					'id' => 'menu_id',
					'title' => __( 'Menus:', MS_TEXT_DOMAIN ),
					'desc' => __( 'Select menu to load:', MS_TEXT_DOMAIN ),
					'value' => $this->data['menu_id'],
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'field_options' => $this->data['menus'],
					'class' => 'chosen-select',
			),
			'menu_rule_edit' => array(
					'id' => 'menu_rule_edit',
					'type' => MS_Helper_Html::TYPE_HTML_LINK,
					'value' => __( 'Edit Menu Restrictions', MS_TEXT_DOMAIN ),
					'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_MENU ),
			),
			'action' => array(
					'id' => 'action',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $action,
			),
			'step' => array(
					'id' => 'step',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['step'],
			),
			'_wpnonce' => array(
					'id' => '_wpnonce',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $nonce,
			),
		);

		if ( ! $this->data['protected_content'] ) {
			$protected_content = MS_Model_Membership::get_visitor_membership();
			if ( MS_Model_Rule_Comment::RULE_VALUE_WRITE == $protected_content->get_rule( MS_Model_Rule::RULE_TYPE_COMMENT )->get_rule_value( MS_Model_Rule_Comment::CONTENT_ID ) ) {
				$fields['comment'] = array(
							'id' => 'comment',
							'type' => MS_Helper_Html::TYPE_HTML_TEXT,
							'title' => __( 'Comments:', MS_TEXT_DOMAIN ),
							'value' => __( 'Members can Read & Post comments', MS_TEXT_DOMAIN ),
							'class' => 'ms-field-description',
							'wrapper' => 'div',
				);
			}
			if ( ! $protected_content->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG )->get_rule_value( MS_Model_Rule_More::CONTENT_ID ) ) {
				$fields['more_tag'] = array(
							'id' => 'more_tag',
							'type' => MS_Helper_Html::TYPE_HTML_TEXT,
							'title' => __( 'More Tag:', MS_TEXT_DOMAIN ),
							'value' => __( 'Members can read full post (beyond the More Tag)', MS_TEXT_DOMAIN ),
							'class' => 'ms-field-description',
							'wrapper' => 'div',
				);
			}
		}
		return apply_filters( 'ms_view_membership_setup_protected_content_get_tab_comment_fields', $fields );
	}

	/* ====================================================================== *
	 *                               SHORTCODE
	 * ====================================================================== */

	public function render_tab_shortcode() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_SHORTCODE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Shortcode( $rule, $membership );
		$rule_list_table->prepare_items();

		$edit_link = array(
			'id' => 'shortcode_rule_edit',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => __( 'Manage Protected Shortcodes', MS_TEXT_DOMAIN ),
			'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_SHORTCODE ),
		);

		$title = __( 'Shortcodes', MS_TEXT_DOMAIN );
		if ( empty( $this->data['protected_content'] ) ) {
			$desc = sprintf(
				__( 'Give access to protected Shortcodes to %s members.', MS_TEXT_DOMAIN ),
				$this->data['membership']->name
			);
		}
		else {
			$desc = __( 'Protect the following Shortcodes to members only. ', MS_TEXT_DOMAIN );
		}

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
			<div class="ms-separator"></div>

			<?php $rule_list_table->views(); ?>
			<form action="" method="post">
				<?php $rule_list_table->display(); ?>
			</form>
			<?php if ( empty( $this->data['protected_content'] ) ): ?>
				<div class="ms-protection-edit-link">
					<?php MS_Helper_Html::html_element( $edit_link ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);

		$html = ob_get_clean();
		//TODO: Did this filter exist in the old version, or what purpose does it have? Can we remove it?
		echo apply_filters( 'ms_view_membership_protected_content_render_tab_shortcode', $html );
	}

	/* ====================================================================== *
	 *                               URL GROUP
	 * ====================================================================== */

	public function render_tab_url_group() {
		$fields = $this->prepare_url_group_fields();
		$edit_link = array(
			'id' => 'menu_rule_edit',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'value' => __( 'Edit URL Group Restrictions', MS_TEXT_DOMAIN ),
			'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Model_Rule::RULE_TYPE_URL_GROUP ),
		);

		$title = __( 'URL Groups', MS_TEXT_DOMAIN );
		if ( empty( $this->data['protected_content'] ) ) {
			$desc = sprintf(
				__( 'Give access to protected URL Groups to %s members.', MS_TEXT_DOMAIN ),
				$this->data['membership']->name
			);
		}
		else {
			$desc = __( 'Protect the following URL Groups to members only. ', MS_TEXT_DOMAIN );
		}

		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
			<div class="ms-separator"></div>

			<form action="" method="post" class="ms-form">
				<?php MS_Helper_Html::settings_box( $fields ); ?>
			</form>
			<div class="clear"></div>
			<?php if ( empty( $this->data['protected_content'] ) ): ?>
				<div class="ms-protection-edit-link">
					<?php MS_Helper_Html::html_element( $edit_link ); ?>
				</div>
			<?php endif;
			MS_Helper_Html::settings_footer(
				array( $fields['step'] ),
				$this->data['show_next_button']
			);
			MS_Helper_Html::settings_box(
				array(
					array(
						'id' => 'url_test',
						'desc' => __( 'Enter an URL to test against rules in the group', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'class' => 'widefat',
					),
				),
				__( 'Test URL group', MS_TEXT_DOMAIN )
			);
			?>
			<div id="url-test-results-wrapper"></div>
		</div>
		<?php
	}

	public function prepare_url_group_fields() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_URL_GROUP );

		$nonce = wp_create_nonce( $this->data['action'] );
		$action = $this->data['action'];

		$fields = array(
				'access' => array(
						'id' => 'access',
						'title' => __( 'Members Access', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
						'value' => $rule->access,
						'class' => '',
						'data_ms' => array(
								'membership_id' => $membership->id,
								'rule_type' => $rule->rule_type,
								'field' => 'access',
								'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD,
								'_wpnonce' => wp_create_nonce( MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD ),
						),
				),
				'strip_query_string' => array(
						'id' => 'strip_query_string',
						'title' => __( 'Strip query strings from URL', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
						'value' => $rule->strip_query_string,
						'data_ms' => array(
								'membership_id' => $membership->id,
								'rule_type' => $rule->rule_type,
								'field' => 'strip_query_string',
								'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD,
								'_wpnonce' => wp_create_nonce( MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD ),
						),
				),
				'is_regex' => array(
						'id' => 'is_regex',
						'title' => __( 'Is regular expression', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
						'value' => $rule->is_regex,
						'data_ms' => array(
								'membership_id' => $membership->id,
								'rule_type' => $rule->rule_type,
								'field' => 'is_regex',
								'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD,
								'_wpnonce' => wp_create_nonce( MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD ),
						),
				),
				'rule_value' => array(
						'id' => 'rule_value',
						'title' => __( 'Page URLs', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
						'value' => implode( PHP_EOL, $rule->rule_value ),
						'class' => 'ms-textarea-medium ms-ajax-update',
						'read_only' => ! empty( $this->data['protected_content'] ) ? '' : 'readonly',
						'data_ms' => array(
								'membership_id' => $membership->id,
								'rule_type' => $rule->rule_type,
								'field' => 'rule_value',
								'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD,
								'_wpnonce' => wp_create_nonce( MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD ),
						),
				),
				'_wpnonce' => array(
						'id' => '_wpnonce',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $nonce,
				),
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $action,
				),
				'step' => array(
						'id' => 'step',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['step'],
				),
				'membership_id' => array(
						'id' => 'membership_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $membership->id,
				),

		);
		if ( empty( $this->data['protected_content'] ) ) {
			unset( $fields['strip_query_string'] );
			unset( $fields['is_regex'] );
		}
		return apply_filters( 'ms_view_membership_setup_protected_content_get_tab_urlgroup_fields', $fields );
	}

	/* ====================================================================== *
	 *                               SHARED
	 * ====================================================================== */

	public function get_control_fields() {
		$membership = $this->data['membership'];
		$nonce = wp_create_nonce( $this->data['action'] );
		$action = $this->data['action'];

		$fields = array(
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $action,
				),
				'step' => array(
						'id' => 'step',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['step'],
				),
				'_wpnonce' => array(
						'id' => '_wpnonce',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $nonce,
				),

		);
		return apply_filters( 'ms_view_membership_setup_protected_content_get_control_fields', $fields );
	}

}