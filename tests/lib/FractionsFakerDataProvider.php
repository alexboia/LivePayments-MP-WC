<?php

class FractionsFakerDataProvider extends \Faker\Provider\Base {
    public function __construct($generator) {
        parent::__construct($generator);
    }

    public function generateFractionsOfAmount($amount, $precision = 2) {
        $amounts = array();
        $fractions = $this->generateFractionsOfUnit($precision);
        $fractionsCount = count($fractions);
        
        for ($i = 0; $i < $fractionsCount; $i ++) {
            $amounts[$i] = round($fractions[$i] * $amount, $precision);
        }

        $testSum = array_sum($amounts);
        if ($testSum < 1) {
            $amounts[$fractionsCount - 1] += (1 - $testSum);
        }

        return $amounts;
    }

    public function generateFractionsOfUnit($precision = 2) {
        $partsCount = $this->generator->numberBetween(2, 5);
        $average = (float)1 / $partsCount;
        $fractions = array_fill(0, 
            $partsCount, 
            $average);

        for ($i = 0; $i < $partsCount - 1; $i ++) {
            $currentValue = $fractions[$i];
            $adjustPercentage = $this->generator->numberBetween(0, 10);
            $adjustValue = ((float)$adjustPercentage / 100) * $currentValue;
            
            if ($this->generator->boolean()) {
                $fractions[$i] += $adjustValue;
                $fractions[$i + 1] -= $adjustValue;
            } else {
                $fractions[$i] -= $adjustValue;
                $fractions[$i + 1] += $adjustValue;
            }
        }

        for ($i = 0; $i < $partsCount; $i ++) {
            $fractions[$i] = round($fractions[$i], $precision);
        }

        $testSum = array_sum($fractions);
        if ($testSum < 1) {
            $fractions[$partsCount - 1] += (1 - $testSum);
        }

        return $fractions;
    }
}