# Calendar Plugin for CakePHP #

## Alpha Status ##

THIS PLUGIN IS PERSONAL TEST USE.

## Features ##

* iCalendar ([RFC2445](http://tools.ietf.org/html/rfc2445)) subset design
* Set event
* Expand events data  for calendar rendering.

## Installation ##

* First, put `calendar' directory on app/plugins in your CakePHP application
* Second, create schema

        cake schema create -path app/plugins/calendar/config/schema
* Third, add the following code in whichever controller you want to use calendar

        <?php
           var $uses = array('Calendar.Venvent');

## iCalendar (RFC2445) support ##

* DTSTART
* DTEND
 *  Daylong event
* SUMMARY
* DESCRIPTION
* LOCATION
* RRULE::FREQ
* RRULE::COUNT
* RRULE::UNTIL
* RRULE::BYDAY (wip)
* CREATED

## TODO ##

* More test.
* Date filter. (ex. holiday)
* Extra attribute support. (ex. user_id)

## License ##

under MIT License
