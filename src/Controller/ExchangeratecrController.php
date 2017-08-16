<?php
/**
 * @file
 * Contains \Drupal\exchangeratecr\ExchangeratecrController
 */
namespace Drupal\exchangeratecr\Controller;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\exchangeratecr\ServiceDataBCCR;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ExchangeratecrController
 * @package Drupal\exchangeratecr\Controller
 */
class ExchangeratecrController extends ControllerBase{

  /**
   * @var \Drupal\exchangeratecr\ServiceDataBCCR
   */
  protected $serviceDataBCCR;

  /**
   * {@inheritdoc}
   */
  public function __construct(ServiceDataBCCR $serviceDataBCCR) {
    $this->$serviceDataBCCR = $serviceDataBCCR;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('exchangeratecr.data_bccr_service'));
  }



  /**
   * This callback is mapped to the path
   * 'exchangeratecr/generate/{paragraphs}/{phrases}'.
   */
  public function generate($currencyx, $amountx) {
  }

//  public function getDataFromBancoCentral($indicator,$startDate,$endDate,$name,$sublevels){
//
//    //Url Banco Central De Costa Rica
//    $url= 'http://indicadoreseconomicos.bccr.fi.cr/indicadoreseconomicos/WebServices/wsIndicadoresEconomicos.asmx/ObtenerIndicadoresEconomicos';
//
//    //Parameters to get the Data
//    $url.= "tcIndicador=".$indicator."&tcFechaInicio=".$startDate."&tcFechaFinal=".$endDate."&tcNombre=".$name."&tnSubNiveles=".$sublevels;
//
//    $test=1;
//
//    return  $url;
//
//  }


  public function currencyConverter ($form, &$form_state) {

    $indicator="317";
    $startDate=date("j/n/Y");
    $endDate=date("j/n/Y");
    $name="exchangeratecr";
    $sublevels="N";

    //$url_method=  $this->serviceDataBCCR->getDataFromBancoCentralCR($indicator,$startDate,$endDate ,$name,$sublevels);





//    $url_method = $this->getDataFromBancoCentral($indicator,$startDate,$endDate ,$name,$sublevels);


    // Web Service parameters

    // Dynamic date, format dd/mm/aaaa
    $fecha = date("j/n/Y");

    // Get the temporal variable
    $tempstorex = \Drupal::service('user.shared_tempstore')->get('exchangeratecr');
    $tempstorexx = $tempstorex->get('tempstore');

    // white the generated errors
    $errors = [];

    //if ($tempstorexx === NULL || $tempstorexx <= 0) {
      // connect to web server and get the data
      try {
        // reuquest to web service


        $url= "http://indicadoreseconomicos.bccr.fi.cr/indicadoreseconomicos/WebServices/wsIndicadoresEconomicos.asmx/ObtenerIndicadoresEconomicos?tcIndicador=317&tcFechaInicio=" . $fecha . "&tcFechaFinal=" . $fecha . "&tcNombre=Oscar&tnSubNiveles=N";


        $data = file_get_contents("http://indicadoreseconomicos.bccr.fi.cr/indicadoreseconomicos/WebServices/wsIndicadoresEconomicos.asmx/ObtenerIndicadoresEconomicos?tcIndicador=317&tcFechaInicio=" . $fecha . "&tcFechaFinal=" . $fecha . "&tcNombre=Oscar&tnSubNiveles=N");



        // process the xml response and get the needed data
        $str = $data;
        $startDelimiter = '<NUM_VALOR>';
        $endDelimiter = '</NUM_VALOR>';
        $startDelimiterLength = strlen($startDelimiter);
        $endDelimiterLength = strlen($endDelimiter);
        $startFrom = $contentStart = $contentEnd = 0;
        while (FALSE !== ($contentStart = strpos($str, $startDelimiter, $startFrom))) {
          $contentStart += $startDelimiterLength;
          $contentEnd = strpos($str, $endDelimiter, $contentStart);
          if (FALSE === $contentEnd) {
            break;
          }
          $contents[] = substr($str, $contentStart, $contentEnd - $contentStart);
          $startFrom = $contentEnd + $endDelimiterLength;
        }

        //set the temporary variable
        $tempstorex = \Drupal::service('user.shared_tempstore')
          ->get('exchangeratecr');
        $tempstorex->set('tempstore', $contents[0]);
        $tempstorexx = $tempstorex->get('tempstore');//valor del dolar xx
      } catch (Exception $e) {
        // catch any error while the data processing
        array_push($errors, $e->getMessage());
      }
    //}

    ////////////////////////
    //Logica de conversion//
    ////////////////////////

//    if ($form_state->getValue('currency')=='CRC') {
//      //Si escoge moneda tica, se convierte a dolares, division
//      if (is_numeric($form_state->getValue('amount'))&&$form_state->getValue('amount')>0) {
//        $converted=$form_state->getValue('amount')/$tempstorexx;
//        $converted=number_format($converted, 2);
//        $simbol='$';
//      }else {
//        (\Drupal::languageManager()->getCurrentLanguage()->getId()=='es' ? $message='El monto no pueden ser letras o numeros negativos' : $message='Amount must be a numeric non-negative value');
//        (\Drupal::languageManager()->getCurrentLanguage()->getId()=='es' ? $message2='Por favor, intentelo de nuevo.' : $message2='Please, try again');
//      }
//
//    }else {

      // validate and convert the value
      if (is_numeric($form_state->getValue('amount')) && $form_state->getValue('amount') > 0) {
        if (!is_numeric($tempstorexx) && !$tempstorexx > 0){
          $message = t('Gendered problem getting the data from the Web Service');
          array_push($errors, $message);
        } else {
          $converted=$form_state->getValue('amount')*$tempstorexx;
          $converted=number_format($converted, 2);
          $simbol='₡';
        }
      }else {
        // set error messages
        $message = t('Amount must be a numeric non-negative value');
        array_push($errors, $message);
        $message2 = t('Please, try again');
      }
//    }

    if (!isset($message)) {
      // build the AJAX response when everything is ok
      $response = new AjaxResponse();
      // set the values
      $response->addCommand(new ReplaceCommand(
        '#edit-total',
        '<input data-drupal-selector="edit-total" disabled="disabled" type="text" id="edit-total" name="total" value="'.$simbol.''.$converted.'" size="35" maxlength="128" placeholder="Total convertido" class="form-text">'));
      $response->addCommand(new ReplaceCommand(
        '#edit-total--description',
        '<div id="edit-total--description" class="description"></div>'));
      // clear the variable
      unset($message);
      // send the response
      return $response;

    }else{
      // build the AJAX response when there are errors
      $response = new AjaxResponse();
      // set a message
      $response->addCommand(new ReplaceCommand(
        '#edit-total',
        '<input data-drupal-selector="edit-total" disabled="disabled" type="text" id="edit-total" name="total" value="'.$message2.'" size="35" maxlength="128" placeholder="Total convertido" class="form-text">'));

      // show every error generated
      foreach ($errors as &$error) {
        $response->addCommand(new ReplaceCommand(
          '#edit-total--description',
          '<div id="edit-total--description" class="description error">'.$error.'</div>'));
      }
      // clear the variable
      unset($message);
      // send the response
      return $response;
    }
  }
}