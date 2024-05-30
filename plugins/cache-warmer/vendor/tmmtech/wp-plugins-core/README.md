# About

A core library for TMM WordPress plugins which contains the common features and APIs.

------

## Notes

1. Plugin admin scripts should have `tmm-wp-plugins-core-admin-style` and `tmm-wp-plugins-core-admin-script`
as a dependency because it comes with some useful (and used) JS libs (listed below).

## Features

Contains the following features.

| Feature        |
|----------------|
| Notifications  |
| Tabs           |
| Setting Fields |

## Libs

Comes bundled with the following libs that are required for the core to work correctly. 

Also, these libs can be used by the plugins themselves.

### PHP

| Name                                                                | Description       | Required for  |
|---------------------------------------------------------------------|-------------------|---------------|
| [action-scheduler](https://github.com/woocommerce/action-scheduler) | Action Scheduler. | Notifications |

### JS

| Name                                             | Description                                                    | Installation | Required for  |
|--------------------------------------------------|----------------------------------------------------------------|--------------|---------------|
| [sweetalert2](https://sweetalert2.github.io/)    | SweetAlert2 - Replacement for JS popup boxes (alert, confirm). |              | Notifications |
| [Swiper](https://github.com/nolimits4web/swiper) | Swiper - Carousel. For drip notifications.                     | swiper       | Notifications |
