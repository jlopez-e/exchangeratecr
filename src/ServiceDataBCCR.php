<?php
namespace Drupal\exchangeratecr;
use GuzzleHttp\Exception\RequestException;
/**
 * @file
 * Contains \Drupal\exchangeratecr\ServiceDataBCCR.
 */
class ServiceDataBCCR {

  /**
   *  This method process the response from the Banco Central
   * @param $indicator
   * @param $startDate
   * @param $endDate
   * @param $name
   * @param $sublevels
   * @return array
   */
  public function getDataFromBancoCentralCR($indicator, $startDate, $endDate, $name, $sublevels) {

    $response = array(
      'successful' => false,
      'date' => $startDate,
      'value' => 0,
      'message' => 'At the moment there is no communication with the Bank.',
    );

    //Url Banco Central De Costa Rica
    $url = 'http://indicadoreseconomicos.bccr.fi.cr/indicadoreseconomicos/WebServices/wsIndicadoresEconomicos.asmx/ObtenerIndicadoresEconomicosXML';

    // Create a HTTP client.
    $client = \Drupal::httpClient();

    try {
      // Set options for our HTTP request.
      $request = $client->request('GET', $url, [
        'query' => [
          'tcIndicador' => $indicator,
          'tcFechaInicio' => $startDate,
          'tcFechaFinal' => $endDate,
          'tcNombre' => $name,
          'tnSubNiveles' => $sublevels
        ]
      ]);

      // If successful HTTP query.
      if ($request->getStatusCode() == 200) {
        $xml = $request->getBody()->getContents();
        $response['value'] = $this->getIndicator($xml,$indicator);
        $response['successful'] = true;
        $response['message'] = '';
      }

    }catch (RequestException $e){
      $dataTempStored= $this->getSharedTempStore($indicator);

      if($dataTempStored['successful']){
        $response['value'] = $dataTempStored['value'];
        $response['date'] = $dataTempStored['date'];
        $response['successful'] = true;
      }
    }

    return $response;
  }

  /**
   *  This method process the xml from the Banco Central and get the value of the indicator, also save
   *  the value in temporal store
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
                'successful' => true,
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
    $buyRate = $this->getDataFromBancoCentralCR('317',$startDate,$endDate ,$name,$sublevels)['value'];

    //Sell Rate
    $sellRate = $this->getDataFromBancoCentralCR('318',$startDate,$endDate ,$name,$sublevels)['value'];

    switch ($from) {
      case 'CRC':
        $result = $amount/$sellRate;
        break;

      case 'USD':
        $result = $amount*$buyRate;
        break;
    }
    return $result;
  }

  /**
   *  This method is to save variables in temporal store
   * @param $values
   * @param $indicator
   */
  public function setSharedTempStore($values,$indicator){
    $shared_temp_store = \Drupal::service('user.shared_tempstore')->get('exchangeratecr');
    $shared_temp_store->set('exchange_rate_data_'.$indicator, $values);
  }

  /**
   *  This method is to get the variables in temporal store
   * @param $indicator
   * @return array
   */
  public function getSharedTempStore($indicator){

    $response= array(
      'successful' => false,
      'date'=> null,
      'value'=> 0
    );

    $shared_temp_store = \Drupal::service('user.shared_tempstore')->get('exchangeratecr');
    $values = $shared_temp_store->get('exchange_rate_data_'.$indicator);

    if($values != null){
      $response['successful'] = true;
      $response['value'] = $values['value'];
      $response['date'] = $values['date'];
    }
    return $response;
  }

  /**
   *  This method is to delete the variable in temporal store
   * @param $indicator
   */
  public function deleteShardTempStore($indicator){
    $shared_temp_store = \Drupal::service('user.shared_tempstore')->get('exchangeratecr');
    $shared_temp_store->delete('exchange_rate_data_'.$indicator);
  }
}