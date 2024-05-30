# About

A WordPress plugin that visits website pages to warm (create) the cache if you have any caching solutions configured.

## Crawl limit explanation

Crawl limit explanation:

When encounters the error for first time, limit from the base speed to /2, then if encountered second time within this limit, limit /8 from the base speed. Then if encounters the error for third time (during the 15 minutes of the second (/8 limit)), pause the warming for an hour.

So this is like a progression (or combo streak in a video game): `/2 -> /8 -> pause for an hour`. To get to the second "level" you need to encounter the error during the 15 munutes timeframe from the time the previous error was encountered. As soon as the last "level" is reached (i.e. pause for an hour), it resets to the base speed (no limit), but the crawl with that (normal) speed will continue in an hour, when the pause is lifted off.

Base speed is calculated is the avg speed by the moment the first 429 or 500 error encountered, and then kept this way until the end of the warm, without recalculation (because otherwise it would be distorted by the previous limits from the "real" speed, so the real speed is considered only the first calculation, when no speed limits were applied yet). Calculated as the number of pages divided by the duration.



------

## Env variables

| Variable                             | Description                                                                   |
|--------------------------------------|-------------------------------------------------------------------------------|
| `CACHE_WARMER_DEBUG`              | Debug mode is enabled.                                                        |
| `CACHE_WARMER_DEBUG_EACH_REQUEST` | Debug each request (by default, only 1 10th due to the potentially big size). |

## Libs

### PHP

| Name                                                              | Description                                             |
|-------------------------------------------------------------------|---------------------------------------------------------|
| [PHPUri](https://github.com/monkeysuffrage/phpuri)                | A php library for converting relative URLs to absolute. |
| [vipnytt/sitemapparser](https://github.com/VIPnytt/SitemapParser) | Sitemap parser.                                         |

### JS

| Name                                                     | Description                                                                     | Installation |
|----------------------------------------------------------|---------------------------------------------------------------------------------|--------------|
| [ApexCharts](https://apexcharts.com/)                    | ApexCharts - to display loading time graph.                                     |              |
