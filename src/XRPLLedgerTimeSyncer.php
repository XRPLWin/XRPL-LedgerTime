<?php declare(strict_types=1);

namespace XRPLWin\XRPLLedgerTime;

use XRPLWin\XRPL\Client;
use XRPLWin\XRPL\Exceptions\BadRequestException;
use Carbon\Carbon;
#use Carbon\CarbonPeriod;


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
  protected ?int $ledger_index_last = null;

  /**
   * XRPL API Client instance
   *
   * @var \XRPLWin\XRPL\Client
   */
  protected readonly Client $XRPLClient;

  protected array $log = [];
  private bool $debug = false;
  private int $start_low = 0;
  #private array $cache = []; //TODO: reuse queried responses

  /**
   * @param array $options ['ledgerindex_low']
   */
  public function __construct(array $options = [], array $connection_options = [])
  {
    $this->XRPLClient = new Client($connection_options);

    //Set starting low edge
    if(isset($options['ledgerindex_low']))
      $this->start_low = (int)$options['ledgerindex_low'];
    
  }

  /**
   * Returns closest lower ledger_index (or equal). ledger_index+1 is always first after specified $datetime.
   * @return int ledger_index
   */
  public function datetimeToLedgerIndex(Carbon $datetime): int
  {
    $this->setup();
    return $this->findLedgerIndex($datetime, $this->start_low, $this->ledger_index_last, $this->ledger_index_last);
    
  }

  /**
   * Query XRPL and return Carbon datetime instance of that ledger
   * @param int $index - ledger_index
   * @return Carbon
   */
  public function ledgerIndexToCarbon(int $index): Carbon
  {
    return $this->fetchLedgerIndexTime($index);
  }

  /**
   * Queries XRPL and sets latest validated ledger_index to $this->ledger_index_last
   * @return void
   */
  private function setup(): void
  {
    if($this->ledger_index_last === null) {
      //Query XRPL and find last validated ledger index.
      $ledger_last_api = $this->XRPLClient->api('ledger')
      ->params([
        'ledger_index' => 'validated',
        'accounts' => false,
        'full' => false,
        'transactions' => false,
        'expand' => false,
        'owner_funds' => false,
      ]);
      $this->ledger_index_last = (int)$ledger_last_api->send()->finalResult()->ledger_index;
    }
  }

  /**
   * @param Carbon $time - target time instance
   * @param int $low - starting low end ledger_index
   * @param int $high - starting high end ledger_index
   * @param int $lastHigh - last processed high ledger_index
   */
  private function findLedgerIndex(Carbon $time, int $low, int $high, int $lastHigh): int
  {
    if($time->isFuture())
      throw new \Exception('Requested time is in future');

    $time_high = $this->fetchLedgerIndexTime($high);

    if($time_high->equalTo($time)) //found exact
      return $high;
    
    if($time_high->greaterThan($time)) { //too high
      return $this->findLedgerIndex($time, $low, $this->halveNumbers($low,$high), $high);
    } else {
      //$high ledger is somewhere between $low and $time
      //check if next ledger is in next time, if not then continue with adjusted ranges
      $next_ledger_time = $this->fetchLedgerIndexTime($high+1);
      if($next_ledger_time->greaterThan($time)) //Found it.
        return $high;
      else //contine search with adjusted lower threshold...
        return $this->findLedgerIndex($time, $high, $this->halveNumbers($high,$lastHigh), $lastHigh);
    }
  }

  /**
   * Halve two integers.
   * @param int $low
   * @param int $high
   * @return int Halved integer
   */
  private function halveNumbers(int $low, int $high): int
  {
    $n = ($high+$low)/2;
    $n = (int)\ceil($n);
    $this->setlog('L: '.$low.' H: '. $high. ' N: '.$n);
    return $n;
  }

  /**
   * Query XRPL and return Carbon datetime instance of that ledger
   * @param int $index - ledger_index
   * @return Carbon
   */
  private function fetchLedgerIndexTime(int $index): Carbon
  {
    $ledger_result = $this->fetchLedgerIndexInfo($index);
    if($ledger_result->closed == false)
      throw new \Exception('Ledger index ('.$index.') you are querying is too high (not closed)');

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
        $this->setlog('Stopping, too many tries');
        throw new \Exception('XRPL Connection timeout after 5 unsuccessful tries.');
      }
      $this->setlog('Sleeping for 10 seconds ('.$trynum.')...');
      sleep(10);
      return $this->fetchLedgerIndexInfo($index,$trynum+1);
    }
    return $ledger->finalResult();
  }

  private function setlog(string $line): void
  {
    $this->log[] = $line;
    if($this->debug)
      echo $line.PHP_EOL;
  }

  public function clearLog()
  {
    $this->log = [];
  }

  public function log()
  {
    return $this->log;
  }

}