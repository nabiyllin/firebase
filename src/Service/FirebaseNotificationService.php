<?php

namespace Drupal\firebase\Service;

/**
 * Creates an interface to push notification to mobile devices using Firebase.
 *
 * @see https://firebase.google.com
 */
class FirebaseNotificationService {

  /**
   * Your firebase API Key.
   *
   * @var string
   */
  private $firebaseKey;

  /**
   * Firebase's endpoint service.
   *
   * @var string
   */
  private $firebaseEndpoint;

  /**
   * Priority of each push notification.
   *
   * @var string
   */
  private $priority = 'high';

  /**
   * Class contructor.
   */
  public function __construct() {
    $config = \Drupal::config('firebase.configuration');
    $this->firebaseKey = $config->get('firebase_server_key');
    $this->firebaseEndpoint = $config->get('firebase_endpoint');
  }

  /**
   * Execute the push notification.
   *
   * @param string $token
   *   Device token.
   * @param array $param
   *   Parameters for payload.
   *
   * @return object
   *   Firebase's Cloud Messassing response.
   */
  private function sendPushNotification($token, array $param) {
    // Build the header of our request to Firebase.
    // The header is composed by Content-Type and Authorization.
    // The Authorization is our server key, which needs to be provided
    // by the admin interface.
    // @see \Drupal\firebase\Form\ConfigurationForm.php
    $headers = $this::buildHeader();

    // Build the body of our request.
    // The body is composed by an array of data.
    $body = $this::buildMessage($token, $param);

    $client = \Drupal::httpClient();

    return $client->post($this->firebaseEndpoint, [
      'headers' => $headers,
      'body' => $body,
    ]);
  }

  /**
   * Builds the push notification header.
   */
  private function buildHeader() {
    return [
      'Content-Type' => 'application/json',
      'Authorization' => 'key=' . $this->firebaseKey,
    ];
  }

  /**
   * Builds the push notification body.
   *
   * @param string $token
   *   Device token.
   * @param array $param
   *   Data for payload.
   *
   * @return json
   *   Prepared payload for push notification.
   */
  private function buildMessage($token, array $param) {
    // We need at least the notification title and message text.
    // Check if these two information are present on $param.
    if ($this::isParamValid($param)) {
      $title = $param['title'];
      $body = $param['body'];
    }

    // This is the core notification body.
    $message = [
      'to' => $token,
      'notification' => [
        'title' => $title,
        'body' => $body,
      ],
      'priority' => $this->priority,
    ];

    // If an icon is available, adds to notification body.
    if (isset($param['icon'])) {
      $message['notification']['icon'] = $param['icon'];
    }

    // If data is available, adds to notification body.
    // Data is not displayed to app users. It is usually used to send
    // data to be processed by the app.
    if (isset($param['data']) && $this::checkReservedKeywords($param['data'])) {
      $message['data'] = $param['data'];
    }

    return json_encode($message);
  }

  /**
   * Validate mandatory data on received parameters.
   *
   * @param array $param
   *   Params that builds Push notification payload.
   *
   * @return bool
   *   TRUE if required data is present, and FALSE if not.
   */
  private function isParamValid(array $param) {
    if (!empty($param['title']) && !empty($param['body'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Validate reserved keywords on data.
   *
   * The key should not be a reserved word
   * ("from" or any word starting with "google" or "gcm").
   * Do not use any of the words defined here
   * https://firebase.google.com/docs/cloud-messaging/http-server-ref.
   *
   * Not checking ALL reserved keywords. Just eliminating the common-ones.
   * Created this function to document this important point of attention.
   *
   * @param array $data
   *   Params that builds Push notification payload.
   *
   * @return bool
   *   TRUE if keys are fine, and FALSE if not.
   */
  private function checkReservedKeywords(array $data) {
    foreach ($param as $key => $value) {
      if (preg_match('/(^from$)|(^gcm)|(^google)/', $key)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Sends the push notification.
   *
   * @param string $token
   *   Firebase token that identify each device.
   * @param array $param
   *   Parameters for payload. Expected values are:
   *   - $param['title']
   *     Title of push message
   *   - $param['body']
   *     Body of push message
   *   Optional values are:
   *   - $param['icon']
   *     Icon to be displayed. If none is given, the App's icon will be used.
   *   - $param['data']
   *     Send extra information to device. Not displayed to users.
   *
   * @return bool
   *   TRUE if the push was sent successfully, and FALSE if not.
   */
  public function send($token, array $param) {
    // We absolutely need the token. If it was not provided, return early.
    if (empty($token)) {
      return FALSE;
    }

    $response = $this::sendPushNotification($token, $param);
    $errorMessage = reset(json_decode($response->getBody())->results);

    // Common errors:
    // - Authentication Error
    //   The Server Key is invalid.
    // - Invalid Registration Token
    //   The token (generated by app) is not recognized by Firebase.
    // @see https://firebase.google.com/docs/cloud-messaging/http-server-ref#error-codes
    if ($response->getStatusCode() === 200 && !isset($errorMessage->error)) {
      return TRUE;
    }
    else {
      // Something went wrong and no notification was sent.
      \Drupal::logger('Firebae')->notice('@module:  @error',
        [
          '@module' => 'Firebase Notification',
          '@error' => $errorMessage->error,
        ]);
    }
  }

}
