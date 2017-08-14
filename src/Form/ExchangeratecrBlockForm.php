<?php

namespace Drupal\exchangeratecr\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\exchangeratecr\Controller\ExchangeratecrController;

/**
 * Lorem Ipsum block form
 */
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

    $options = [
      'CRC' => $this->t('Costa rican colon'),
      'USD' => $this->t('US dollar')
    ];

    $form['test'] = array(
      '#type' => 'label',
      '#title' => $this->t('"The converted amounts are estimates due to currency variability."'),
      '#attributes' => array(
        'class' => 'leyenda',
      ),
    );

    $form['amount'] = array(
      '#type' => 'number',
      '#title' => $this->t('Enter the amount in USD'),
      '#min' => 0,
//      '#required' => TRUE,
    );
    $form['currency'] = array(
      '#type' => 'select',
      '#title' => $this->t('Currency to convert.'),
      '#options' => $options,
     '#description' => $this->t('Select the currency to convert to find its equivalent.'),
    );
    $form['total'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Total in Costa Rican colon'),
      '#placeholder' => $this->t('Total converted'),
      '#disabled' => true,
      '#description' => $this->t(''),
      '#size' => '35',
    );
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
