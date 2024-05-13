﻿=== Profile Builder Pro ===

Contributors: reflectionmedia, sareiodata, cozmoslabs, madalin.ungureanu, iova.mihai, barinagabriel, adi.spiac, vadasan
Donate link: https://www.cozmoslabs.com/wordpress-profile-builder/
Tags: registration, profile, user registration, custom field registration, customize profile, user fields, builder, profile builder, custom profile, user profile, custom user profile, user profile page,
custom registration, custom registration form, custom registration page, extra user fields, registration page, user custom fields, user listing, user login, user registration form, front-end login,
front-end register, front-end registration, frontend edit profile, edit profileregistration, customize profile, user fields, builder, profile builder, custom fields, avatar
Requires at least: 3.1
Tested up to: 6.2
Stable tag: 3.9.2


Login, registration and edit profile shortcodes for the front-end. Also you can choose what fields should be displayed or add custom ones.


== Description ==

Profile Builder is WordPress registration done right.

**Like this plugin?** Consider leaving a [5 star review](https://wordpress.org/support/view/plugin-reviews/profile-builder?filter=5).

It lets you customize your website by adding a Front-end menu for all your users,
giving them a more flexible way to modify their user-information or register new users (front-end registration).
Also, grants users with administrator rights to customize basic user fields or add custom ones.

To achieve this, just create a new page and give it an intuitive name(i.e. Edit Profile).
Now all you need to do is add the following shortcode(for the previous example): [wppb-edit-profile].
Publish the page and you are done!

You can use the following shortcodes:

* **[wppb-edit-profile]** - to grant users front-end access to their personal information (requires user to be logged in).
* **[wppb-login]** - to add a front-end log-in form.
* **[wppb-register]** - to add a front-end registration form.
* **[wppb-recover-password]** - to add a password recovery form.

Users with administrator rights have access to the following features:

* add a custom stylesheet/inherit values from the current theme or use one of the following built into this plugin: default, white or black.
* select whether to display or not the admin bar in the front end for a specific user-group registered to the site.
* select which information-field can users see/modify. The hidden fields values remain unmodified.
* add custom fields to the existing ones, with several types to choose from: heading, text, textarea, select, checkbox, radio, and/or upload.
* add an avatar upload for users.
* create custom redirects
* front-end userlisting using the **[wppb-list-users]** shortcode.
* role editor: add, remove, clone and edit roles and also capabilities for these roles.
* private website functionality: restrict access to only logged in users

NOTE:

This plugin only adds/removes fields in the front-end. The default information-fields will still be visible(and thus modifiable) from the back-end, while custom fields will only be visible in the front-end.



== Installation ==

1. Upload the profile-builder folder to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create a new page and use one of the shortcodes available

== Frequently Asked Questions ==

= I navigated away from Profile Builder and now I can�t find it anymore; where is it? =

	Profile Builder can be found in the default menu of your WordPress installation below the �Users� menu item.

= Why do the custom WordPress fields still show up, even though I set it to be "hidden"? =

	Profile Builder only disables the default fields in the front-end of your site/blog, it does absolutely nothing in the dashboard.

= I entered the serial number I received in the confirmation e-mail, but the indicator still is red. What�s wrong? =

	The validation, as well as the update checks require an active internet connection. If you are currently working on a localhost, check your internet connection. If you, however, are on a live server and your serial number won't validate, it means that either our servers are/were down or your server blocked the validation request.

= I see that there is a heading in the Extra Profile Fields section of Profile Builder, but it isn�t displaying in the front-end, neither in the back-end. How come? =

	If you mean the default Header item "Extra Profile Fields", as long as you don't change the title, it won't show up.

= I deleted the default header from the Extra Profile Fields section, but when I refreshed the page, it was there again. Am I seeing things? =

	Luckily for you, you aren't imagining it! The plugin is designed in such way, that there must always be a header item in the list. But don't worry: your users won't see the default header.


= I can't find a question similar to my issue; Where can I find support? =

	For more information please visit https://www.cozmoslabs.com and check out the faq section from Profile Builder


== Screenshots ==
1. Plugin Layout (Control Panel): screenshot1.jpg
2. Show/Hide the Admin Bar (Control Panel): screenshot2.jpg
3. Default Profile Fields (Control Panel): screenshot3.jpg
4. Extra Profile Fields (Control Panel): screenshot4.jpg
5. Register Your Version (Control Panel): screenshot5.jpg
6. Edit Profile Page: screenshot6.jpg
7. Login Page: screenshot7.jpg
8. Registration Page: screenshot8.jpg
9. Customizable Userlisting (Control Panel): screenshot9.png
10.Userlisting: screenshot10.png



== Changelog ==
= 3.9.2 =
* Fix: issue between PMS pay what you want functionality and new Form Designs
* Fix: issue between 2FA functionality and new form designs

= 3.9.1 =
* Fix: issue between new form styles and Paid Member Subscriptions Billing Fields
* Fix: issue with Select2 Multiple occurring when field data was saved as serialized
* Misc: minor changes to Social Connect Settings page

= 3.9.0 =
* Fix: Issue with Userlisting not showing anything when the language is switched on a front-end page
* Fix: Some compatibility issues between Edit Profile Approve by Admin and the new Form Styles functionality
* Fix: Add labels functionality info to Form Designs
* Fix: Don't display multiple infoview windows on a map
* Fix: Fixed issue that caused the Display on WooCommerce Checkout option to be hidden for Repeater Fields
* Misc: Properly remove orphaned pending status when users are deleted

= 3.8.9 =
* Fix: Compatibility for Timepicker field with EPAA add-on
* Misc: Correctly set default active form style on initial installation

= 3.8.8 =
* Feature: You can now enable different Form Designs for all of your front-end forms. Go to Profile Builder -> General Settings to select your style.

= 3.8.7 =
* Fix: Issue where you had to press twice on the login button in order for it to work when 2FA was enabled
* Fix: Hide repeater fields with empty groups from front-end
* Fix: Issue with Upload field losing track of target file in some cases

= 3.8.6 =
* Fix: issue with EPAA emails edited from the back-end not applying when the message was sent

= 3.8.5 =
* Fix: Social Connect Google login. This requires you to add the Google Client Name on the Profile Builder -> Social Connect page
* Fix: Disable WooCommerce auto login if Admin Approval is enabled
* Misc: Update WooCommerce templates versions
* Misc: Added a filter that can be used to change the required capability to view the Mailchimp settings page: wppb_mailchimp_page_capability

= 3.8.4 =
* Fix: Issue with Select2 Multiple not loading correctly
* Fix: Role Faceted Menus will now only display the roles selected in Userlisting Settings

= 3.8.3 =
* Fix: issue with 2FA functionality and newer block themes
* Fix: simple upload fields issue on mobile and improve UI when waiting for the upload to process
* Fix: case where 2FA fields were duplicated in a scenario with Elementor

= 3.8.2 =
* Fix: issue with large icons and text Social Connect display option
* Fix: issue with {{approval_url}} tag for EPAA email notification that wasn't working in some cases

= 3.8.1 = 
* Feature: Added option to set the password recovery link on the login form that can be added on the WooCommerce account page
* Misc: compatibility changes

= 3.8.0 = 
* Fix: Small issues with Userlisting Themes loading
* Fix: Userlisting javascript error

= 3.7.9 = 
* Feature: added a Template Selector for Userlisting allowing you to choose different styles for your userlistings
* Fix: issue with Language field not saving the language correctly when Email Confirmation was enabled
* Fix: case where back-end fields were validated for the currently logged in user
* Misc: compatibility fixes for Datepicker field with a WooCommerce add-on

= 3.7.8 =
* Feature: Add support to restrict the BuddyPress Activity and Member pages with a redirect, through the Content Restriction feature
* Fix: Add compatibility between Custom Profile Menus iFrames and Max Mega Menu plugin
* Fix: Issue between Simple Upload Fields and Conditional Logic
* Misc: Add .ico image type in the default image types for Avatar fields

= 3.7.7 =
* Fix: cases where forms were submitted before the file in an simple upload field was uploaded
* Fix: don't show unnapproved users in the userlisting
* Fix: add back social connect option to bypass email confirmation

= 3.7.6 =
* Fix: make sure GDPR consent is properly stored when registering through Social Connect and Email Confirmation is active
* Misc: remove the Filter Media menu from the Upload field Media Gallery popup
* Misc: fix a random issue with Social Connect when platform was undefined

= 3.7.5 =
* Feature: added redirect option after Password Recovery form is submitted
* Fix: Hide the edit profile success message when switching Multi Step Forms tabs
* Fix: A compatibility issue between the Map field and Field Visibility add-on
* Fix: A warning coming from the WooCommerce functionality
* Fix: Issue with multiple Simple Upload fields in a single form
* Misc: Allow Select (User Role) field to work with the Edit Profile Updates Approved by Admin add-on

= 3.7.4 =
* Fix: Issue with Avatar and Upload fields not working
* Fix: Javascript error when using Conditional Fields with ajax
* Misc: Update confirmation notice text for Admin Approval Link emailed to admins

= 3.7.3 =
* Improve initial upgrade behaviour

= 3.7.2 =
* Initial release
