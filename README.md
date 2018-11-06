# Contao Events Bundle

This bundle offers useful functionality concerning the entities `tl_calendar` and `tl_calendar_events` of the Contao CMS.

## Features

- adds the new entity `tl_calendar_sub_event`

## Installation

Install via composer: `composer require heimrichhannot/contao-events-bundle` and update your database.

## Configuration

### About sub events

The `tl_calendar_sub_event` dca is created by copying the dca of `tl_calendar_events` and resetting some callbacks. See `tl_calendar_sub_event.php` for more details on that.