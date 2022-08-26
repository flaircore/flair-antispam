<?php

/**
 * Plugin Name:       Flair Antispam
 * Description:       Filter and unpublish spam contents (posts/comments) and provides a way to analyze the spam content.
 * Requires at least: 5.9
 * Requires PHP:      7.0
 * Version:           1.0.0
 * Author:            Nicholas Babu
 * Author URI:        https://profiles.wordpress.org/bahson/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       flair-antispam
 *
 * @package           flair-antispam
 */

if (!defined('ABSPATH')) {
	exit();
}

// Notes
// https://developer.wordpress.org/reference/hooks/save_post/
// https://developer.wordpress.org/plugins/plugin-basics/best-practices/#object-oriented-programming-method
if (!class_exists('Flair_AntiSpam')) {

	class Flair_AntiSpam {
		// @TODO load from admin settings
		const PATTERN = '/(wolf|woof|moon)/i';

		public function __construct() {

			add_action( 'save_post', array($this, 'is_spam_post'), 10, 3 );
			add_action( 'comment_post', array($this, 'is_spam_comment'), 10, 2 );

			register_activation_hook(__FILE__, array($this, 'create_tables'));

			register_deactivation_hook(__FILE__, array($this, 'drop_tables'));
		}

		public function create_tables()
		{
			global $wpdb;
			$table = $wpdb->prefix . "flair_antispam";
			$charset = $wpdb->get_charset_collate();

			$msg_sql = "CREATE TABLE $table(
 		id mediumint NOT NULL AUTO_INCREMENT,
 		item_id varchar(200) NOT NULL,
 		item_type varchar(20) NOT NULL,
 		PRIMARY KEY (id),
 		UNIQUE KEY item_id (item_id)
 	    )$charset;";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($msg_sql);
		}

		public function drop_tables()
		{
			global $wpdb;
			$tables = array(
				$wpdb->prefix . "flair_antispam",
				'another_one'
			);
			foreach ($tables as $table) {
				$sql = "DROP TABLE IF EXISTS $table";
				$wpdb->query($sql);
			}
		}

		public function is_spam_post( $post_id, $post, $update ) {
			$title = $post->post_title;
			$content = $post->post_content;
			$status = $post->post_status;
			if ($status === 'publish' && $content) {
				try {
					// unhook this function so it doesn't loop infinitely
					remove_action( 'save_post', array($this, 'is_spam_post'));
					global $wpdb;
					$table = $wpdb->prefix . "flair_antispam";

					if ($this->does_match($content) || $this->does_match($title)) {
						wp_update_post(array(
							'ID'    =>  $post_id,
							'post_status'   =>  'draft'
						));

						$saved = $wpdb->get_var(
							$wpdb->prepare("SELECT item_id FROM $table WHERE item_id = %d", $post_id)
						);
						if ( !$saved ) {
							$post_data = array(
								'item_id' =>  $post_id,
								'item_type' => 'post'
							);
							// write to also to separate from other Drafts $wpdb->prefix . "flair_antispam";
							$wpdb->insert($table, $post_data);
						}

					} else {
						$wpdb
							->delete( $table, array('item_id' => $post_id) );
					}

					// re-hook this function
					add_action( 'save_post', array($this, 'is_spam_post'), 10, 3 );
				} catch (\Throwable $ex) {
					dump($ex);
				}
			}

		}

		public function is_spam_comment( $comment_id, $comment_approved ) {

			if ( $comment_approved ) {
				$comment = get_comment( $comment_id );
				global $wpdb;
				$table = $wpdb->prefix . "flair_antispam";

				if ($this->does_match($comment->comment_content)) {
					// unhook this function so it doesn't loop infinitely
					remove_action( 'save_post', array($this, 'is_spam_comment'));
					// If spam un-approve comment
					wp_set_comment_status($comment_id, '0');

					$saved = $wpdb->get_var(
						$wpdb->prepare("SELECT item_id FROM $table WHERE item_id = %d", $comment_id)
					);

					if (!$saved) {
						$comment_data = array(
							'item_id' =>  $comment_id,
							'item_type' => 'comment'
						);
						// write to also to separate from other Drafts $wpdb->prefix . "flair_antispam";
						$wpdb->insert($table, $comment_data);
					}

					// re-hook this function
					add_action( 'comment_post', array($this, 'is_spam_comment'), 10, 2 );
				} else {
					$wpdb
						->delete( $table, array('item_id' => $comment_id) );
				}
			}

		}

		public function does_match($input): array {
			preg_match_all(self::PATTERN, $input, $matches);
			return $matches[0];
		}
	}

	$anti_spam = new Flair_AntiSpam();
}