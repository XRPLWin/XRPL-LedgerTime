![main workflow](https://github.com/XRPLWin/XRPL-LedgerTime/actions/workflows/main.yml/badge.svg)
[![GitHub license](https://img.shields.io/github/license/XRPLWin/XRPL-LedgerTime)](https://github.com/XRPLWin/XRPL-LedgerTime/blob/main/LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/xrplwin/xrpl-ledgertime.svg?style=flat)](https://packagist.org/packages/xrplwin/xrpl-ledgertime)

# XRPL Ledger Time

This package will extract ledger index by giving date and time as input.

Basically `2023-04-13 15:00:00 UTC` will give you `79077871`

## Install

```
composer require xrplwin/xrpl-ledgertime
```

## Why?

There is no available XRPL API method to extract exact time of ledger. Say you want to query list of account transactions between Jan 1, 2022 08:00:00 and 12:00:00. For that query you need to provide in `account_tx` method `ledger_index_min` and `ledger_index_max`.

You can lookup local pre-synced database to get required ledger indexes and add them to query.

You can sync and store daily starting ledgers, hourly starting ledgers, etc. Periods you will sync depends of your business needs.

## Fair use notice

**Always pre-sync times and store them in local or shared database!**

DO NOT use this script to query XRPLedger live in production site. This script extracts single ledger datetime by making multiple connections to XRPL Rest API. Use background sync job to pre-sync ledger times.

## System time

A rippled server relies on maintaining the correct time. It is recommended that the your system synchronize time using the Network Time Protocol (NTP) with daemons such as ntpd or chrony.

## Usage

Option 1 (datetime to ledgerindex)
```PHP
use XRPLWin\XRPLLedgerTime\XRPLLedgerTimeSyncer;
use Carbon\Carbon;

$syncer = new XRPLLedgerTimeSyncer(); //init syncer

$datetime = Carbon::create(2023, 4, 13, 15, 0, 0, 'UTC'); //create Carbon datetime object
$ledgerIndex = $syncer->datetimeToLedgerIndex($datetime); //will return: 79077871
```

Option 2 (ledgerindex to datetime)
```PHP
use XRPLWin\XRPLLedgerTime\XRPLLedgerTimeSyncer;

$syncer = new XRPLLedgerTimeSyncer(); //init syncer
$carbon = $syncer->ledgerIndexToCarbon(79077871);
```

Options overview:
```PHP
$syncer = new XRPLLedgerTimeSyncer(
  [
    # Following values are defined by default, uncomment to override
    //'ledgerindex_low' => 0
  ],
  [
    # Following values are defined by default, uncomment to override
    # These options will be passed to \XRPLWin\XRPL\Client
    //'endpoint_fullhistory_uri' => 'https://xrplcluster.com'
  ]
); 
```

## Running tests
Run all tests in "tests" directory.
```
composer test
```
or
```
./vendor/bin/phpunit --testdox
```
