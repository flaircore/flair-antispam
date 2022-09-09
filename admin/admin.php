<?php

if (!defined('ABSPATH')) {
	exit();
}

function add_flair_antispam_settings_page(): void
{
	add_options_page(
            __('Flair Antispam plugin', "flair-antispam"),
            __('Flair Antispam Config', "flair-antispam"),
		'manage_options',
		'flair-antispam-settings-page',
		'flair_antispam_admin_index',
            null);
}


function flair_antispam_settings_init(): void
{
    // Setup settings section
	add_settings_section(
		'flair_antispam_settings_section',
		'Flair Antispam Settings Page',
		'',
		'flair-antispam-settings-page'
	);

	// Register form fields

	// app_id
	register_setting(
		'flair-antispam-settings-page',
		'flair_antispam_settings_phrases',
		array(
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default' => ''
		)
	);

	// Add settings fields
	add_settings_field(
		'flair_antispam_settings_phrases',
		__('Words/phrases to check for, comma separated', 'flair-antispam'),
		'flair_antispam_settings_phrases_callback',
		'flair-antispam-settings-page',
		'flair_antispam_settings_section'
	);
}

function flair_antispam_settings_phrases_callback(): void
{
	$options = get_option('flair_antispam_settings_phrases');
	?>
	<div class="flair-antispam-settings-phrases">
        <label for="flair_antispam_settings_phrases">
            Words or phrases to look for, comma separated, no spaces unless you want to include them too.
        </label>
        <br>
        <textarea
                rows="10"
                cols="55"
                name="flair_antispam_settings_phrases"
                id="flair_antispam_settings_phrases"
                placeholder="a bad word, another bad word, another one"
                class="regular-text"
        ><?php esc_html_e( $options, 'flair-antispam' ); ?></textarea>
        <div>
            Current pattern according to settings: '
            <?php
                $pattern = str_replace(',', '|', $options);
                $pattern = '/('.$pattern.')/i';
                esc_html_e( $pattern, 'flair-antispam' );
            ?> '
        </div>
    </div>


	<?php
}
function flair_antispam_admin_index(): void
{
	?>
	<div class="wrap">
		<form action="options.php" method="post">
			<?php

			// Security field
			settings_fields('flair-antispam-settings-page');

			// output settings section here
			do_settings_sections('flair-antispam-settings-page');

			// Save settings btn

			submit_button('Save words/phrases');

			?>
		</form>
        <div class="phrases-example-hints">
            <p>Example 1: wolf,moon,what will be converted to /(wolf|moon|what)/i</p>
            <p>Example 2: wolf, moon, what will be converted to /(wolf| moon| what)/i</p>
        </div>
	</div>
	<?php
}

add_action('admin_menu', 'add_flair_antispam_settings_page');
add_action('admin_init', 'flair_antispam_settings_init');
