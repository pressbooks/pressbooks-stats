# Pressbooks Stats

[![Packagist](https://img.shields.io/packagist/l/pressbooks/pressbooks-stats.svg)](https://packagist.org/packages/pressbooks/pressbooks-stats) [![Build Status](https://travis-ci.org/pressbooks/pressbooks-stats.svg?branch=dev)](https://travis-ci.org/pressbooks/pressbooks-stats) [![Current Release](https://img.shields.io/github/release/pressbooks/pressbooks-stats.svg)](https://github.com/pressbooks/pressbooks-stats/releases/latest/) [![Packagist](https://img.shields.io/packagist/v/pressbooks/pressbooks-stats.svg)](https://packagist.org/packages/pressbooks/pressbooks-stats) [![Packagist](https://img.shields.io/packagist/dt/pressbooks/pressbooks-stats.svg)](https://packagist.org/packages/pressbooks/pressbooks-stats)


A Pressbooks plugin which provides some basic activity statistics for a Pressbooks network.

## Composer

From within your WordPress or Bedrock root directory, run:

`composer require pressbooks/pressbooks-stats`

## Caching

To cache stats for a network, run (with [wp-cli](https://wp-cli.org)): `wp eval-file bin/cache.php`. You can set up a cron job for this if you want.

## Changelog

### 1.7.0

* See: https://github.com/pressbooks/pressbooks-stats/releases/tag/1.7.0
* Full release history available at: https://github.com/pressbooks/pressbooks-stats/releases

## Upgrade Notice
### 1.6.5
* Pressbooks Stats requires Pressbooks >= 5.34.1
