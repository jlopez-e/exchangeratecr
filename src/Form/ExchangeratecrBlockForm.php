<?php

namespace Drupal\exchangeratecr\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

class ExchangeratecrBlockForm extends FormBase{

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'exchangeratecr_block_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state){

    $startDate=date("j/n/Y");
    $endDate=date("j/n/Y");
    $name="exchangeratecr";
    $sublevels="N";

    $serviceDataBCCR = \Drupal::service('exchangeratecr.data_bccr_service');
    $buyRate = $serviceDataBCCR->getDataFromBancoCentralCR('317',$startDate,$endDate ,$name,$sublevels);
    $sellRate = $serviceDataBCCR->getDataFromBancoCentralCR('318',$startDate,$endDate ,$name,$sublevels);


    //Just to know what is the dollar buy rate according to Banco Central de Costa RIca
    $form['buy_rate'] = [
      '#markup' =>"<p >".$this->t('Buy Rate').' : '.$buyRate."</p>",
    ];

    //Just to know what is the dollar sell rate according to Banco Central de Costa RIca
    $form['sell_rate'] = [
      '#markup' =>"<p >".$this->t('Sell Rate').' : '.$sellRate."</p>",
    ];

    //Amount to Convert
    $form['amount'] = array(
      '#type' => 'number',
      '#title' => $this->t('Amount'),
      '#limit_validation_errors' => array(), // No validation.
    );

    //Options for select: Dollars, Colons
    $options = [
      'CRC' => '₡',
      'USD' => '$'
    ];

    //Currency I want to convert
    $form['currency_from'] = array(
      '#type' => 'select',
      '#title' => $this->t('From'),
      '#options' => $options,
      '#default_value' => 'USD',
    );

    //Currency to I want to convert
    $form['currency_to'] = array(
      '#type' => 'select',
      '#title' => $this->t('To'),
      '#options' => $options,
      '#default_value' => 'CRC',
    );

    //Input to show the conversion result
    $form['total'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Result'),
      '#placeholder' => $this->t('Total converted'),
      '#size' => '35',
      '#attributes' => ['readonly' => 'readonly'],
    );

    $form['actions'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Convert'),
      '#ajax' => array (
        'callback' => 'Drupal\exchangeratecr\Form\ExchangeratecrBlockForm::convertCurrecyCallback',
        'wrapper' => 'edit-currency',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function submitForm(array &$form, FormStateInterface $form_state){
  }

  public function convertCurrecyCallback(array &$form, FormStateInterface $form_state) {

    //Amount to convert
    $amount = $form_state->getValue('amount');

    //Ajax Response
    $response = new AjaxResponse();

    if($amount > 0) {

      //Currency from I want to convert
      $currency_from = $form_state->getValue('currency_from');

      //Service to use the function convertCurrency
      $serviceDataBCCR = \Drupal::service('exchangeratecr.data_bccr_service');

      //Converting result
      $result = number_format($serviceDataBCCR->convertCurrecy($currency_from, $amount), 2, '.', ' ');

      //Sign of the result
      $sign = '';
      switch ($currency_from) {
        case 'CRC':
          $sign = '$ ';
          break;
        case 'USD':
          $sign = '₡ ';
          break;
        default;
          break;
      }
      // set the values
      $response->addCommand(new ReplaceCommand(
        '#edit-total',
        '<input data-drupal-selector="edit-total" disabled="disabled" type="text" id="edit-total" name="total" value="' . $sign . '' . $result . '" size="35" maxlength="128" placeholder="Total convertido" class="form-text">'));
      $response->addCommand(new ReplaceCommand(
        '#edit-total--description',
        '<div id="edit-total--description" class="description"></div>'));

    }else{
      // set the values

      $message = t('The Amount should be greater than 0');
      $response->addCommand(new ReplaceCommand(
        '#edit-total',
        '<input data-drupal-selector="edit-total" disabled="disabled" type="text" id="edit-total" name="total" value="' . $message . '" size="35" maxlength="128" placeholder="Total convertido" class="form-text">'));
    }

    return $response;
  }

}
