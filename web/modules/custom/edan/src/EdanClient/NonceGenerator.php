<?php

namespace Drupal\edan\EdanClient;


class NonceGenerator {
  const DEFAULT_LENGTH = 15;
  const DEFAULT_TOKENS = "abcdefghijklmnopqrstuvwxyz0123456789";

  private $length;
  private $tokenString;

  private static $currentNonce;

  /**
   * NonceGenerator constructor.
   * @param int $length Length of nonce.
   * @param string $tokenString String of characters that can be used in nonce.
   *  in a nonce.
   */
  public function __construct($length = self::DEFAULT_LENGTH,
                              $tokenString = self::DEFAULT_TOKENS) {
    $this->length = $length;
    $this->tokenString = $tokenString;
  }

  /**
   * @return string Randomly generated nonce.
   */
  public function next() {
    $this->randomize($this->length, $this->tokenString);
    return self::$currentNonce;
  }

  /**
   * @param int $length Length of nonce.
   * @param string $tokenString String of characters that can be used in nonce.
   */
  private static function randomize($length, $tokenString) {
    self::$currentNonce = self::generate($length, $tokenString);
  }

  /**
   * @param int $length Length of nonce.
   * @param string $tokenString String of characters that can be used in nonce.
   * @return string Randomly generated nonce.
   */
  private static function generate($length, $tokenString) {
    $nonce = "";
    $i = 0;

    while ($i < $length) {
      $randomInt = random_int(0, strlen($tokenString) - 1);

      $char = substr($tokenString, $randomInt, 1);
      if (!strstr($nonce, $char)) {
        $nonce .= $char;
        $i++;
      }
    }

    return $nonce;
  }
}

