<?php
namespace Drupal\eventbrite_attendees\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class Settings extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'eventbrite_attendees_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
      'eventbrite_attendees.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $oauth_token = $this->config('eventbrite_attendees.settings')->get('oauth_token');

    $oauth_token_desc = $this->t('Invalid token');

    if ( $oauth_token ){
      $oauth_token_desc = $this->t('Valid token. ');
    }

    $form['eventbrite_attendees_oauth_token'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Private API Token'),
      '#description' => $oauth_token_desc,
      '#maxlength' => 64,
      '#default_value' => $oauth_token ? $oauth_token : '',
    );


    $event_id = $this->config('eventbrite_attendees.settings')->get('event_id');
    $form['eventbrite_attendees_event_id'] = [
        '#type'   => 'textfield',
        '#title'  => $this->t('Event ID'),
        '#maxlength' => 64,
        '#default_value' => $event_id ? $event_id : ''

    ];




    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $api_client = \Drupal::service('eventbrite_attendees.api_client');
    $api_client->setToken($form_state->getValue('eventbrite_attendees_oauth_token'));

    $valid = !empty($api_client->getUserMe());
    if ( !$valid ) {
      $form_state->setErrorByName(
        'eventbrite_attendees_oauth_token',
        $this->t('Invalid oauth token provided.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('eventbrite_attendees.settings')
      ->set('oauth_token', $form_state->getValue('eventbrite_attendees_oauth_token'));

    $eventId = $form_state->getValue('eventbrite_attendees_event_id');
    if(!empty($eventId)){
        $this->config('eventbrite_attendees.settings')
            ->set('event_id', $eventId);
    }
    $this->config('eventbrite_attendees.settings')->save();

    parent::submitForm($form, $form_state);
  }
}
