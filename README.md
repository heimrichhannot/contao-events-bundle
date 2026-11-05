# Contao Events Bundle

This bundle offers useful functionality concerning the entities `tl_calendar` and `tl_calendar_events` of the Contao CMS.

## Features

- adds support for sub events (e.g. workshops, sessions, ...) for a given calendar event in 2 different flavors:
    - via a new (and auto-generated) entity `tl_calendar_sub_events`
    - via declaring the parent-child-relation in `tl_calendar_events` instances
- adds an `Item` class for the generic reader bundle [heimrichhannot/contao-list-bundle](https://github.com/heimrichhannot/contao-list-bundle) and the generic list bundle [heimrichhannot/contao-list-bundle](https://github.com/heimrichhannot/contao-list-bundle)
- adds a dateAdded field for `tl_calendar_events`
- adds the `feature` operation to `tl_calendar_events` (and `tl_calendar_sub_events` if used) as already existing in `tl_news`
- adds some new fields (see below)
- Calendar event coordinates

## Requirements (branch `contao53`)

- PHP 8.2 or newer
- Contao 5.3 or newer within the 5.x series
- Heimrich & Hannot Utils Bundle 3.13 or newer

This branch no longer supports Contao 4 or Utils Bundle 2. Optional list, reader,
head and multilingual extensions must themselves support Contao 5 and Utils 3.
Their integration should be checked with the versions installed in your project.

After updating, clear the Contao cache and run `vendor/bin/contao-console contao:migrate`.
For automatic coordinates, configure the Google Maps API key in the Contao settings
(the existing `utilsGoogleApiKey` setting is preserved), or disable coordinate retrieval.

Visibility and featured operations now use Contao's native toggle fields. Access to
sub-events and their content is checked through the parent event's calendar permissions.
Custom integrations calling the removed legacy toggle/permission callbacks must use
Contao's data container operations and security voters instead.

### Development checks

```bash
composer update --no-plugins --no-scripts
vendor/bin/phpunit
php tests/smoke.php
```

The smoke test boots a separate Contao 5.3 kernel, loads the calendar and sub-event
DCAs, checks callback methods and compiles the Twig templates. It does not connect
to the project database or perform migrations.

## Installation

Install via composer: `composer require heimrichhannot/contao-events-bundle` and update your database.

## Configuration

### Sub events

You can activate sub events in the global Contao settings. Here you have the following 2 options. Which one you take depends on
if the sub events can be a separate entity (`tl_calendar_sub_events`) or need to be instances of `tl_calendar_events`.

In both cases, activate the appropriate checkbox in the global contao settings. Afterwards **clear the cache** and **update the database**.

#### Realization as an entity "tl_calendar_sub_events"

The `tl_calendar_sub_events` dca is created by copying the dca of `tl_calendar_events` and resetting some callbacks. See `tl_calendar_sub_events.php` for more details on that.

#### Realization using a parent-child-relation

A new field `parentEVent` is added to `tl_calendar_events`

### Coordinates

Can be disabled in settings.

### New fields

- dateAdded (auto-generated)
- website
- coordinates
- city
- postal
- street
- locationAdditional
- shortTitle
- featured
- subTitle
