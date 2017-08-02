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
    $config = \Drupal::config('firebase.settings');
    $this->firebaseKey = $config->get('server_key');
    $this->firebaseEndpoint = $config->get('endpoint');
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
    if (!$body = $this::buildMessage($token, $param)) {
      return FALSE;
    }

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
    // Parameters will be okay if we have at least the title and body.
    // If we do NOT have minimum fields, we assume it is a silent push.
    // Silent pushes need parameter data. So we check for $param['data'].
    // If these conditions are not met, we set a default value, just to go
    // through the push notification.
    if (!$this::validParam($param)) {
      return FALSE;
    }

    // This is the core notification body.
    $message = [
      'to' => $token,
      'priority' => $this->priority,
    ];

    // Since we validated 'title' and 'body' previously,
    // its okay to check only title here.
    if (!empty($param['title'])) {
      $message['notification'] = [
        'title' => $param['title'],
        'body' => $param['body'],
      ];
    }

    // If data is available, adds to notification body.
    // Data is not displayed to app users. It is usually used to send
    // some data to be processed by the app.
    if (!empty($param['data'])) {
      $message['data'] = $param['data'];
    }

    // If an icon, sound or click_action are available,
    // add them to notification body.
    if (!empty($param['icon'])) {
      $message['icon'] = $param['icon'];
    }
    if (!empty($param['sound'])) {
      $message['sound'] = $param['sound'];
    }
    if (!empty($param['click_action'])) {
      $message['click_action'] = $param['click_action'];
    }

    return json_encode($message);
  }

  /**
   * Validate mandatory data on received parameters.
   *
   * @param array $param
   *   Params that builds Push notification payload.
   */
  private function validParam(array $param) {
    // We either have the title and body OR
    // it's a silent push - require $param['data'].
    if (!empty($param['title']) && !empty($param['body'])) {
      return TRUE;
    }

    if (isset($param['data']) && $this::checkReservedKeywords($param['data'])) {
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
   * Not checking ALL reserved keywords. Just eliminating the common ones.
   * Created this function to document this important restriction.
   *
   * @param array $data
   *   Params that builds Push notification payload.
   *
   * @return bool
   *   TRUE if keys are fine, and FALSE if not.
   */
  private function checkReservedKeywords(array $data) {
    foreach ($data as $key => $value) {
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
   *   - $param['sound']
   *     Sound to play. If none is given, the App's default will be used.
   *   - $param['click_action']
   *     The action associated with a user click on the notification.
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

    if (!$response = $this::sendPushNotification($token, $param)) {
      return FALSE;
    }
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
      return FALSE;
    }
  }

}
