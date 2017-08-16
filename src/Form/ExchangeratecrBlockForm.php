<?php

namespace Drupal\exchangeratecr\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\exchangeratecr\Controller\ExchangeratecrController;

class ExchangeratecrBlockForm extends FormBase {

  protected $tipocambio;

  public function __construct() {
    $this->tipocambio = new ExchangeratecrController();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'exchangeratecr_block_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    //Just to know what is the dollar buy rate according to Banco Central de Costa RIca
    $form['buy_rate'] = [
      '#markup' =>"<p >".$this->t('Buy Rate :')."</p>",
    ];

    //Just to know what is the dollar sell rate according to Banco Central de Costa RIca
    $form['sell_rate'] = [
      '#markup' =>"<p >".$this->t('Sell Rate :')."</p>",
    ];

    //Amount to Convert
    $form['amount'] = array(
      '#type' => 'number',
      '#title' => $this->t('Amount'),
      '#min' => 0,
      '#required' => TRUE,
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
      '#options' => $options
    );

    //Currency to I want to convert
    $form['currency_to'] = array(
      '#type' => 'select',
      '#title' => $this->t('To'),
      '#options' => $options
    );

    //Input to show the conversion result
    $form['total'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Result'),
      '#placeholder' => $this->t('Total converted'),
      '#size' => '35',
      '#attributes' => ['readonly' => 'readonly'],
    );

//    $form['total'] = array(
//      '#type' => 'textfield',
//      '#title' => $this->t('Total in Costa Rican colon'),
//      '#placeholder' => $this->t('Total converted'),
//      '#disabled' => true,
//      '#description' => $this->t(''),
//      '#size' => '35',
//    );
//    $form['actions']['#type'] = 'actions';


    $form['actions'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Convert'),
      '#ajax' => array (
        'callback' => 'Drupal\exchangeratecr\Controller\ExchangeratecrController::currencyConverter',
//        'wrapper' => 'edit-currency',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
