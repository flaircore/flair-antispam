<?php

/**
 * Plugin Name:       Flair Antispam
 * Description:       Filter and unpublish contents (posts/comments) based of defined words/phrases and provides a way to analyze the spam content.
 * Requires at least: 5.7
 * Tested up to: 6.0
 * Requires PHP:      7.0
 * Version:           1.0.2
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

if (!class_exists('Flair_AntiSpam')) {
	class Flair_AntiSpam {

		private $pattern;

		public function __construct() {

			add_action( 'save_post', array($this, 'is_spam_post'), 10, 3 );
			add_action( 'comment_post', array($this, 'is_spam_comment'), 10, 2 );

			register_activation_hook(__FILE__, array($this, 'create_tables'));

			register_deactivation_hook(__FILE__, array($this, 'drop_tables'));

			$this->flair_antispam_setup();
		}

		public function flair_antispam_setup() {
			if ( is_admin() ) {
				$plugin = plugin_basename(__FILE__);
				add_filter("plugin_action_links_$plugin", array($this, 'flair_antispam_settings_links'));
				require_once __DIR__ . '/admin/admin.php';
			}

			$phrases = get_option('flair_antispam_settings_phrases');
			$pattern = str_replace(',', '|', $phrases);
			$this->pattern = '/('.$pattern.')/i';
		}

		public function flair_antispam_settings_links($links) {
			$settings_link = '<a href="admin.php?page=flair-antispam-settings-page">Configuration</a>';
			$links[]       = $settings_link;
			return $links;
		}

		public function create_tables() {
			global $wpdb;
			$table = $wpdb->prefix . "flair_antispam";
			$charset = $wpdb->get_charset_collate();

			$msg_sql = "CREATE TABLE $table(
 		id mediumint NOT NULL AUTO_INCREMENT,
 		item_id varchar(200) NOT NULL,
 		item_type varchar(20) NOT NULL,
 		PRIMARY KEY (id)
 	    )$charset;";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($msg_sql);
		}

		public function drop_tables() {
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
			if ($status === 'publish' && ($content || $title)) {
				// unhook this function so it doesn't loop infinitely
				remove_action( 'save_post', array($this, 'is_spam_post'));
				global $wpdb;
				$table = $wpdb->prefix . "flair_antispam";

				if (!empty($this->does_match( $content )) || !empty($this->does_match( $title ))) {
					wp_update_post(array(
						'ID'    =>  $post_id,
						'post_status'   =>  'draft'
					));

					$this->create_entry( $post_id, 'post' );

					// Fires this event throughout the app.
					do_action('flair_antispam_post', $post);

				} else {
					$wpdb
						->delete( $table, array( 'item_id' => $post_id ), array( '%d' ) );
				}

				// re-hook this function
				add_action( 'save_post', array( $this, 'is_spam_post' ), 10, 3 );
			}

		}

		public function is_spam_comment( $comment_id, $comment_approved ) {

			if ( $comment_approved ) {
				$comment = get_comment( $comment_id );
				global $wpdb;
				$table = $wpdb->prefix . "flair_antispam";

				if (!empty($this->does_match($comment->comment_content))) {
					// unhook this function so it doesn't loop infinitely
					remove_action( 'save_post', array($this, 'is_spam_comment'));
					// If spam un-approve comment
					wp_set_comment_status( $comment_id, '0' );

					$this->create_entry( $comment_id, 'comment' );

					// re-hook this function
					add_action( 'comment_post', array( $this, 'is_spam_comment' ), 10, 2 );

					// Fires this event throughout the app.
					do_action('flair_antispam_post', $comment);
				} else {
					$wpdb
						->delete( $table, array( 'item_id' => $comment_id ), array( '%d' ));
				}
			}

		}

		private function create_entry($item_id, $item_type) {
			global $wpdb;
			$table = $wpdb->prefix . "flair_antispam";
			$where_array = array();
			$where_array[] = $wpdb->prepare( "item_type = %s", $item_type );
			$where_array[] = $wpdb->prepare( "item_id = %d", $item_id );
			$sql = "SELECT item_id FROM {$table}";
			$sql .= ' WHERE ' . join( ' AND ', $where_array);
			$saved = $wpdb->get_var($sql);

			if (!$saved) {
				$comment_data = array(
					'item_id' =>  $item_id,
					'item_type' => $item_type
				);
				$format = array( '%d', '%s' );
				// Write to table, so there's a separation from other Drafts according to other metrics.
				// This records a Draft as a result of the post/comment being spam.
				$wpdb->insert( $table, $comment_data, $format );
			}
		}

		public function does_match( $input ): array {
			preg_match_all( $this->pattern, $input, $matches );
			return $matches[0];
		}
	}

	$anti_spam = new Flair_AntiSpam();
}