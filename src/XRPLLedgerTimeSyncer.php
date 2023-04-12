<?php declare(strict_types=1);

namespace XRPLWin\XRPLLedgerTime;

use XRPLWin\XRPL\Client;
use XRPLWin\XRPL\Exceptions\BadRequestException;
use Carbon\Carbon;
use Carbon\CarbonPeriod;


/**
 * XRPL Ledger Time Syncer
 */
class XRPLLedgerTimeSyncer
{
  /**
   * Current ledger being scanned.
   *
   * @var int
   */
  private int $ledger_current;

  /**
   * Last ledger index in time of this job.
   *
   * @var int
   */
  private readonly int $ledger_last;

  /**
   * XRPL API Client instance
   *
   * @var \XRPLWin\XRPL\Client
   */
  protected readonly Client $XRPLClient;

  public function __construct(array $connection_options = [])
  {
    $this->XRPLClient = new Client($connection_options);

    $ledger_last_api = $this->XRPLClient->api('ledger')
      ->params([
        'ledger_index' => 'validated',
        'accounts' => false,
        'full' => false,
        'transactions' => false,
        'expand' => false,
        'owner_funds' => false,
      ]);
    
    $this->ledger_last = (int)$ledger_last_api->send()->finalResult()->ledger_index;
      //TODO: https://github.com/XRPLWin/XWA/blob/dev-dynamodb/app/Console/Commands/XwaLedgerIndexSync.php
  }
}