<?php
/**
 * @file
 * Contains \Drupal\exchangeratecr\ServiceDataBCCR.
 */
namespace Drupal\exchangeratecr;

class ServiceDataBCCR {

  /**
   *  This method process the response from the Banco Central
   * @param $indicator
   * @param $startDate
   * @param $endDate
   * @param $name
   * @param $sublevels
   * @return float
   */
  public function getDataFromBancoCentralCR($indicator, $startDate, $endDate, $name, $sublevels) {

    //Url Banco Central De Costa Rica
    $url = 'http://indicadoreseconomicos.bccr.fi.cr/indicadoreseconomicos/WebServices/wsIndicadoresEconomicos.asmx/ObtenerIndicadoresEconomicosXML';

    //Parameters to get the Data
    $parameters = "?tcIndicador=" . $indicator . "&tcFechaInicio=" . $startDate . "&tcFechaFinal=" . $endDate . "&tcNombre=" . $name . "&tnSubNiveles=" . $sublevels;

    // Get cURL resource
    $curl = curl_init();

    // Set options
    curl_setopt_array($curl, array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => $url . $parameters,
    ));

    // Send the request & save response to $respose
    if (!curl_exec($curl)) {
      die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
    }
    else {
      $response = curl_exec($curl);
    }

    $value = $this->getIndicator($response);

    // Close request to clear up some resources
    curl_close($curl);

    return $value;

  }

  /**
   *  This method process the xml from the Banco Central and get the value of the indicator
   * @param $xml
   * @return float
   */
  public function getIndicator($xml) {

    //Getting the first part of xml from Banco Central
    $first_xml = new \SimpleXMLElement($xml);

    //We have to parse again to get the values from Banco Central
    $second_xml = $first_xml[0];

    //Now we have the values and we can access to them
    $values = new \SimpleXMLElement($second_xml);

    //Getting the value num_valor
    $numValor = $values->INGC011_CAT_INDICADORECONOMIC[0]->NUM_VALOR;

    return floatval($numValor);
  }

  public function convertCurrecy($from, $to, $amount) {

    //Variable to Store the conversion result
    $result=0;

    //Variables for method getDataFromBancoCentralCR
    $startDate=date("j/n/Y");
    $endDate=date("j/n/Y");
    $name="exchangeratecr";
    $sublevels="N";

    //Buy Rate
    $buyRate = $this->getDataFromBancoCentralCR('317',$startDate,$endDate ,$name,$sublevels);

    //Sell Rate
    $sellRate = $this->getDataFromBancoCentralCR('318',$startDate,$endDate ,$name,$sublevels);

    switch ($from) {
      case 'CRC':
        $result = $amount/$buyRate;
        break;

      case 'USD':
        $result = $amount*$sellRate;

        break;
    }

    return $result;

  }

}