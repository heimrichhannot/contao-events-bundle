# Contao Events Bundle

This bundle offers useful functionality concerning the entities `tl_calendar` and `tl_calendar_events` of the Contao CMS.

## Features

- adds support for sub events (e.g. workshops, sessions, ...) for a given calendar event in 2 different flavors:
    - via a new (and auto-generated) entity `tl_calendar_sub_events`
    - via declaring the parent-child-relation in `tl_calendar_events` instances
- adds an `Item` class for the generic reader bundle [heimrichhannot/contao-list-bundle](https://github.com/heimrichhannot/contao-list-bundle) and the generic list bundle [heimrichhannot/contao-list-bundle](https://github.com/heimrichhannot/contao-list-bundle)
- adds a dateAdded field for `tl_calendar_events`
- adds the `feature` operation to `tl_calendar_events` (and `tl_calendar_sub_events` if used) as already existing in `tl_news`

## Installation

Install via composer: `composer require heimrichhannot/contao-events-bundle` and update your database.

## Configuration

### Sub events

You can activate sub events in the global Contao settings. Here you have the following 2 options. Which one you take depends on
if the sub events can be a separate entity (`tl_calendar_sub_events`) or need to be instances of `tl_calendar_events`.

#### Realization as an entity "tl_calendar_sub_events"

The `tl_calendar_sub_events` dca is created by copying the dca of `tl_calendar_events` and resetting some callbacks. See `tl_calendar_sub_events.php` for more details on that.

#### Realization using a parent-child-relation