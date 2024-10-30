<?php
/**
 *   Plugin Name: Copy Or Move Comments
 *   Plugin URI:
 *   Description: Using Copy/Move WordPress Plugin the admin can copy or move any comment from several types of pages to any other page!
 *   Version: 5.1.0
 *   Author: biztechc
 *   Author URI: https://www.appjetty.com/
 *   License: GPLv2
 *
 * @package Copy-move-comments
 */

?>
<?php
require_once 'copy-move-functions.php';
if ( ! class_exists( 'copy_move_comments' ) ) {

	/** Add plugin functionality */
	class Copy_Move_Comments {

		/** Add plugin name in admin menu */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'copy_move_menu' ) );
		}

		/** Add plugin name in admin menu  */
		public function copy_move_menu() {
			add_menu_page( 'Copy/Move Comments', 'Copy/Move Comments', 'manage_options', 'copy-move', array( $this, 'copy_move_settings_page' ), 'dashicons-format-chat' );
			add_submenu_page( 'copy-move', 'Setting', 'Setting', 'manage_options', 'copy-move-setting', array( $this, 'copy_move_setting_func' ) );
			add_action( 'admin_init', array( $this, 'register_copy_move__suggest_settings' ) );
		}

		/** Callback function for registering settings page */
		public function register_copy_move__suggest_settings() {
			register_setting( 'copy-move-settings-group', 'all_private_post_type' );
		}

		/** Add settings page functionality */
		public function copy_move_setting_func() {
			wp_enqueue_style( 'cm-select2-css' );
			wp_enqueue_script( 'cm-select2-script' );
			wp_enqueue_script( 'cm-custom-script' );

			if ( isset( $_REQUEST['settings-updated'] ) && sanitize_text_field( wp_unslash( $_REQUEST['settings-updated'] ) ) && 'true' === $_REQUEST['settings-updated'] ) {
				?>
				<div class="updated notice notice-success is-dismissible" id="message">
					<p>Setting updated.</p><button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button>
				</div>
				<?php
			}
			?>
			<h2>Copy/Move Comments Setting page</h2><br>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'copy-move-settings-group' );
				$set_privet_post_type = get_option( 'all_private_post_type' );
				?>
				<tr valign="top">
					<td>
						<fieldset>
							<?php
							{
							if ( 'on' === $set_privet_post_type ) {
								$checked = 'checked=checked';
							}
							?>
								<input type="checkbox" name="all_private_post_type" 
								<?php
								if ( isset( $checked ) ) {
									echo esc_html( $checked ); }
								?>
								><label>Include Private Posts?</label><br>
							<?php
								$checked = '';
							}
							?>
						</fieldset>
					</td>
				</tr>
				<?php
				$other_attributes = array( 'id' => 'wpdocs-button-id' );
				submit_button( 'Save', 'primary', 'submit', true, $other_attributes );
				?>

			</form>
			<?php
		}

		/** Main page for copy-move comments */
		public function copy_move_settings_page() {
			wp_enqueue_style( 'cm-select2-css' );
			wp_enqueue_script( 'cm-select2-script' );
			?>
			<div class="wrap">
				<h2>Copy/Move Comments</h2><br>
				<form id="copy_move_form" action="admin-post.php" method="post">
					<input type="hidden" id="hidden_action_name">
					<input type="hidden" id="hidden_source_type">
					<input type="hidden" id="hidden_post_id">
					<input type="hidden" id="hidden_comment_type">
					<?php
					settings_fields( 'copy-move-settings-group' );

					do_settings_sections( 'copy-move-settings-group' );
					?>
					<div class="tablenav top">
						<p style="font-size: larger; float: left; margin-top: 5px; margin-right: 7px;"><?php esc_html_e( 'Action' ); ?>:</p>
						<div class="alignleft actions">
							<label class="screen-reader-text">Select Action</label>
							<select id="copy-move" name="copy-move">
								<option value="">Select Action</option>
								<option value="copy">Copy</option>
								<option value="move">Move</option>
							</select>
						</div>
						<p style="font-size: larger; float: left; margin-top: 5px; margin-right: 7px;"><?php esc_html_e( 'Source' ); ?>:</p>
						<div class="alignleft actions">
							<?php
							$post_types = array( 'post', 'page' );
							?>
							<label for="cat" class="screen-reader-text">All Post Types</label>
							<select name="all_post_types" id="all_post_types">
								<option value="0">Select Post Type</option>
								<?php
								foreach ( $post_types as $post_type ) {
									?>
									<option value="<?php echo esc_html( $post_type ); ?>"><?php echo esc_html( $post_type ); ?></option>

									<?php
								}
								?>
							</select>

						</div>

						<div class="alignleft actions" id="">
							<select id="source_post" name="source_post">
								<option value="">Select Post</option>
							</select>
							<span id="bc_loader" style="display: none;"><img src="<?php echo esc_html( plugins_url( 'ajax-loader.gif', __FILE__ ) ); ?>" alt=""></span>
						</div>
						<div class="alignleft actions" id="comment_history">
							<p style="font-size: larger; float: left; margin-top: 5px; margin-right: 7px;"><?php esc_html_e( 'Select Comment Type' ); ?>:</p>
							<select id="comment_type" name="comment_type">
								<option value="0">Select Comment Type</option>
								<!--<option value="1">Single</option>
								<option value="2">With Replies</option>-->
							</select>
							<span id="bc_loader1" style="display: none;"><img src="<?php echo esc_html( plugins_url( 'ajax-loader.gif', __FILE__ ) ); ?>" alt=""></span>
						</div>


					</div>
					<div id="get_comments"></div>
					<div class="tablenav bottom" style="display: none;">
						<p style="font-size: larger; float: left; margin-top: 5px; margin-right: 10px;"><?php esc_html_e( 'Target' ); ?>:</p>
						<div class="alignleft actions">
							<?php
							$target_post_types = array( 'post', 'page' );
							?>
							<select name="target_all_post_types" id="target_all_post_types">
								<option value="0">Select Post Type</option>
								<?php
								foreach ( $target_post_types as $target_post_type ) {
									?>
									<option value="<?php echo esc_html( $target_post_type ); ?>"><?php echo esc_html( $target_post_type ); ?></option>
									<?php
								}
								?>
							</select>
						</div>
						<div class="alignleft actions">
							<select id="target_post" name="target_post">
								<option value="0">Select Post</option>
							</select>
							<span id="target_bc_loader" style="display: none;"><img src="
							<?php
							echo esc_html( plugins_url( 'ajax-loader.gif', __FILE__ ) );
							?>
							" alt=""></span>
						</div>
						<input type="submit" value="Perform Action" class="button action" id="do_action2" name="do_action2" onclick="return chk_val();">
					</div>
					<input type="hidden" name="action" value="action_move">
				</form>
				<script type="text/javascript">
					function checkAll(ele) {
						var checkboxes = document.getElementsByTagName('input');
						if (ele.checked) {
							for (var i = 0; i < checkboxes.length; i++) {
								if (checkboxes[i].type == 'checkbox') {
									checkboxes[i].checked = true;
								}
							}
						} else {
							for (var i = 0; i < checkboxes.length; i++) {
								if (checkboxes[i].type == 'checkbox') {
									checkboxes[i].checked = false;
								}
							}
						}
					}
				</script>
			</div>
			<?php
		}
	}
}
new Copy_Move_Comments(); // Initiate object .

add_action( 'admin_footer', 'copy_move_get_all_posts' );
add_action( 'admin_footer', 'copy_move_add_validation', 10 );
add_action( 'wp_ajax_get_all_posts', 'get_all_posts_callback' );
add_action( 'wp_ajax_get_comment_type', 'get_comment_type_callback' );
add_action( 'wp_ajax_get_post_comments', 'get_post_comments_callback' );
add_action( 'wp_ajax_perform_action', 'perform_action_callback' );

/** To enqueue script when get posts*/
function copy_move_get_all_posts() {

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'cm-custom-script' );
}

/** Get_all_post_callback call when post type change */
function get_all_posts_callback() {
	$set_privet_post_type = get_option( 'all_private_post_type' );
	if ( isset( $_POST['post_type'] ) ) {
		$post_type = sanitize_text_field( wp_unslash( $_POST['post_type'] ) );
	}
	$get_res   = new Copy_Move_Functions();
	$get_posts = $get_res->get_posts( $post_type, $set_privet_post_type );
	?>
	<option value="">Select Post</option>
	<?php foreach ( $get_posts as $get_post ) { ?>
		<option value="<?php echo esc_attr( $get_post->ID ); ?>"><?php echo esc_html( $get_post->post_title ); ?></option>
		<?php
	}
	wp_enqueue_script( 'jquery' );
	exit;
}

/** Get_post_comments_callback called when we change comment type */
function get_post_comments_callback() {

	global $wpdb;
	if ( isset( $_POST['post_id'] ) ) {
		$post_id = wp_unslash( sanitize_text_field( $_POST['post_id'] ) );
	}
	if ( isset( $_POST['comment_type'] ) ) {
		$comment_type = wp_unslash( sanitize_text_field( $_POST['comment_type'] ) );
	}
	$get_res1     = new Copy_Move_Functions();
	$get_comments = $get_res1->get_all_comments_by_postid( $post_id );
	?>

	<table class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th class="manage-column column-cb check-column" scope="col"><input type="checkbox" value="0" name="move_comment_id1[]" class="move_comment_id1" onchange="checkAll(this);"></th>
				<th class="manage-column column-author" scope="col">Author</th>
				<th width="400" class="manage-column column-title sortable desc" scope="col">Comment</th>
				<th class="manage-column column-date sortable asc" scope="col">In Reply To</th>
				<th class="manage-column column-date sortable asc" scope="col">Status</th>
				<th class="manage-column column-date sortable asc" scope="col">Date</th>
			</tr>
		</thead>
		<tbody>
			<?php
			if ( isset( $comment_type ) && 0 === $comment_type ) {
				?>
				<tr>
					<td class="source_error" colspan="4" align="center">No Comments found. Please change Comment Type.</td>
				</tr>

				<?php
			} else {
				$c = 0;
				if ( ! empty( $get_comments ) ) {
					$get_comment_ids = $get_comments;
					$ids             = $get_comment_ids;
					do {
						$tem       = $ids;
						$tem_count = count( $tem );
						$ids       = array();
						for ( $i = 0; $i < $tem_count; $i++ ) {

							$child_comments = get_comments(
								array(
									'fields' => 'ids',
									'status' => '0, 1, spam',
									'parent' => $tem[ $i ],
								)
							);
							if ( ! empty( $child_comments ) ) {
								foreach ( $child_comments as $single_child ) {
									$ary[ $tem[ $i ] ][] = $single_child;
									$ids[]               = $single_child;
								}
							}
						}
						$count_ids = count( $ids );
					} while ( $count_ids > 0 );

					$ids     = $get_comment_ids;
					$printed = array();
					do {
						$get_comment_ids_1       = $get_comment_ids;
						$count_get_comment_ids_1 = count( $get_comment_ids_1 );
						$get_comment_ids         = array();
						for ( $i = 0; $i < $count_get_comment_ids_1; $i++ ) {
							if ( in_array( $get_comment_ids_1[ $i ], $printed, true ) ) {
								continue;
							} else {
								$printed[]   = $get_comment_ids_1[ $i ];
								$get_comment = $get_res1->get_single_comment( $get_comment_ids_1[ $i ] );
								?>
								<tr id="<?php echo esc_attr( $c ); ?>">
									<?php
									if ( isset( $comment_type ) && '2' === $comment_type ) {
										if ( ! in_array( $get_comment_ids_1[ $i ], $ids, true ) ) {
											?>
											<td></td>
											<?php
										} else {
											?>
											<td><input type="checkbox" value="<?php echo esc_attr( $get_comment->comment_ID ); ?>" name="move_comment_id[]" class="chkbox_val"></td>
											<?php
										}
									} else {
										?>
										<td><input type="checkbox" value="<?php echo esc_attr( $get_comment->comment_ID ); ?>" name="move_comment_id[]" class="chkbox_val"></td>
										<?php
									}
									?>

									<td><?php echo esc_html( $get_comment->comment_author ); ?></td>
									<td>
										<?php
										if ( ! in_array( $get_comment_ids_1[ $i ], $ids, true ) && isset( $temp ) ) {
											echo esc_html( str_repeat( ' - ', count( $temp ) ) );
										} else {
											$temp = array();
										}
										echo esc_html( $get_comment->comment_content );
										?>
										</td>
									<td>
										<?php
										if ( '0' !== $get_comment->comment_parent ) {
											$child_comment = get_comment( $get_comment->comment_parent );
											echo esc_html( $child_comment->comment_author );
										} else {
											echo '-';
										}
										?>
									</td>
									<?php
									if ( '1' === $get_comment->comment_approved ) {
										$status = 'Approved';
									} elseif ( '0' === $get_comment->comment_approved ) {
										$status = 'Pending';
									} elseif ( 'spam' === $get_comment->comment_approved ) {
										$status = 'Spam';
									}
									?>
									<td><?php echo esc_html( $status ); ?></td>
									<td><?php echo esc_html( $get_comment->comment_date ); ?></td>
								</tr>
								<?php
								if ( isset( $ary ) && ! empty( $ary ) ) {
									if ( isset( $ary[ $get_comment_ids_1[ $i ] ] ) && count( $ary[ $get_comment_ids_1[ $i ] ] ) > 0 ) {
										$temp[]          = $get_comment_ids_1;
										$get_comment_ids = $ary[ $get_comment_ids_1[ $i ] ];
										break;
									}
								}
							}
						}

						if ( count( $get_comment_ids_1 ) === $i ) {
							if ( isset( $temp ) && is_array( $temp ) & '' !== $temp ) {
								$get_comment_ids = array_pop( $temp );
							}
						}
						if ( is_array( $get_comment_ids ) ) {
							$count_get_comment_ids = count( $get_comment_ids );
						}
					} while ( null !== $get_comment_ids && $count_get_comment_ids > 0 );
				} else {
					?>
					<tr>
						<td class="source_error" colspan="4" align="center">No Comments found. Please change Source Post.</td>
					</tr>

					<?php
				}
			}
			?>
		<tfoot>
			<tr>
				<th class="manage-column column-cb check-column" scope="col"><input type="checkbox" value="0" name="move_comment_id1[]" class="move_comment_id1" onchange="checkAll(this);"></th>
				<th class="manage-column column-author" scope="col">Author</th>
				<th width="400" class="manage-column column-title sortable desc" scope="col">Comment</th>
				<th class="manage-column column-date sortable asc" scope="col">In Reply To</th>
				<th class="manage-column column-date sortable asc" scope="col">Status</th>
				<th class="manage-column column-date sortable asc" scope="col">Date</th>
			</tr>
		</tfoot>
		</tbody>
	</table>
	<?php
	exit;
}

/** For selecting comment types */
function get_comment_type_callback() {
	?>
	<option value="0">Select Comment Type</option>
	<option value="1">Single</option>
	<option value="2">With Replies</option>

	<?php
}

/** Add validations in copy-move page */
function copy_move_add_validation() {
	?>
	<script type="text/javascript">
		function chk_val() {
			var target_type = jQuery('#target_all_post_types').val();
			var target_post = jQuery('#target_post').val();

			//var flag = 0;
			jQuery('input[type=checkbox]').each(function() {
				var sThisVal = (this.checked ? jQuery(this).val() : "");

				if (sThisVal != '') {
					flag = 1;
				}
			});
			if (flag == 0) {
				alert('Select any one comment');
				return false;
			}
			if (target_type == 0) {
				alert('Select any one post type');
				return false;

			}
			if (target_post == 0) {
				alert('Select any one target post');
				return false;

			}

		}
	</script>
	<?php
}

add_action( 'admin_post_action_move', 'prefix_admin_action_move' );

/** Checks if user logged in or not */
function prefix_admin_action_move() {
	if ( ! is_user_logged_in() ) {
		$url = admin_url();
		wp_safe_redirect( $url . '/admin.php?page=copy-move&error=1' );
		exit;
	} elseif ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		if ( ! in_array( 'administrator', $user->roles, true ) ) {
			$url = admin_url();
			wp_safe_redirect( $url . '/admin.php?page=copy-move&error=1' );
			exit;
		}
	}

	global $wpdb;
	$get_source_id    = isset( $_POST['source_post'] ) ? sanitize_text_field( wp_unslash( $_POST['source_post'] ) ) : 0;
	$get_target_id    = isset( $_POST['target_post'] ) ? sanitize_text_field( wp_unslash( $_POST['target_post'] ) ) : 0;
	$get_action_type  = isset( $_POST['copy-move'] ) ? sanitize_text_field( wp_unslash( $_POST['copy-move'] ) ) : '';
	$get_comment_type = isset( $_POST['comment_type'] ) ? sanitize_text_field( wp_unslash( $_POST['comment_type'] ) ) : 0;
	$get_comment_ids  = isset( $_POST['move_comment_id'] ) ? $_POST['move_comment_id'] : array();

	if ( isset( $get_comment_type ) && $get_comment_type == '1' ) {
		$all_ids = $get_comment_ids;
	} else {
		$ids = $get_comment_ids;
		do {
			$tem       = $ids;
			$tem_count = count( $tem );
			$ids       = array();
			for ( $i = 0; $i < $tem_count; $i++ ) {
				$s   = "select * from $wpdb->comments where comment_parent = '" . $tem[ $i ] . "' and comment_Approved != 'trash'";
				$res = $wpdb->get_results( $s );
				if ( $res ) {
					for ( $j = 0; $j < count( $res ); $j++ ) {
						$ary[ $tem[ $i ] ][] = $res[ $j ]->comment_ID;
						$ids[]               = $res[ $j ]->comment_ID;
					}
				}
			}
		} while ( count( $ids ) > 0 );

		$ids = $get_comment_ids;
		do {
			$get_comment_ids_1       = $get_comment_ids;
			$get_comment_ids         = array();
			$count_get_comment_ids_1 = count( $get_comment_ids_1 );
			for ( $i = 0; $i < $count_get_comment_ids_1; $i++ ) {
				if ( ! empty( $printed ) && in_array( $get_comment_ids_1[ $i ], $printed, true ) ) {
					continue;
				} else {
					$printed[] = $get_comment_ids_1[ $i ];
					echo '<br>';
					if ( ! in_array( $get_comment_ids_1[ $i ], $ids, true ) && isset( $temp ) ) {
						echo esc_html( str_repeat( ' - ', count( $temp ) ) );
					}
					echo esc_html( $get_comment_ids_1[ $i ] );
					$all_ids[] = $get_comment_ids_1[ $i ];
					if ( isset( $ary[ $get_comment_ids_1[ $i ] ] ) && count( $ary[ $get_comment_ids_1[ $i ] ] ) > 0 ) {
						$temp[]          = $get_comment_ids_1;
						$get_comment_ids = $ary[ $get_comment_ids_1[ $i ] ];
						break;
					}
				}
			}
			$counts_get_comment_ids_1 = count( $get_comment_ids_1 );
			if ( $i === $counts_get_comment_ids_1 && isset( $temp ) ) {
				$get_comment_ids = is_array( $temp ) ? array_pop( $temp ) : '';
			}
			if ( is_array( $get_comment_ids ) ) {
				$count_get_comment_ids = count( $get_comment_ids );
			}
		} while ( null !== $get_comment_ids && $count_get_comment_ids > 0 );
	}

	if ( ! empty( $all_ids ) ) {
		$count_all_ids  = count( $all_ids );
		$comment_cnt    = $count_all_ids;
		$get_comment_id = implode( ',', $all_ids );
	}

	if ( isset( $get_source_id ) && isset( $get_target_id ) && isset( $get_action_type ) && isset( $all_ids ) && '' !== $get_source_id && '' !== $get_target_id && '' !== $get_action_type && ! empty( $all_ids ) ) {
		$perform_action    = new Copy_Move_Functions();
		$ary               = isset( $ary ) ? $ary : array();
		$transfer_comments = $perform_action->perform_action( $get_source_id, $get_target_id, $get_action_type, $get_comment_id, $ary, $all_ids, $get_comment_type, $comment_cnt );
		if ( false === $transfer_comments ) {
			$url = admin_url();
			wp_safe_redirect( $url . '/admin.php?page=copy-move&error=1' );
			exit;
		} else {
			$url = admin_url();
			wp_safe_redirect( $url . '/admin.php?page=copy-move&success=1' );
			exit;
		}
	} else {
		$url = admin_url();
		wp_safe_redirect( $url . '/admin.php?page=copy-move&error=1' );
		exit;
	}
}

add_action( 'admin_footer', 'error_message', 15 );

/** Checking for copy/move comments in selected posts or page */
function error_message() {
	if ( isset( $_REQUEST['success'] ) && $_REQUEST['success'] == 1 ) {
		$url         = admin_url();
		$all_comment = $url . 'edit-comments.php';
		?>
		<div class="notice notice-success">
			<p>Comments moved/copied successfully.Click here to <a href='<?php echo esc_url( $all_comment ); ?>'>view.</a></p>
		</div>

		<?php
	}
	if ( isset( $_REQUEST['error'] ) && 1 == $_REQUEST['error'] ) {
		?>
		<div class="error">
			<p><?php esc_html_e( 'Please select action OR select atleast one comment to copy/move.' ); ?></p>
		</div>
		<?php
	}
}

// styles and scripts .
add_action( 'admin_enqueue_scripts', 'copy_and_move_wp_admin_enqueue_scripts' );

/** Callback function for admin_enqueue_scripts hook */
function copy_and_move_wp_admin_enqueue_scripts() {

	// styles .
	wp_register_style( 'cm-select2-css', plugins_url( 'css/select2.min.css', __FILE__ ), array(), true );

	// scripts .
	wp_register_script( 'cm-select2-script', plugins_url( 'js/select2.min.js', __FILE__ ), array(), true, array() );

	wp_register_script( 'cm-custom-script', plugins_url( 'js/custom.js', __FILE__ ), array(), true, array() );
}
?>
