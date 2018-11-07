# Contao Events Bundle

This bundle offers useful functionality concerning the entities `tl_calendar` and `tl_calendar_events` of the Contao CMS.

## Features

- adds the new entity `tl_calendar_sub_events` for creating sub events (e.g. workshops, sessions, ...) for a given calendar event
- adds an `Item` class for the generic reader bundle [heimrichhannot/contao-list-bundle](https://github.com/heimrichhannot/contao-list-bundle) and the generic list bundle [heimrichhannot/contao-list-bundle](https://github.com/heimrichhannot/contao-list-bundle)
- adds a dateAdded field for `tl_calendar_events`
- adds the `feature` operation to `tl_calendar_events` (and `tl_calendar_sub_events`) as already existing in `tl_news`

## Installation

Install via composer: `composer require heimrichhannot/contao-events-bundle` and update your database.

## Configuration

### About sub events

The `tl_calendar_sub_events` dca is created by copying the dca of `tl_calendar_events` and resetting some callbacks. See `tl_calendar_sub_events.php` for more details on that.