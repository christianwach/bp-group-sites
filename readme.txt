=== BP Group Sites ===
Contributors: needle
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8MZNB9D3PF48S
Tags: buddypress, groups, sites, reading groups
Requires at least: 4.2.1
Tested up to: 4.8
Stable tag: 0.2.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Enables the creation of a many-to-many relationship between BuddyPress Groups and WordPress Sites in a Multisite context.



== Description ==

The *BP Group Sites* plugin enables the creation of a many-to-many relationship between *BuddyPress* Groups and *WordPress* Sites. This is useful when you have a *BuddyPress* network in which you want, for example, many groups to comment simultaneously on sites which they share access to.

The plugin is designed to work with [*CommentPress Core*](https://wordpress.org/plugins/commentpress-core/) "documents" (which are themselves complete sub-sites) so that there can be many reading groups for each document and many documents for each reading group.

This plugin was developed for [The Readers' Thoreau](http://commons.digitalthoreau.org/) where it enables multiple reading groups to collectively discuss the works of Henry David Thoreau.

### Requirements

This plugin requires a minimum of *WordPress 4.2.1* and *BuddyPress 2.0*.

### Plugin Development

This plugin is in active development. For feature requests and bug reports (or if you're a plugin author and want to contribute) please visit the plugin's [GitHub repository](https://github.com/christianwach/bp-group-sites).



== Installation ==

1. Extract the plugin archive
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Network activate the plugin through the 'Plugins' menu in WordPress Multisite Network Admin



== Changelog ==

= 0.2.5 =

* Restore BuddyPress activity stream filtering

= 0.2.4 =

* Fix inconsistency in group selection when comment form is moved

= 0.2.3 =

* Refine appearance on WordPress plugin repo

= 0.2.2 =

* Use cookie to store state of comment filters in CommentPress Core

= 0.2.1 =

* Comment authors can edit own comments
* Introduce widget listing group sites

= 0.2 =

* Add "read with" invitation functionality

= 0.1 =

Initial commit
