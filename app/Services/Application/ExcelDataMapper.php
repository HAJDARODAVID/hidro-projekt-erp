<?php

namespace App\Services\Application;

use Illuminate\Support\Arr;

/**
 * @method void prepare()
 */
class ExcelDataMapper
{
    /**Store the array that will be used for the export */
    protected array $rawData;

    /**Column header titles, that will be shown in the export */
    protected array $headerTitles = [];

    /**Defines the additional header info */
    protected array $specialHeader = [];

    /**Additional information that will be used in the export */
    protected NULL|array $addInfo;

    public function __construct(array $rawData, ?array $addInfo = NULL)
    {
        $this->rawData = $rawData;
        $this->addInfo = $addInfo;

        //Run the prepare method if it exists.
        //Add prepare():void method to your DTO file as protected, so you can prepare the data for the export classes. 
        //This will run after all your properties are set.
        if (method_exists(get_class($this), 'prepare')) $this->prepare();
    }

    /**
     * Define the user friendly column header titles.
     * 
     * @param array $headerTitles Set the key/value pairs for the titles,
     * key being the data key, and the value the final title. 
     */
    protected function setColumnHeaders(array $headerTitles)
    {
        $this->headerTitles = $headerTitles;
        return $this;
    }

    /**
     * Define the special header that will be shown above the headers.
     * 
     * @param array $headers Add additional header array that should be shown.
     */
    protected function setSpecialHeader(array $headers)
    {
        //Add translations and if needed replace values from addInfo 
        foreach ($headers as $rowKey => $rowData) {
            foreach ($rowData as $colKey => $colData) {
                /**Check for add info flag */
                //TODO: ovo predelaj da bude malo bolje
                $checkRules = explode('.', $colData);
                if (count($checkRules) > 1 && $checkRules[0] ?? NULL == 'addInfo') {
                    $headers[$rowKey][$colKey] = isset($this->addInfo[$checkRules[1]]) ? $this->addInfo[$checkRules[1]] : NULL;
                }
            }
        }
        $this->specialHeader = $headers;
        return $this;
    }

    /**
     * Get the column header titles.
     * This will go thru all the data and get all the key, and replace it with the set header titles
     * 
     * @return array
     */
    public function getColumnHeaders()
    {
        //Get the keys from the first item
        $mainHeadersOutput = array_keys(Arr::first($this->rawData));
        //Replace and translate titles
        foreach ($mainHeadersOutput as $key => $value) {
            $mainHeadersOutput[$key] = array_key_exists($value, $this->headerTitles) ? translator($this->headerTitles[$value]) : translator($value);
        }
        return $mainHeadersOutput;
    }

    /**
     * Returns the special header array.
     * 
     * @return array
     */
    protected function getSpecialHeader()
    {
        return $this->specialHeader;
    }

    /**
     * Returns the header array.
     * This will combine special header and the main column header into one array.
     * 
     * @return array
     */
    public function getHeaders()
    {
        $output = [];
        if (!empty($this->getSpecialHeader())) {
            $output = array_merge($output, $this->getSpecialHeader());
            $output[] = [];
        }
        $output[] = $this->getColumnHeaders();
        return $output;
    }

    /**
     * Return the data that should be exported.
     * 
     * @return array
     */
    public function getArrayData()
    {
        return $this->rawData;
    }

    /**
     * Method for removing unnecessary keys/data from your array.
     * Use this method when you don't want to export some data.
     */
    protected function removeKeyFromData(...$keys)
    {
        foreach ($this->rawData as $dataKey => $data) {
            foreach ($keys as $key) {
                /**array_key_exists (not isset) so a key holding NULL still gets removed */
                if (array_key_exists($key, $this->rawData[$dataKey])) unset($this->rawData[$dataKey][$key]);
            }
        }
        return $this;
    }

    /**
     * Replace bool values in the raw Data property
     * 
     * @param $where define where to replace the values
     * @param $trueReplacement define with what to replace a TRUE value
     * @param $falseReplacement define with what to replace a FALSE value
     */
    protected function replaceBoolWith($where, $trueReplacement = NULL, $falseReplacement = NULL)
    {
        foreach ($this->rawData as $rawDataKey => $rawDataItem) {
            if (is_bool($rawDataItem[$where])) {
                if ($rawDataItem[$where] === TRUE) $this->rawData[$rawDataKey][$where] = $trueReplacement;
                if ($rawDataItem[$where] === FALSE) $this->rawData[$rawDataKey][$where] = $falseReplacement;
            }
        }
        return $this;
    }

    /**
     * Replace NULL value with zero value
     * 
     */
    protected function replaceNullWithZero()
    {
        foreach ($this->rawData as $rawDataKey => $rawDataItem) {
            foreach ($rawDataItem as $itemKey => $itemValue) {
                if ($itemValue === NULL) $this->rawData[$rawDataKey][$itemKey] = 0;
            }
        }
        return $this;
    }

    /**
     * Reorder the rawDate with a given set of key.
     * The keys given will be added and the other keys, that are not in the user given set, will be added on the end.
     */
    protected function reorderByKeys(...$keys)
    {
        //Get the key from the first item
        $rawDataKeys = array_keys($this->rawData[array_key_first($this->rawData)]);

        //Check if the given keys are in the original array
        foreach ($keys as $key => $value) {
            if (!in_array($value, $rawDataKeys)) unset($keys[$key]);
        }

        //Set new order
        $newOrder = array_merge($keys, array_diff($rawDataKeys, $keys));

        //Here we will set the reordered array
        $output = [];

        foreach ($this->rawData as $rawDataKey => $rawDataItemArray) {
            $reorderedArray = [];
            foreach ($newOrder as $key) {
                $reorderedArray[$key] = $rawDataItemArray[$key];
            }
            $output[$rawDataKey] = $reorderedArray;
        }
        $this->rawData = $output;
        return $this;
    }

    /**
     * Used for development
     */
    protected function devShow($enabled = TRUE)
    {
        if ($enabled) dd($this);
    }
}
