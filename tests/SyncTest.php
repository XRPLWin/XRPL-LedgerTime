<?php declare(strict_types=1);

namespace XRPLWin\XRPLLedgerTime\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLLedgerTime\XRPLLedgerTimeSyncer;

final class SyncTest extends TestCase
{
  public function testCreateInstance()
  {
    $syncer = new XRPLLedgerTimeSyncer([

    ]);

    $this->assertTrue(true);
  }
}