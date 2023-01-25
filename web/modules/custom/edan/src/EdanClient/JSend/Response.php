<?php

namespace Drupal\edan\EdanClient\JSend;

/**
 * Class Response
 *
 * @package Drupal\edan\EdanClient\JSend
 * @author Chintan Desai <cdesai@quotient-inc.com>
 * @since 1.0.0
 */
class Response implements JSendInterface, \JsonSerializable {
  protected $status;
  protected $message;
  protected $data;

  // used for error response
  protected $code;
  protected $exception;

  // used for debugging
  protected $debugData;

  public function __construct() {
    // default to success
    $this->status = NULL;
    $this->message = NULL;
    $this->data = NULL;
    $this->debugData = NULL;
  }

  /**
   * @return string Status type (i.e. success, fail or error).
   */
  public function getStatus() {
    return $this->status;
  }

  public function getMessage() {
    return $this->message;
  }

  public function getData() {
    return $this->data;
  }

  public function getCode() {
    return $this->code;
  }

  public function getException() {
    return $this->exception;
  }

  public function getDebugData($assoc = FALSE) {
    // convert to associative array
    if ($assoc) {
      return json_decode(json_encode($this->debugData), TRUE);
    }

    return $this->debugData;
  }

  public function setDebugData($debugData) {
    $this->debugData = $debugData;
  }

  public function hasDebugData() {
    return (NULL != $this->debugData);
  }

  /**
   * {@inheritdoc}
   */
  public function success(array $data = NULL) {
    // TODO: Implement success() method.
    $this->status = static::STATUS_SUCCESS;
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function fail(array $data = NULL) {
    $this->status = static::STATUS_FAIL;
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function error($message,
                        $code = NULL,
                        \Exception $exception = NULL,
                        array $data = NULL) {
    $this->status = static::STATUS_ERROR;
    $this->message = $message;
    $this->code = $code;
    $this->exception = $exception;
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    $serialized = array(
      'status' => $this->status,
      'message' => $this->message,
      'data' => $this->data,
    );

    if (static::STATUS_ERROR == $this->status) {
      $serialized['code'] = $this->code;
      $serialized['exception'] = $this->exception;
    }

    // attach debug data if we have any
    if (NULL != $this->debugData) {
      // convert to associative array
      $serialized['debug'] = $this->getDebugData(TRUE);
    }

    return $serialized;
  }

}

