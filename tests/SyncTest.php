<?php declare(strict_types=1);

namespace XRPLWin\XRPLLedgerTime\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLLedgerTime\XRPLLedgerTimeSyncer;
use Carbon\Carbon;

final class SyncTest extends TestCase
{
  public function testExactLedger()
  {
    $syncer = new XRPLLedgerTimeSyncer();

    //This is exact time of closing of ledger index: 79077871
    $datetime = Carbon::create(2023, 4, 13, 15, 0, 0, 'UTC');
    $ledgerIndex = $syncer->datetimeToLedgerIndex($datetime);

    $this->assertEquals(79077871,$ledgerIndex);
  }

  public function testLatestLedger()
  {
    $syncer = new XRPLLedgerTimeSyncer();

    //Ledger index 79077872 was closed on 2023-04-13 15:00:01 UTC
    //Next closed ledger index 79077873 is closed on 2023-04-13 15:00:10 UTC
    //We will set reference datetime to 2023-04-13 15:00:05 and should get 79077872 in response
    $datetime = Carbon::create(2023, 4, 13, 15, 0, 5, 'UTC');
    $ledgerIndex = $syncer->datetimeToLedgerIndex($datetime);
    $this->assertEquals(79077872,$ledgerIndex);
  }
}