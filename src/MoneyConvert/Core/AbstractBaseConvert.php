<?php

namespace MoneyConvert\Core;

use MoneyConvert\Core\BaseConvertInterface;

/**
 * @author Diego Brocanelli <contato@diegobrocanelli.com.br>
 */
class AbstractBaseConvert implements BaseConvertInterface 
{
    protected $langConvert;

    /**
     * @param string $lang
     */
    public function __construct($lang) 
    {
        $path = __DIR__.'/../i18n/'.$lang.'.php';

        if(!file_exists($path)){
            throw new \Exception("Class: {$lang} not fount.");
        }

        $nameSpace = "MoneyConvert\\i18n\\{$lang}";
        $langType  = new $nameSpace;

        $this->langConvert = $langType;
    }

    /**
     * Convert numbers to words.
     * 
     * @param float $number
     * @param int $decimalPoint
     * @return string
     * @throws Exception
     */
    public function convert($number, $decimalPoint = 0)
    {
        if($number == 0){
            return 'Value informed is zero, please report value bigger than zero';
        }

        $langType = $this->langConvert;

        $remove = $langType->specialCharacter;

        foreach ($remove as $string) {
            $value = str_ireplace($string, '', $number);
        }

        if (substr_count($value, ',') > 1) {
            throw new Exception("The number has more than one comma.");
        }

        $values = explode('.', $value);

        $reais = $this->real($values);
        $cents = $this->cents($values);

        $and = "";
        if(count($values) >= 2){
            $and = $langType->etc[$langType->lang]['&'];
        }

        return trim($reais." {$and} ".$cents);
    }

    /**
     * Treatment of values before the comma.
     * 
     * @param type $values
     * @return string
     */
    private function real($values)
    {
        $langType = $this->langConvert;

        $reaisValue = (array_key_exists(0, $values)) ? $values[0] : 0;

        $reais = $this->numberToString($values[0]);
        if($reaisValue && $reaisValue == 1){

            $reais .= ' '.$langType->realType[0];

        }elseif($reaisValue){

            $reais .= ' '.$langType->realType[1];

        }

        return $reais;
    }

    /**
     * Treatment cents
     * 
     * @param type $values
     * @return string
     */
    private function cents($values)
    {
        $langType = $this->langConvert;

        $centValue = (array_key_exists(1, $values)) ? $values[1] : 0;

        $cents = $this->numberToString($centValue);
        if($centValue && $centValue == 1){

            $cents .= ' '.$langType->centsType[0];

        }elseif($centValue){

            $cents .= ' '.$langType->centsType[1];

        }

        return $cents;
    }


    /**
     * Convert number in text.
     * 
     * @param type $number
     * @param type $decimalPoint
     * @return type
     */
    private function numberToString($number, $decimalPoint = 0) 
    {
        $langType = $this->langConvert;

        $reaisCentValues = explode(
            '.', 
            number_format(
                preg_replace("/[,]/", "", ($number) ?: 0), 
                ($decimalPoint) ?: $langType->decimalPoint, 
                ".", 
                ","
            )
        );

        $formated = (array_key_exists(0, $reaisCentValues)) ? $reaisCentValues[0] : 0;
        $point = (array_key_exists(1, $reaisCentValues)) ? $reaisCentValues[1] : 0;

        $langType->numberP = $formated . (empty($point) ? "" : "." . $point);
        $langType->number = $formated;

        $groups = explode(',', $formated);
        $stepNum = count($groups) - 1;

        $parts = array();
        foreach ($groups as $step => $group) {

            $groupWords = $this->groupToWords($group);

            if ($groupWords) {
    
                $part = implode(' ' . $langType->etc[$langType->lang]['&'] . ' ', $groupWords);
    
    
                if(count($groups) >= 3 && $groups[0] == 1 ){
                    if (isset($langType->stepsSingular[$langType->lang][$stepNum - $step])) {
                        $part .= ' ' . $langType->stepsSingular[$langType->lang][$stepNum - $step];
                    }
                }else{
                    if (isset($langType->stepsPlural[$langType->lang][$stepNum - $step])) {
                        $part .= ' ' . $langType->stepsPlural[$langType->lang][$stepNum - $step];
                    }
                }
    
                $parts[] = $part;
            }
        }

        return ($langType->result = implode(' ' . $langType->etc[$langType->lang]['&'] . ' ', $parts));
    }

    /**
     * Get the text of each number
     * 
     * @param type $group
     * @param type $groupPoint
     * @return boolean
     */
    private function groupToWords($group, $groupPoint = 0) 
    {
        $group = sprintf('%03d', $group);

        $d1 = (int) $group{2};
        $d2 = (int) $group{1};
        $d3 = (int) $group{0};

        $langType = $this->langConvert;

        $groupArray = array();
        if (!$groupPoint) {
            if ($d3 != 0){
                if($d3 == 1) {
                    //if the word needed is 'cem'
                    if($d1 == 0 && $d2 == 0 && $langType->lang == 'pt_BR'){
                        $groupArray[] = $langType->digitTH[$langType->lang][2];
                    //if the word needed is 'cento'
                    }else {
                        $groupArray[] = $langType->digitTH[$langType->lang][$d3];
                    }
                } else {
                    $groupArray[] = $langType->digitTH[$langType->lang][$d3 + 1];
                }
            }

            if ($d2 == 1 && $d1 != 0){ // 11-...-19
                $groupArray[] = $langType->digitTE[$langType->lang][$d1];
    
            }else if ($d2 != 0 && $d1 == 0){ // 1-...-9+0
 
                $groupArray[] = $langType->digitTW[$langType->lang][$d2];
    
            }else if ($d2 == 0 && $d1 == 0) {} // 00
            else if ($d2 == 0 && $d1 != 0) { // 1-...-9
    
                $groupArray[] = $langType->digitON[$langType->lang][$d1];
    
            } else {
    
                $groupArray[] = $langType->digitTW[$langType->lang][$d2];
                $groupArray[] = $langType->digitON[$langType->lang][$d1];
    
            }
        } elseif ($groupPoint) {
            if ($d3 != 0){
                $groupArray[] = $langType->digitTH[$langType->lang][$d3];
            }

            if ($d2 == 1 && $d1 != 0){ // 11-19
 
                $groupArray[] = $langType->digitTE[$langType->lang][$d1];
    
            }else if ($d2 != 0 && $d1 == 0){ // 10-20-...-90
 
                $groupArray[] = $langType->digitTW[$langType->lang][$d2];
    
            }else if ($d2 == 0 && $d1 == 0) {} // 00
            else if ($d2 == 0 && $d1 != 0) // 1-9

                $groupArray[] = $langType->digitON[$langType->lang][$d1];

            else { // Others
    
                $groupArray[] = $langType->digitTW[$langType->lang][$d2];
                $groupArray[] = $langType->digitON[$langType->lang][$d1];
    
            }
        }

        if (!count($groupArray)){
            return false;
        }

        return $groupArray;
    }
}
