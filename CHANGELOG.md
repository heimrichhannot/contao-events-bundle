# Changelog
All notable changes to this project will be documented in this file.

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
- added class check for checking if contao-calendar_plus is installed, 
if installed the dca fields in tl_calendar_events will not be replaced
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
