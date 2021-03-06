<?php
namespace MaciejSzAt\NbpPhp;
 
use MaciejSz\NbpPhp\NbpRepository;
use PHPUnit\Framework\TestCase;

class MainFeatureContext extends FeatureContext
{
    /**
     * @var null|NbpRepository
     */
    protected $_NbpRepo = null;

    /**
     * @var string[]
     */
    protected $_requested_currencies = [];

    /**
     * @var float[]
     */
    protected $_actual_rates = [];

    /**
     * @var float[]
     */
    protected $_expected_rates = [];

    /**
     * @var bool
     */
    protected $_work_day_before = false;

    /**
     * @Transform :currencies
     */
    public function extractCurrencies($string)
    {
        $items = preg_split("/([ ,]|and)/", $string);
        $items = array_filter($items, function($item){
            return !empty($item);
        });
        return $items;
    }

    /**
     * @Transform :rates
     */
    public function extractRates($string)
    {
        $items = preg_split("/(?<currencies>(and|[A-Z ,])+)/", $string);
        $out = [];
        foreach ( $items as $item ) {
            if ( empty($item) ) {
                continue;
            }
            $out[] = floatval(trim($item));
        }
        return $out;
    }

    /**
     * @Given I have the NbpRepository object for table :table
     */
    public function iHaveTheNbpRepositoryObjectForTable($table)
    {
        $this->_NbpRepo = NbpRepository::defaultCacheInstance();
    }

    /**
     * @When /^I request the rate(s)? of (?<currencies>(and|[A-Z ,])+)$/
     */
    public function iRequestTheRatesOf(array $currencies)
    {
        $this->_requested_currencies = $currencies;
        $this->_work_day_before = false;
    }

    /**
     * @When /^I request the rate(s)? of (?<currencies>(and|[A-Z ,])+) from work day before$/
     */
    public function iRequestTheRatesOfFromWorkDayBefore($currencies)
    {
        $this->_requested_currencies = $currencies;
        $this->_work_day_before = true;
    }

    /**
     * @Then /^I should get the rate(s)? (?<rates>([0-9 ,\.]|and)+)$/
     */
    public function iShouldGetTheRates(array $rates)
    {
        TestCase::assertGreaterThan(0, count($rates));
        reset($this->_requested_currencies);
        foreach ( $rates as $expected ) {
            $requested_currency = current($this->_requested_currencies);
            $Rate = null;
            if ( $this->_work_day_before ) {
                $Rate = $this->_NbpRepo->getRateBefore(
                    $this->_inputDate,
                    $requested_currency
                );
            }
            else {
                $Rate = $this->_NbpRepo->getRate(
                    $this->_inputDate,
                    $requested_currency
                );
            }
            TestCase::assertEquals($expected, $Rate->avg, "", 0.00001);
            next($this->_requested_currencies);
        }
    }
}
 