# Common Libraries

This project contains many useful libraries that are currently used and can be reused across our projects. They are kept here for easy maintenance and also so that consumers get a uniform interface and things dont break across versions or on updates.

## Using the theme

The compiled theme is present in the root `updraft-theme` folder.

In the project you will be using the library like this -

- Just include the main entry point PHP file -

  ```php
      if (!class_exists('TU_Theme')) require_once(UD_CENTRAL_DIR.'/vendor/team-updraft/common-libs/updraft-theme/theme.php');
  ```

- Then just initialize the library by calling -

  ```php
    TU_Theme::instance();
  ```

- There's a filter available filter to restrict adding the `updraft-theme` colors. As soon as you disable that, all the components will be discolored. In your project you have to add a CSS to add all those variables with the same name with the colors of your project you want.

  ```php
    // Disable loading theme colors.
    add_filter('tu_theme_load_colors', '__return_false');
  ```

  By default it is true and theme colors will be loaded.

- Components: Each component has a documentation in its own folder, on how to use it.

CHANGELOG

- TWEAK: Port from previous semaphore classes to Updraft_Semaphore_3_0 in updraft-tasks
- FIX: Wrong query value in `delete_task_meta` method
- TWEAK: Make the logging format uniform
- FIX: Wrong DB Schema reference
- TWEAK: Logging on the semaphore
