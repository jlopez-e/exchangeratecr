<?php

namespace Drupal\exchangeratecr\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ExchangeratecrBlockForm extends FormBase{

//  /**
//   * @var \Drupal\exchangeratecr\ServiceDataBCCR
//   */
//  protected $serviceDataBCCR;
//
//  /**
//   * {@inheritdoc}
//   */
//  public function __construct(ServiceDataBCCR $serviceDataBCCR) {
//    $this->$serviceDataBCCR = $serviceDataBCCR;
//  }

//  /**
//   * {@inheritdoc}
//   */
//  public static function create(ContainerInterface $container) {
//    return new static($container->get('exchangeratecr.data_bccr_service'));
//  }

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
        //   'callback' => 'Drupal\exchangeratecr\Controller\ExchangeratecrController::currencyConverter',
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
