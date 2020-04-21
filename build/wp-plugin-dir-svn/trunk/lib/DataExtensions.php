<?php

namespace LvdWcMc {
    trait DataExtensions {
        public function mergeAdditionalData(\stdClass $targetData, array $additionalData) {
            if (is_array($additionalData)) {
                foreach($additionalData as $key => $value) {
                    //Do not override existing properties
                    if (!property_exists($targetData, $key)) {
                        $targetData->$key = $value;
                    }
                }
            }
            
            return $targetData;
        }
    }
}