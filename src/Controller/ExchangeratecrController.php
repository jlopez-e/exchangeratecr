<?php

namespace Drupal\exchangeratecr\Controller;

use Drupal\Core\Url;
// Change following https://www.drupal.org/node/2457593
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;


/**
 * Controller routines
 */
class ExchangeratecrController{
  /**
   * This callback is mapped to the path
   * 'exchangeratecr/generate/{paragraphs}/{phrases}'.
   */
  public function generate($currencyx, $amountx) {
  }

  public function currencyConverter ($form, &$form_state) {
    // Wb Service parameters //
    // dynamic date, and with the format dd/mm/aaaa
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