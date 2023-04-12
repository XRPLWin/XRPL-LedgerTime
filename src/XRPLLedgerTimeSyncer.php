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
   * @see https://xrpl.org/basic-data-types.html#specifying-time
   */
  const RIPPLE_EPOCH = 946684800;

  /**
   * Current ledger being scanned.
   *
   * @var int
   */
  protected int $ledger_current;

  /**
   * Last ledger index in time of this job.
   *
   * @var int
   */
  protected readonly int $ledger_last;

  /**
   * XRPL API Client instance
   *
   * @var \XRPLWin\XRPL\Client
   */
  protected readonly Client $XRPLClient;

  protected array $log = [];

  public function __construct(array $connection_options = [])
  {
    $this->XRPLClient = new Client($connection_options);

    $time = Carbon::now()->yesterday()->hour(17);
    //dd($time);
    $test = $this->findLedgerIndex($time,0,79060012,79060012);
    dd($time,$test,$this->log);

    /*$ledger_last_api = $this->XRPLClient->api('ledger')
      ->params([
        'ledger_index' => 'validated',
        'accounts' => false,
        'full' => false,
        'transactions' => false,
        'expand' => false,
        'owner_funds' => false,
      ]);
    
    $this->ledger_last = (int)$ledger_last_api->send()->finalResult()->ledger_index;*/
      //TODO: https://github.com/XRPLWin/XWA/blob/dev-dynamodb/app/Console/Commands/XwaLedgerIndexSync.php
  }

  /*public function executePeriod(CarbonPeriod $period)
  {
    foreach($period as $p) {
      # find last ledger index for this $day
      $day_last_ledger_index = $this->findLastLedgerIndexForDay($day, $this->ledger_current, $this->ledger_last, $this->ledger_last);
      $this->info($day_last_ledger_index. ' - '. $day->format('Y-m-d'));
      $this->ledger_current = $day_last_ledger_index+1;
      //save to local db $day_last_ledger_index is last ledger of $day
      $this->saveToDb($li_first,$day_last_ledger_index,$day);
      $li_first = $day_last_ledger_index+1;
      $bar->advance();
      $this->info('');
    }
  }*/

  /**
   * @param Carbon $time
   * @param int $low
   * @param int $high
   * @param int $lastHigh
   */
  public function findLedgerIndex(Carbon $time, int $low, int $high, int $lastHigh): int
  {
    $time_high = $this->fetchLedgerIndexTime($high);
    if($time_high->greaterThan($time)) { //too high
      return $this->findLedgerIndex($time, $low, $this->halveNumbers($low,$high), $high);
    } else {
      //$high ledger is somewhere between $low and $time
      //check if next ledger is in next day, if not then continue with adjusted ranges
      $next_ledger_time = $this->fetchLedgerIndexTime($high+1);
      if($next_ledger_time->greaterThanOrEqualTo($time)) //Found it.
        return $high;
      else //contine search with adjusted lower threshold...
        return $this->findLedgerIndex($time, $high, $this->halveNumbers($high,$lastHigh), $lastHigh);
    }
  }

  private function halveNumbers($low,$high): int
  {
    $n = ($high+$low)/2;
    $n = \ceil($n);
    $this->log( 'L: '.$low.' H: '. $high. ' N: '.(int)$n);
    return (int)$n;
  }

  

  private function fetchLedgerIndexTime(int $index): Carbon
  {
    $ledger_result = $this->fetchLedgerIndexInfo($index);
    return Carbon::createFromTimestamp($ledger_result->close_time + self::RIPPLE_EPOCH);
  }

  private function fetchLedgerIndexInfo(int $index, $trynum = 1)
  {
    $ledger = $this->XRPLClient->api('ledger')
    ->params([
      'ledger_index' => $index,
      'accounts' => false,
      'full' => false,
      'transactions' => false,
      'expand' => false,
      'owner_funds' => false,
    ]);
    $success = true;
    try {
      $r = $ledger->send();
    } catch (BadRequestException $e) {
      $success = false;
    }

    if(!$success){
      if($trynum > 5) {
        $this->log('Stopping, too many tries');
        throw new \Exception('XRPL Connection timeout after 5 unsuccessful tries.');
      }
      $this->log('Sleeping for 10 seconds ('.$trynum.')...');
      sleep(10);
      return $this->fetchLedgerIndexInfo($index,$trynum+1);
    }
    return $ledger->finalResult();
  }

  private function log(string $line): void
  {
    $this->log[] = $line;
  }

}