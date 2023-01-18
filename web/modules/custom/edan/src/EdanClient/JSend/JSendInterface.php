<?php

namespace Drupal\edan\EdanClient\JSend;

/**
 * Interface JSendInterface
 *
 * @package Drupal\edan\EdanClient\JSend
 * @author Chintan Desai <cdesai@quotient-inc.com>
 * @see https://labs.omniti.com/labs/jsend          For JSend Specification
 * @since 1.0.0
 */
interface JSendInterface {
  const STATUS_SUCCESS = 'success';
  const STATUS_FAIL = 'fail';
  const STATUS_ERROR = 'error';

  // Methods needed to implemented;

  /**
   * Request was handled successfully, and some data was returned by the API.
   * The JSend object is used as a simple envelope for the results.
   *
   * @param array|NULL $data
   */
  public function success(array $data = NULL);

  /**
   * There was a problem with the data submitted, or some pre-condition of the
   * API call wasn't satisfied.
   *
   * @param array|NULL $data
   */
  public function fail(array $data = NULL);

  /**
   * An error occurred in processing the request, i.e. an exception was thrown.
   *
   * @param $message
   * @param null $code
   * @param \Exception|NULL $exception
   * @param array|NULL $data
   */
  public function error($message,
                        $code = NULL,
                        \Exception $exception = NULL,
                        array $data = NULL);


}
