<?php
/** Ini_set("display_errors", "1"); if you want to debug then uncheck this commnet */
class Copy_Move_Functions {
	/**
	 * Get posts when when user select post types
	 *
	 * @param bool| string $post_type is get post_type .
	 * @param bool         $set_privet_post_type is check if private post type is selected or not .
	 */
	public function get_posts( $post_type, $set_privet_post_type ) {
		$data = array();
		if ( 'on' === $set_privet_post_type ) {
			$post_status = array( 'publish', 'private' );
		} else {
			$post_status = array( 'publish' );
		}

		$post_query = new WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => $post_status,
				'posts_per_page' => -1,
			)
		);
		if ( $post_query->post_count ) {
			$data = $post_query->posts;
		}
		return $data;
	}

	/** Get all comments when select comment type
	 *
	 * @param bool| string $id is get selected post's post id when comment type select .
	 */
	public function get_all_comments_by_postid( $id ) {
		$data = array();
		if ( is_numeric( $id ) ) {
			$data = get_comments(
				array(
					'fields'  => 'ids',
					'orderby' => 'comment_ID',
					'order'   => 'ASC',
					'post_id' => $id,
					'status'  => '0, 1, spam',
					'parent'  => 0,
				)
			);
		}

		return $data;
	}

	/**
	 * When submit perform action in copy-move page
	 *
	 * @param int    $source_post_id is for source selected post's id .
	 * @param int    $target_post_id is for target selected post's id .
	 * @param string $get_action_type is for selected action type(copy or move) .
	 * @param int    $comment_id is for comment id .
	 * @param array  $ary is not used .
	 * @param array  $all_ids is get all selected comment ids .
	 * @param int    $get_comment_type is get comment type .
	 * @param int    $comment_cnt is get comment count .
	 */
	public function perform_action( $source_post_id, $target_post_id, $get_action_type, $comment_id, $ary, $all_ids, $get_comment_type, $comment_cnt ) {
		global $wpdb;

		if ( null === get_post( $source_post_id ) || null === get_post( $target_post_id ) ) {
			return false;
		}

		if ( 'move' === $get_action_type ) {

			// update the comment_post_id to $target_post_id .
			if ( isset( $get_comment_type ) && '1' === $get_comment_type ) {

				$all_comments = $wpdb->get_results( "select * from $wpdb->comments where comment_id IN ($comment_id)" );

				foreach ( $all_comments as $d1 ) {
					if ( in_array( $d1->comment_parent, $all_ids, true ) ) {
						$sql[] = $wpdb->prepare( "update {$wpdb->comments} set comment_post_id = %d where comment_id IN ($d1->comment_ID)", $target_post_id );

						// Decrement the comment_count in the $source_post_id .
						$sql[] = $wpdb->prepare( "update {$wpdb->posts} set comment_count = comment_count-1 where id = %d and post_status = 'publish'", $source_post_id );

						// Increment the comment_count in the $target_post_id .
						$sql[] = $wpdb->prepare( "update {$wpdb->posts} set comment_count = comment_count+1 where id = %d and post_status = 'publish'", $target_post_id );
					} else {
						$sql[] = $wpdb->prepare( "update {$wpdb->comments} set comment_post_id = %d , comment_parent = '0' where comment_id IN ($d1->comment_ID)", $target_post_id );

						// Decrement the comment_count in the $source_post_id .
						$sql[] = $wpdb->prepare( "update {$wpdb->posts} set comment_count = comment_count-1 where id = %d and post_status = 'publish'", $source_post_id );

						// Increment the comment_count in the $target_post_id .
						$sql[] = $wpdb->prepare( "update {$wpdb->posts} set comment_count = comment_count+1 where id = %d and post_status = 'publish'", $target_post_id );
					}
				}

				foreach ( $sql as $query ) {
					$wpdb->query( $query );
				}
			} else {

				$sql[] = $wpdb->prepare( "update {$wpdb->comments} set comment_post_id = %d where comment_id IN ($comment_id)", $target_post_id );

				// Decrement the comment_count in the $source_post_id .
				$sql[] = $wpdb->prepare( "update {$wpdb->posts} set comment_count = comment_count-%d where id = %d and post_status = 'publish'", $comment_cnt, $source_post_id );

				// Increment the comment_count in the $target_post_id .
				$sql[] = $wpdb->prepare( "update {$wpdb->posts} set comment_count = comment_count+%d where id = %d and post_status = 'publish'", $comment_cnt, $target_post_id );

				foreach ( $sql as $query ) {
					$response[] = $wpdb->query( $query );
				}
			}
		}
		if ( 'copy' === $get_action_type ) {

			if ( isset( $get_comment_type ) && $get_comment_type == '1' ) {
				$all_comments = $wpdb->get_results( "select * from $wpdb->comments where comment_id IN ($comment_id)" );
				foreach ( $all_comments as $data1 ) {

					$data_new = array(
						'comment_post_ID'      => $target_post_id,
						'comment_author'       => $data1->comment_author,
						'comment_author_email' => $data1->comment_author_email,
						'comment_author_url'   => $data1->comment_author_url,
						'comment_content'      => $data1->comment_content,
						'comment_type'         => $data1->comment_type,
						'comment_parent'       => '0',
						'user_id'              => $data1->user_id,
						'comment_author_IP'    => $data1->comment_author_IP,
						'comment_agent'        => $data1->comment_agent,
						'comment_date'         => $data1->comment_date,
						'comment_approved'     => $data1->comment_approved,
					);

					wp_insert_comment( $data_new );
				}
			} else {
				$all_comments = $wpdb->get_results( "select * from $wpdb->comments where comment_id IN ($comment_id)" );
				foreach ( $all_comments as $data1 ) {

					$data = array(
						'comment_post_ID'      => $target_post_id,
						'comment_author'       => $data1->comment_author,
						'comment_author_email' => $data1->comment_author_email,
						'comment_author_url'   => $data1->comment_author_url,
						'comment_content'      => $data1->comment_content,
						'comment_type'         => $data1->comment_type,
						'comment_parent'       => $data1->comment_parent,
						'user_id'              => $data1->user_id,
						'comment_author_IP'    => $data1->comment_author_IP,
						'comment_agent'        => $data1->comment_agent,
						'comment_date'         => $data1->comment_date,
						'comment_approved'     => $data1->comment_approved,
					);

					$new_comment_ids[] = wp_insert_comment( $data );
				}

				{
					$count_all_ids = count( $all_ids );
				for ( $i = 0; $i < $count_all_ids; $i++ ) {
					$sql2[ $i ] = "update {$wpdb->comments} set comment_parent = " . $new_comment_ids[ $i ] . ' where comment_post_id = ' . $target_post_id . ' and comment_parent = ' . $all_ids[ $i ] . '';
					$wpdb->query( $sql2[ $i ] );
				}
				}
			}
		}
	}

	/** Get all comments data when select comment type
	 *
	 * @param int $id give all comment ids .
	 */
	public function get_single_comment( $id ) {
		$data = array();
		if ( is_numeric( $id ) ) {
			$data = get_comment( $id );
		}
		return $data;
	}
}
