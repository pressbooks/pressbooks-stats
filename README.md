# Pressbooks Stats

[![Packagist](https://img.shields.io/packagist/l/pressbooks/pressbooks-stats.svg)](https://packagist.org/packages/pressbooks/pressbooks-stats) [![Build Status](https://travis-ci.org/pressbooks/pressbooks-stats.svg?branch=dev)](https://travis-ci.org/pressbooks/pressbooks-stats) [![Current Release](https://img.shields.io/github/release/pressbooks/pressbooks-stats.svg)](https://github.com/pressbooks/pressbooks-stats/releases/latest/) [![Packagist](https://img.shields.io/packagist/v/pressbooks/pressbooks-stats.svg)](https://packagist.org/packages/pressbooks/pressbooks-stats) [![Packagist](https://img.shields.io/packagist/dt/pressbooks/pressbooks-stats.svg)](https://packagist.org/packages/pressbooks/pressbooks-stats)


A Pressbooks plugin which provides some basic activity statistics for a Pressbooks network.

## Composer

From within your WordPress or Bedrock root directory, run:

`composer require pressbooks/pressbooks-stats`

## Caching

To cache stats for a network, run (with [wp-cli](https://wp-cli.org)): `wp eval-file bin/cache.php`. You can set up a cron job for this if you want.

## Changelog

### 1.6.2

#### Patches

- Prevent cache stampedes: [#17](https://github.com/pressbooks/pressbooks-stats/pull/17)
- Add PB_DISABLE_NETWORK_STORAGE constant: [#18](https://github.com/pressbooks/pressbooks-stats/pull/17)

### 1.6.1

#### Patches

- Handle symlinked directories when displaying network storage: [#13](https://github.com/pressbooks/pressbooks-stats/pull/13)

### 1.6.0

#### Minor Changes

- Add network storage usage to network dashboard: [#11](https://github.com/pressbooks/pressbooks-stats/pull/11)
