Configuration Manager [by [Gabriel Zanetti][author]]
====================================================

Description
-----------

Configuration Manager is a [Question2Answer][Q2A] plugin that allows users to import and export Q2A configuration.

Features
--------

 * Export Q2A configuration
    * Several export options to limit exporting secrets, emails, URLs, etc
 * Import Q2A configuration
 * Internationalization support
 * No need for core hacks or plugin overrides
 * Simple installation

Bear in mind that:
 * The main purpose of the plugin is not to backup settings but rather serve as a simple way to share configuration with other users. This is relevant when trying to identify issues
 * The plugin will not export or import any setting that is not part of the Q2A core or the plugins distributed with it

Requirements
------------
 * Q2A version 1.8.0+
 * PHP 7.0.0+

Installation instructions
-------------------------

 1. Copy the plugin directory into the `qa-plugin` directory
 2. Enable the plugin from the *Admin -> Plugins* menu option
 3. Click on the `Save` button

Support
-------

If you have found a bug then create a ticket in the [Issues][issues] section.

Get the plugin
--------------

The plugin can be downloaded from [this link][download]. You can say thanks [donating using PayPal][paypal].


[Q2A]: https://www.question2answer.org
[author]: https://question2answer.org/qa/user/pupi1985
[download]: https://github.com/pupi1985/q2a-pupi-coma/archive/master.zip
[issues]: https://github.com/pupi1985/q2a-pupi-coma/issues
[paypal]: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=Y7LUM6ML4UV9L
