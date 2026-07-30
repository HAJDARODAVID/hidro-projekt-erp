<?php

namespace App\Services\HidroProjekt\Domain\Api;

use DateTime;

class WeatherForecastService{

    const FORECAST_URL = "https://prognoza.hr/tri/3d_graf_i_simboli.xml";
    private $forecastData =[];
    private $data;
    private $townData=NULL;
    private $townArray=NULL;

    public function __construct(){
        $this->forecastData = $this->getDataFromUrl();
    }

    public function toJson(){
        $this->data = json_encode($this->forecastData);
        return $this;
    } 

    public function toArray(){
        $json = json_encode($this->forecastData);
        $this->data = json_decode($json,TRUE);
        return $this;
    } 

    public function formatArray(){
        if(!is_array($this->data)){
            return $this;
        }
        foreach ($this->data['grad'] as $key => $town) {
            $this->data[$town["@attributes"]['ime']] = $town['dan'];
            unset($this->data['grad'][$key]);
        }
        unset($this->data['grad']);
        $this->setTownArray();
        return $this;
    }

    private function setTownArray(){
        foreach ($this->data as $town => $data) {
            $this->townArray[$town] = TRUE;
        }
        unset($this->townArray['izmjena']);
        return;
    }

    public function getTownArray(){
        return $this->townArray;
    }

    public function town($town){
        if(!is_array($this->data)){
            return $this;
        }
        if(!isset($this->data[$town])){
            return $this;
        }
        $this->townData = $this->data[$town];
        return $this;
    }

    public function forDashboard(){
        $newArray=[];
        $today = new DateTime(now());
        $tomorrow = (new DateTime(now()))->modify('+1 day');
        $hours=[5,8,11,14,17];
        //$hours=[4,7,10,13,16];
        foreach ($hours as $hour) {
            $newArray[$today->format('d.m.Y')."."][$hour] = NULL;
            $newArray[$tomorrow->format('d.m.Y')."."][$hour] = NULL;
        }

        foreach ($this->townData ?? [] as $key => $item) {
            $datum = $item['@attributes']['datum'];
            $sat = $item['@attributes']['sat'];
            if(($datum == $today->format('d.m.Y') ."." || $datum == $tomorrow->format('d.m.Y') .".") && in_array($sat,$hours)){
                $newArray[$datum][$this->townData[$key]['@attributes']['sat']] = $this->townData[$key];
                unset($newArray[$datum][$this->townData[$key]['@attributes']['sat']]['@attributes']);
            }
        }
       
        $this->townData = $newArray;
        return $this;
    }

    public function getLastChanged(){
        return is_array($this->data) ? ($this->data['izmjena'] ?? null) : null;
    }

    public function getTownData(){
        return $this->townData;
    }

    private function getDataFromUrl(){
        try {
            $data = simplexml_load_file(self::FORECAST_URL, "SimpleXMLElement", LIBXML_NOCDATA);
        } catch (\Throwable $e) {
            return null;
        }
        return $data === false ? null : $data;
    }
    
}