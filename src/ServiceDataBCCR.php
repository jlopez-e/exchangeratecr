<?php
namespace Drupal\exchangeratecr;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

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

    $response = false;

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
      CURLOPT_FAILONERROR => true,
    ));

    $xml = curl_exec($curl);
    curl_close($curl);

    if($xml !== false) {
      $response = $this->getIndicator($xml,$indicator);
    }else{
      $dataTempStored= $this->getSharedTempStore($indicator);
      if($dataTempStored != null){
        $numValor = $dataTempStored['value'];
        $response = $numValor;
      }
    }
    return $response;
  }

  /**
   *  This method process the xml from the Banco Central and get the value of the indicator
   * @param $xml
   * @return float
   */
  public function getIndicator($xml,$indicator) {
    $numValor = false;

    if($xml !== false){

      $first_xml = new \SimpleXMLElement($xml);

      if($first_xml !== false) {

        $second_xml = $first_xml[0];

        if($second_xml !== false){

          $values = new \SimpleXMLElement($second_xml);

          if($values !== false){

            $value = $values->INGC011_CAT_INDICADORECONOMIC[0]->NUM_VALOR;

            if($value){
              $numValor = floatval($value);
              $values_shared_temp_store= array(
                'date'=> date("j/n/Y"),
                'value'=>$numValor,
              );
              $this->setSharedTempStore($values_shared_temp_store,$indicator);
            }
          }
        }
      }
    }
    return $numValor;
  }

  /**
   *  This method convert the currency
   * @param $from
   * @param $amount
   * @return float
   */
  public function convertCurrecy($from, $amount) {

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
        $result = $amount/$sellRate;
        break;

      case 'USD':
        $result = $amount*$buyRate;
        $var=1;
        break;
    }
    return $result;
  }

  public function setSharedTempStore($values,$indicator){
    $shared_temp_store = \Drupal::service('user.shared_tempstore')->get('exchangeratecr');
    $shared_temp_store->set('exchange_rate_data_'.$indicator, $values);
  }

  public function getSharedTempStore($indicator){
    $shared_temp_store = \Drupal::service('user.shared_tempstore')->get('exchangeratecr');
    $values = $shared_temp_store->get('exchange_rate_data_'.$indicator);;
    return $values;
  }

}