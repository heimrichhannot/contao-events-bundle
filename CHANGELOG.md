# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

These changes are maintained in the fork's `contao53` branch. The release version
and release date are left to the upstream maintainers.

### Changed

- Require PHP 8.2+, Contao 5.3+ (5.x) and Heimrich & Hannot Utils Bundle 3.13+.
- Replace removed Contao class aliases, insert-tag helpers and Utils Bundle 2 services with supported APIs.
- Use Contao's native visibility and featured toggle operations.
- Check access to sub-events and their content through security voters using the parent event's calendar permissions, including both parents when moving a sub-event.
- Adapt the copied sub-event DCA to Contao 5.3, including content navigation and callbacks.
- Retrieve coordinates through Symfony HttpClient while preserving the existing `utilsGoogleApiKey` setting and the option to disable retrieval.
- Maintain creation timestamps through bundle-owned callbacks.
- Render event content through Contao's content elements and use a bundle-owned image template instead of the removed Utils template.
- Replace the obsolete Travis CI configuration with GitHub Actions and update PHPUnit to version 10.5.

### Fixed

- Add missing English labels and help texts for event location and contact fields, the short title, custom event palettes, automatic coordinate retrieval and their legends; correct the parent-event and create-sub-event descriptions.
- Add English and German labels for the sub-events backend module.

- Query `tl_calendar_sub_events.pid` when loading sub-events in entity mode.
- Resolve a sub-event's calendar through its parent event for SERP URLs and page titles, and restore the page context after rendering the title.
- Register the coordinates save callback as a nested callback array.
- Use the event start time and Contao's description field for reader metadata, correct the page-title fallback and guard optional metadata fields.
- Correct enclosure handling and replace removed frontend URL and HTML helpers.

### Added

- Regression tests for sub-event queries, access checks, coordinate retrieval, service definitions and optional list configuration.
- An isolated Contao 5.3 kernel smoke test covering DCA loading, callback resolution and Twig template compilation.
- Upgrade requirements and validation instructions in the README.

### Removed

- Support for Contao 4, PHP versions below 8.2 and Utils Bundle 2.
- Legacy Ajax toggle hooks and permission callbacks. Custom integrations using these callbacks must migrate to Contao's data container operations and security voters.

## [1.9.4] - 2024-11-12
- Fixed: skip adding meta tags when head bundle is not installed
- Fixed: do not use invalid insert tag in meta tags

## [1.9.3] - 2022-11-14
- Fixed: removed usage of non existing method

## [1.9.2] - 2022-05-17
- Fixed: utils bundle dependency

## [1.9.1] - 2022-05-17
- Fixed: issues with contao 4.13

## [1.9.0] - 2022-05-05
- Changed: dropped contao 4.4 and symfony 3 support
- Fixed: symfony 5 support

## [1.8.1] - 2022-04-27
- Fixed: contao 4.4 compatibility

## [1.8.0] - 2022-04-20
- Changed: allow php 8 ([#9], [@rabauss])
- Fixed: contao 4.10+ support ([#9], [@rabauss])

## [1.7.1] - 2021-12-21
- Fixed: issues with palette manipulation on tl_calender_events
- Fixed: missing contao/calendar-bundle dependency

## [1.7.0] - 2021-07-28

- deprecated CalendarEventsModel::getSubEvents and CalendarEventsModel::hasSubEvents (#6)
- added getSubEvents and hasSubEvents to EventsManager

## [1.6.1] - 2021-07-27

- fixed type declaration for lower php versions

## [1.6.0] - 2021-07-05

- fixed contao 4.9 issue for entity mode
- minor refactoring

## [1.5.4] - 2021-04-19

- fixed contao 4.9 issue

## [1.5.3] - 2021-04-14

- refactoring for upcoming symfony versions
- child_record_callback bug
- enhanced readme

## [1.5.2] - 2021-04-12

- enhanced readme

## [1.5.1] - 2021-02-03

- fixed palette manipulation in LoadDataContainerListener

## [1.5.0] - 2020-10-14

- refactored `tl_calendar_events`
- added LoadDataContainerListener for palette manipulation
- added `onSaveCoordinates` to CalendarEventsContainer

## [1.4.0] - 2020-09-23

- added support for multilingual events in model class

## [1.3.0] - 2020-09-22

- added support for multilingual content elements in `EventTrait`

## [1.2.0] - 2020-06-18

- removed urlCache in `EventsItemTrait` -> doesn't make sense if different `jumpToDetails` are set
- fixed canonical link generation

## [1.1.2] - 2020-06-03

- fixed robots in reader

## [1.1.1] - 2020-04-29

- added class check for checking if contao-calendar_plus is installed, if installed the dca fields in tl_calendar_events
  will not be replaced
  (preventing double replacing and duplicate fields)

## [1.1.0] - 2020-03-23

- added customizable palette; choosable at archive config

## [1.0.2] - 2020-01-30

- removed cut and copy from possible options for sub events

## [1.0.1] - 2020-01-08

- fixed id callback issue

## [1.0.0] - 2019-09-03

### Added

- initial state

[@rabauss]: https://github.com/rabauss

[#9]: https://github.com/heimrichhannot/contao-events-bundle/pull/9

