<?php

namespace Drupal\eventbrite_attendees\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\eventbrite_attendees\Eventbrite\ApiClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class WebhookController.
 */
class WebhookController extends ControllerBase {

    /**
     * @var \Drupal\Core\Logger\LoggerChannelFactory
     */
    protected $logger;


    /**
     * @var \Drupal\Core\Queue\QueueInterface
     */
    protected $queue;


    /**
   * Capture.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *
   *
   */
  public function run(Request $request) {

      $response = new Response();

      $this->logger = \Drupal::logger('eventbrite_attendees');

      $payload = $request->getContent();

      $payloadData = json_decode($payload, true);

      if (empty($payload)) {
          $message = 'The payload was empty.';

          $this->logger->error($message);
          $response->setContent($message);
          return $response;
      }

//      $api_client = \Drupal::service('eventbrite_attendees.api_client');

      $this->logger->info('Webhook eventbrite triggered with: %payload', ['%payload' => $payload]);


      $response->setContent('OK');
      return $response;

  }



}
