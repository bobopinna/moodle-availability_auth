moodle-availability_auth
======================================

Restrict access based on user authentication method.

This plugin only pops up when there is more than 1 authentication method is used in the system 
(obvious, we need a least 2 authentication method to restrict)

Check the global documentation about conditional availability conditions:
   https://docs.moodle.org/en/Conditional_activities_settings

Installation:

1. Unpack the zip file into the availability/condition/ directory. A new directory will be created called
   auth.
2. Go to Site administration > Notifications to complete the plugin installation.

TODO:
* Mobile app.

DONE:
* MOODLE_10_STABLE language compability
* Standard hidden for participants
* Behat testing
* Unit testing
* userid bug
* MOODLE_37_STABLE code coverage changes
* GPL 3 licence
* GDPR


[![Build Status](https://github.com/bobopinna/moodle-availability_auth/workflows/Tests/badge.svg)](https://github.com/ewallah/moodle-availability_language/actions)
