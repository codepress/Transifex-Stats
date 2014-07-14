=== Transifex Stats ===
Contributors: tschutter, davidmosterd
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ZDZRSYLQ4Z76J
Tags: transifex, stats, translations, language, statitics, languages, api
Requires at least: 3.4
Tested up to: 3.9.1
Stable tag: 1.0.1

Displays your Transifex Project Statistics on your website.

== Description ==

Display the status of your translatios from Transifex on your website. It will give you visitors a quick overview of your translations and will let them start translating right away with the translate button.

You can also display a list of people that have contributed to your project.

**Related Links:**

* http://www.codepresshq.com/

== Screenshots ==

1. Transifex translation statistics as displayed on your page.
2. Settings page for your Transifex credentials.
3. Shortcode selector in your edit page screen.

== Installation ==

1. Upload codepress-transifex to the /wp-content/plugins/ directory
2. Activate Transifex Stats through the 'Plugins' menu in WordPress
3. Fill in your Transifex username and password on the settings page.
4. Go to your page and click the Transifex shortcode button in the toolbar.
5. Fill in your project slug, example: [transifex_stats project="transifex-stats"].
6. Or use the contributors shortcode, example: [transifex_contributors project="transifex-stats"]
7. You're done!

== Changelog ==

= 1.1 =
Statistics will display immediately
Added shortcode to display list of contributors of a project, example: [transifex_contributors project="transifex-stats"]

= 1.0.1 =

Added filter cpti_transifex_stats to control stats output

= 1.0 =

* Initial release.