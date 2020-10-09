<?php

namespace Drupal\eventbrite_attendees\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eventbrite_attendees\UsersSync;

/**
 * Class Sync
 *
 * @package Drupal\eventbrite_attendees\Form
 */
class Sync extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eventbrite_attendees_users_sync';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $oauth_token = $this->config('eventbrite_attendees.settings')->get('oauth_token');
    $savedEventId = $this->config('eventbrite_attendees.settings')->get('event_id');
      $last_eventbrite_id = $this->config('eventbrite_attendees.settings')->get('last_attendee_id');


    if (!$oauth_token) {
      \Drupal::messenger()->addError('No OAuth token found. Please visit the settings page and provide your personal OAuth token.');
      return $form;
    }

    $form['event_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event ID'),
        '#default_value' => $savedEventId ? $savedEventId : ''
    ];

    $form['attendees_query'] = [
      '#type' => 'button',
      '#value' => $this->t('Sync Attendees Manually'),
      '#ajax' => [
        'callback' => '::doAttendeesSync',
        'event' => 'click',
        'wrapper' => 'eventbrite-sync-users',
      ],
    ];


    $form['results_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sync Results'),
      '#attributes' => [
        'id' => 'eventbrite-sync-users',
      ],

      'data' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#prefix' => '<strong>' . $this->t('Eventbrite -> Drupal users since '.$last_eventbrite_id.': ') . '</strong>',
        '#attributes' => [
          'class' => ['eventbrite-sync-users']
        ]
      ],
    ];

    return $form;
  }

  /**
   * Triggers the creation of Drupal users for Eventbrite attendees
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function doAttendeesSync(array $form, FormStateInterface $form_state) {
    $event_id = $this->config('eventbrite_attendees.settings')->get('event_id');

    $last_eventbrite_id = $this->config('eventbrite_attendees.settings')->get('last_attendee_id');
    ob_start();

    $usersSync = new UsersSync();
    $attendees = $usersSync->getNewAttendees($event_id, $last_eventbrite_id );


    print "<h6>TOTAL EVENTBRITE ATTENDEES FETCHED: ".count($attendees).'</h6>';
    $r = $usersSync->createUsersFromAttendees($attendees);

    print "<h6>TOTAL ERRORS PROCESSING USERS: ".count($r['errors']).'</h6>';
    print_r($r['errors']);

    print "<h6>FOUND ".count($r['duplicated']).' DUPLICATED EMAILS FROM EVENTBRITE: </h6>';
    print_r($r['duplicated']);


    print "<h6>TOTAL DRUPAL USERS CREATED/EDITED: ".count($r['users']).'</h6>';
    print_r($r['users']);



    $o = ob_get_contents();
    ob_end_clean();

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('.eventbrite-sync-users', $o));
    return $response;
  }



  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
