# Calendar Plugin for CakePHP #

## Alpha Status ##

This Plugin is personal test use.

## Features ##

* iCalendar (RFC2445) subset design
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
* SUMMARY
* DESCRIPTION
* LOCATION
* RRULE::FREQ
* RRULE::COUNT
* RRULE::UNTIL
* RRULE::BYDAY (wip)
* CREATED

## License ##

under MIT License
