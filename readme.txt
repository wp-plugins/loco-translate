=== Plugin Name ===
Contributors: timwhitlock
Tags: translation, translators, localization, localisation, l10n, i18n, Gettext, POEdit, productivity
Requires at least: 3.5
Tested up to: 3.5
Stable tag: 1.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Translate Wordpress plugins and themes directly in your browser


== Description ==

The Loco Translate plugin provides in-browser editing of PO files used for localizing Wordpress plugins and themes.

Features include:

* POEdit style translations editor within Wordpress admin
* Extraction of translatable strings from your source code
* Create and update langauge files directly in your project

Built by <a href="//twitter.com/timwhitlock">@timwhitlock</a> / <a rel="author" href="https://plus.google.com/106703751121449519322">Tim Whitlock</a>. Official [Loco](https://localise.biz/) WordPress plugin.



== Installation ==

1. Unzip all files to the `/wp-content/plugins/loco-translate` directory
2. Log into Wordpress admin and activate the 'Loco Translate' plugin through the 'Plugins' menu
3. Go to *Tools > Manage Translations* in the left-hand menu to start translating


If you want to create new translations for a theme or plugin, follow these steps:

1. Create a `languages` directory in your plugin or theme's root directory
2. Make the new directory writable by the web server
3. Find the theme or plugin in the list at *Tools > Manage Translations*
4. Click `+ New language` and follow the on-screen prompts.

Make sure you're familiar with the conventions of [Translating Wordpress](http://codex.wordpress.org/Translating_WordPress) before you start.

Please note that this plugin doesn’t support Windows servers.


== Frequently Asked Questions ==

= Does this automatically translate my project? =

No. It's for manually entering your own translations, but we do intend to be integrating some automatic translation services into the plugin soon.


= Why can't it extract any translatable strings from my code? =

The extraction process looks fo Wordpress translation functions with string literal arguments, such as `__('Foo')`.

Using your own custom functions like `myTranslate('Foo')` won't work. Neither will using variables, such as `__( $foo )`.


= Do I need to create a POT file? =

There are some good reasons to, but you don't have to in order to use this plugin.

Loco Translate allows you to work purely with PO files and keep them up to date with the source code without the interim step of maintaining a POT file.


= Why do I get errors when I try to save files? =

To be able to save PO files directly to your project, the files must be writable by the web server. 

You shouldn't do this in a live server, only for developing your theme or plugin on a local server.

If you're unsure how to set file permission on your server, ask your systems administrator. 


= How do I create MO files? =

If you have [Gettext](http://www.gnu.org/software/gettext/) installed on your system, Loco Translate will automatcally create a MO file when you save a PO file.

Ensure that the web server is able to write the file to disk, and also ensure that the `msgfmt` program is in a common location, such as `/usr/bin/msgfmt`.


= Does it support Windows? =

At the user end, yes you can access the interface on Windows using Internet Explorer. But Loco Translate does not support Windows versions of PHP, so if your server running Wordpress has a Windows operating system the back end won’t work.



== Screenshots ==

1. Translating strings in the browser with the Loco PO Editor
2. Listing of all available translation files installed



== Changelog ==

= 1.3 =
* Last-Translator header added to PO files from Wordpress user
* Support for files under WP_LANG_DIR
* Disabling cache when WP_DEBUG = true
* Better POEdit integration, including source headers and file refs

= 1.2.2 =
* Fixed incorrect plural equation offset
* Compacted pre-compiled locale data
* Added settings link from plugin meta row

= 1.2.1 =
* Fixed incorrect version update message
* Added note about Windows support in readme.txt

= 1.2 =
* Added settings screen with gettext config
* Fixed msgfmt hanging bug

= 1.1.3 =
* Improved PHP strings extraction
* Fixed strict warning

= 1.1.2 =
* Added dutch translations
* Better persistence of PO headers

= 1.1.1 =
* Added country flag icons
* Fixed major IE8 bug in editor search
* Reduced size of icon font file

= 1.1.0 =
* Added translation search filter in editor
* Added percentage completion in list and edit views


= 1.0.0 =
* First version published


== Upgrade Notice ==

= 1.2 =
* Bug fixes and improvements.



== Coming soon ==

These features are on our todo list. There's no particular timeframe for any of them and they're in no particular order:

* Integration with Google and Bing for automatic translation
* Full, but optional integration with Loco for collaborative translation
* Support multiple pairings of POT and PO files within a single package.


== Credits ==

* Dutch translations courtesy of [Niels Geryl](http://hetwittepaard.be)
