=== Flair Antispam ===
Contributors: bahson
Donate link: https://flaircore.com/flair-core/paypal_payment
Tags: antispam, spam-filtering, spam-detection, content moderation
Requires at least: 5.7
Tested up to: 6.0
Stable tag: 1.0.2
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Filter and unpublish contents (posts/comments) according to defined patterns and provides
a way to analyze the unpublished content.

== Description ==

This WordPress plugin provides a way to filter content according to the defined patters (words/phrases), and
sets post status to draft, and comment status to 0 if their contents match the defined words and or phrases.

You can filter contents by any rules, and for all roles in your WordPress website/application.

*USE CASES INCLUDE:*
* You run multiple sites associated with multiple brands, and don't want to mix brand names in the
contents of each site (content editor).
* Don't want users to use certain words/phrases in posts and comments.

== Installation ==

1.  Install via the WordPress plugin repository or download and place in /wp-content/plugins directory
2.  Activate the plugin through the \'Plugins\' menu in WordPress
3.  See this plugin's configuration section to set the words/phrases to filter.

== Configuration ==
* From your WordPress Admin Dashboard, click on the Settings tab, to expand it's
details and then click on "Flair Antispam Config" menu item, to reveal the configuration
form, where you input the words or phrases to search for in comments or posts.
eg for words like; wolf, moon, woof, @gmail you will enter something like: wolf,moon,woof,@gmail

== Frequently Asked Questions ==

== Screenshots ==
1. Configuration form example view.

== Changelog ==

= 1.0.0 =
First version

= 1.0.1 =
Add screenshots

= 1.0.2 =
Added do_actions, when such content is posted so other plugins can build on that. see docs.md for more.