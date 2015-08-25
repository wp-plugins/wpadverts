=== WPAdverts - Classifieds Plugin ===
Plugin URI: http://wpadverts.com/
Contributors: gwin
Tags: ads, classified, classified ads, classified script, classifieds, classifieds script, wp classified, wp classifieds
Requires at least: 4.0
Tested up to: 4.3
Stable tag: 1.0.1
License: GNU Version 2 or Any Later Version

Build classifieds section in seconds. Allow your visitors to browse and post (paid or free) classified ads on your site.

== Description ==

WPAdverts is a lite weight plugin which allows to build beatufiul classifieds site in minutes. Plugin will work with ANY properly
coded WordPress theme and you can use it on new or existing site.

When building Adverts we are focusing on ease of use, WordPress compatibility and extensibility, the plugin core is small but this
is NOT a demo, this is a fully functional classifieds software with most crucial functionalities builtin.

**Links**

* [WordPress Classifieds Plugin](http://wpadverts.com/) - official site.
* [View User Demo](http://demo.wpadverts.com/) - see plugin frontend.
* [View Admin Demo](http://demo.wpadverts.com/wp-admin/) - see wp-admin panel.
* [Documentation](http://wpadverts.com/documentation/) - mainly for users.
* [Code Snippets](https://github.com/gwin/wpadverts-snippets) - for programmers and developers.

**Noatble Features**

* Modern, responsive design.
* Intuitive ads browsing and searching.
* Display categories grid.
* Allow (registered and/or anonymous) users to post Ads.
* Payments Module to track user payments and transactions logs.
* Bank Transfer payment gateway included.
* Easy to use drag and drop image upload.
* Ads will automatically expire after set number of days.
* Detailed user and developer documenation.

See [Screenshots](https://wordpress.org/plugins/wpadverts/screenshots/) tab for full, visual features list.

**Extensions**

Adverts plugin can be extended with premium extensions. Currently we have two modules available
which will allow you to easily accept user payments.

* [PayPal Payments Standard](http://wpadverts.com/extensions/paypal-payments-standard/)
* [WooCommerce Integration](http://wpadverts.com/extensions/woocommerce-integration/)

**Get Involved**

* Wording - I am not English native speaker, if you find any typo, grammer mistake or etc. please report it on support forum.
* Translation - If you translated Adverts to your language feel free to submit translation.
* Rate Adverts - If you find this plugin useful please leave [positive review](https://wordpress.org/support/view/plugin-reviews/wpadverts).
* Submit a Bug - If you find some issue please [submit a bug](https://github.com/gwin/wpadverts/issues/new) on GitHub.

== Installation ==

1. Activate the plugin
2. On activation plugin will create two Pages (in wp-admin / Pages panel)  with [adverts_list] and [adverts_add] shortcodes .
3. Go to Classifieds / Options panel and configure the options.
3. For more detailed instructions visit plugin [documentation](http://wpadverts.com/documentation/)

== Frequently Asked Questions ==

= I have a problem what now? =

Please describe your issue and submit ticket on plugin support forum, you should receive reply within 24 hours (except Sunday).

= Ads pages are showing 404 error? =

Most likely rewrite rules were not registered properly for some reason. Go to wp-admin / Settings / Permalinks panel and click
"Save Changes" button without actually changing anything, this should reset router and fix URLs.

== Changelog ==

= 1.0.1 - 2015-08-25 =

* FEATURE: Improved UX in [adverts_manage]. When unregistered user tries to access this page he will see Login and Register links in addition to error message.
* FIXED: Removed the_editor_content filter.
* FIXED: Set default user role for registered user to Subscriber.
* FIXED: HTML updates for WP 4.3
* FIXED: Default listing type selection in [adverts_add]
* FIXED: In [adverts_add] correct post names are now generated instead of 'Adverts Auto Draft' 
* FIXED: Improved Gallery upload/edit/delete security.
* FIXED: Delete link not working on some installations with mod_security enabled.

= 1.0 - 2015-08-11 = 

* First Release *

== Screenshots ==

1. Default Ads list [adverts_list] (you can select how many columns to display).
2. Ad details page (compatible with all popular SEO plugins to boost your rankings).
3. Top Categories [adverts_categories show="top"] icons are configurable from wp-admin / Classifieds / Categories panel.
4. All Categories [adverts_categories].
5. Post an Ad Form [adverts_add] (allow anonymous and/or registered users to post ads).
6. Ads list in wp-admin panel.
7. Ad edit page in wp-admin panel.
8. Category edition with icon select.
9. Options, modules and premium extensions.
10. Payment history (if you are planning to charge users for posting Ads)
11. Payment details

== Upgrade Notice ==

= 1.0 - 2015-08-11 = 

* Just try it, you will like it.