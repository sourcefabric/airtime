# Information on Forking this Repository

Some work has been put into making this easier to fork. Due to the nature of the
AGPL, forking is more or less mandated as we need to contribute our changes. The
lack of a merging upstream has led to a situation where a lot of forks exist
in a competing space. The forking support was implemented by [Radio Bern Rabe](http://rabe.ch)
as part of the [RaBe Airtime Fork](https://github.com/radiorabe/airtime) to 
help in testing various forks. It makes it easy to customize a fork to a degree
where we can recognize the difference between a fork and upstream.

We plan on adding more features to the fork detection as the need arises.

The places where Airtime displays version and application name information
are at the beginning of the installer as well as in the about page.

At the moment the installer does not get overridden since we have no interest
in it. This would be easy to implement though.

## Configuration

You can configure your fork by creating the file `airtime_mvc/application/configs/constants.fork.php`
and defining constants.

```php
<?php
define('AIRTIME_FORK_VERSION', '0.0.0');
define('AIRTIME_FORK_NAME', 'Airtime Fork');
define('AIRTIME_FORK_URL', 'https://github.com/<your-username>/airtime');
define('AIRTIME_FORK_DESCRIPTION', 'fork feature blurb for about page');
```

You need to define all of the above constants or the feature will trigger notice errors
and the about page will 404 on you.

## About Page

The about page on `/dashboard/about` will now display the following additional line.

```
<a href="AIRTIME_FORK_URL">AIRTIME_FORK_NAME</a> AIRTIME_FORK_VERSION, AIRTIME_FORK_DESCRIPTION
```
