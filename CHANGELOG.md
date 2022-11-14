# Changelog

All notable changes to this project will be documented in this file.

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

