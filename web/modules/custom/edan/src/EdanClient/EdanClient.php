<?php

namespace Drupal\edan\EdanClient;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\edan\EdanClient\JSend\Response;
use Drupal\edan\EdanClient\NonceGenerator;


class EdanClient {
  const REQUEST_UNSIGNED = 0;
  const REQUEST_SIGNED = 1;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  // EDAN settings
  private $baseUri;
  private $appId;
  private $authToken;
  private $version;

  private $debug = FALSE;
  private $nonceGenerator;

  private $edanConfigs;
  private $helper;
  private $client;
  /**
   * EdanClient constructor.
   *
   * @param $config
   */

  /**
   * Constructs a ProviderRepository instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   (optional) The cache backend.
   * @param int $max_age
   *   (optional) How long the cache data should be kept. Defaults to a week.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $config = $config_factory->get('edan.settings');
	  $this->baseUri = $config->get('api.url');
	  $this->appId = $config->get('api.app_id');
	  $this->authToken = $config->get('api.auth_token');
	  $this->version = $config->get('api.version');
	  $this->httpClient = new Client([
		  'base_uri' => $this->baseUri
	  ]);
		$this->debug = $config->get('api.debug');
	  $this->nonceGenerator = new NonceGenerator();
  }

  /**
   * @param bool $debug
   */
  public function setDebug($debug) {
    $this->debug = $debug;
  }

  public function setAuthentication($appId, $authToken) {
	  $this->appId = $appId;
	  $this->authToken = $authToken;
  }

	public function setBaseUri($baseUri) {
		$this->baseUri = $baseUri;
		$this->httpClient = new Client([
			'base_uri' => $this->baseUri
		]);
	}
  public function getBaseUri() {
    return $this->baseUri;
  }

	/**
   * @param $parameters array Associative array of query parameters.
   * @param $endpoint string EDAN endpoint that needs to be queried.
   * @return \Drupal\edan\EdanClient\JSend\Response prepared response object
   */
  public function fetchEdanResponse($parameters, $endpoint, $method = 'GET') {

    $jSendResponse = new Response();
    $responseObj = new \stdClass();

    $this->addDefaultParameters($parameters);
   // $queryString = http_build_query($parameters, NULL, '&', PHP_QUERY_RFC3986);
    $queryString = $this->buildQueryString($parameters);
    $headers = $this->getHeaders($queryString);
    $path = sprintf($endpoint, $this->version);
    $uri = '';
    if ($method == 'POST'){
      $uri = $path;

    } else {
      $uri = $path .'?'. $queryString;
    }
    try {
      $response = new Response();
      if ($method == 'POST'){
        //$encodedParams= 'encodedRequest='. base64_encode($queryString);
        $response = $this->httpClient->request('POST', $uri , [
          'http_errors' => FALSE,
          'headers' => $headers,
          'form_params' => [
            'encodedRequest' => base64_encode($queryString)
          ],
        ]);
      } else {
        $response = $this->httpClient->request($method, $uri , [
          'http_errors' => FALSE,
          'headers' => $headers,
        ]);
      }
      $data = json_decode($response->getBody(), TRUE);
      if (200 == $response->getStatusCode()) {
        $jSendResponse->success($data);
      }
      else {
        $jSendResponse->fail($data);
      }

      $responseObj->httpStatus = $response->getStatusCode() . " " . $response->getReasonPhrase();
      $responseObj->headers = $response->getHeaders();
    }

    catch (RequestException $e) {
      $jSendResponse->error(
        'RequestException while fetching response.',
        $e->getCode(),
        $e);
    }
    catch(\Exception $e) {
      $jSendResponse->error(
        'Exception while fetching response.',
        $e->getCode(),
        $e);
    }

    if ( $this->debug ) {
      $requestObj = new \stdClass();
      $requestObj->baseUri = $this->baseUri;
      $requestObj->path = $path;
      $requestObj->headers = $headers;
      $requestObj->queryString = urldecode($queryString);
      $requestObj->parameters = $parameters;

      $debugData = new \stdClass();
      $debugData->request = $requestObj;
      $debugData->response = $responseObj;
      $jSendResponse->setDebugData($debugData);
    }
    if (\Drupal::request()->query->get('edan_dump')) {
      dump($jSendResponse);
    }

    return $jSendResponse;
  }

  private static function addDefaultParameters(&$parameters) {
    if ( !isset($parameters['rows']) ) {
      $parameters['rows'] = 10;
    }

    if ( !isset($parameters['wt']) ) {
      $parameters['wt'] = 'json';
    }
    if ( !isset($parameters['start']) ) {
      $parameters['start'] = 0;
    }

  }

  private function buildQueryString($parameters) {
    $queryString = '';

    foreach($parameters as $key => $query) {
      if (is_array($query)) {
        if ($key === 'fqs') {
          $queryString .= '&fqs='.  urlencode(json_encode($query));
        }
        else {
          foreach($query as $item) {
            $queryString .= '&'. $key .'='. urlencode($item);
          }
        }
      }
      elseif($key == 'search_all') {
      	continue;
      }
      else {
        $queryString .= '&'. $key .'='. urlencode($query);
      }
    }
    return trim($queryString, '&');
  }
  /**
   * Creates the header for the request to EDAN.
   * @param string $queryString Query string to be hashed and encoded.
   * @returns array containing all the elements and signed header value
   */
  private function getHeaders($queryString) {
    $date = date('Y-m-d H:i:s');

    $headers = array(
      'X-AppId' => $this->appId,
      'X-RequestDate' => $date,
      'X-AppVersion' => 'EDANInterface-0.10.1'
    );

//    if (EdanConfigs::REQUEST_SIGNED == $this->edanConfigs->getRequestType()) {
      $this->addAuthenticationHeaders($queryString, $date, $headers);
//    }

    return $headers;
  }

  /**
   * For signed/T2 requests
   * @param string $queryString Query string to be hashed and encoded.
   * @param string $date Date string used in header.
   * @param array $headers Header array to which authentication headers will be added.
   */
  private function addAuthenticationHeaders($queryString, $date, &$headers) {
    $nonce = $this->nonceGenerator->next();
    $content = sprintf(
      "%s\n%s\n%s\n%s",
      $nonce, $queryString, $date, $this->authToken
    );
    $authContent = base64_encode(sha1($content));

    $headers['X-Nonce'] =  $nonce;
    $headers['X-AuthContent'] =  $authContent;
  }

  /**
   * @param $parameters
   * @param $endPoint
   *
   * @return array
   */
  public function processRequest($parameters, $endPoint, $method = 'GET') {
    // Pass EDAN query parameters to EDAN PHP Client and and let it process the
    // request. Client returns a response in JSend format.
    // @see \Drupal\edan\EdanClient\JSendInterface

    $response = $this->fetchEdanResponse($parameters, $endPoint, $method);
    $envelop = [];

    switch($response->getStatus()) {
      case Response::STATUS_SUCCESS:
        $envelop['message'] = "";
        // Mirror numFound (v1.1) and rowCount (v2.0) keys to a generic recordCount variable. Set default recordCount
        $envelop['data'] = $response->getData();
        $envelop['data']['recordCount'] = 0;
        // numFound (v1.1)
        if (array_key_exists('numFound', $envelop['data'])) {
          $envelop['data']['recordCount'] = $envelop['data']['numFound'];
        }
        // rowCount (v2.0)
        elseif (array_key_exists('rowCount', $envelop['data'])) {
          $envelop['data']['recordCount'] = $envelop['data']['rowCount'];
        }
        elseif (isset($envelop['data']['url'])) {
          $envelop['data']['recordCount'] = 1;
        }
        break;

      case Response::STATUS_FAIL:
        $message = $response->getData();
        $envelop['message'] = "Failure to fetch a response form EDAN API.  ";
        $envelop['message'] .= $message['error'] ?? '';
        $envelop['error'] = $message['error'] ?? '';
        $envelop['data'] = NULL;
        \Drupal::logger('edan')->notice('Failure to fetch a response from EDAN API. Enable EDAN API debug mode for more detailed message.');
        \Drupal::logger('edan')->notice(json_encode($response));
        break;

      case Response::STATUS_ERROR:
      default:
        $message = $response->getData();
        $envelop['message'] = "An error occurred while fetching a response form EDAN API.";
        $envelop['message'] .= $message['error'] ?? '';
        $envelop['error'] = $message['error'] ?? '';
        $envelop['data'] = NULL;
        \Drupal::logger('edan')->notice('An error occurred while fetching a response form EDAN API.');
        \Drupal::logger('edan')->notice(json_encode($response));
        watchdog_exception('edan', $response->getException());
        break;
    }

    if ($response->hasDebugData()) {
      $envelop['debug'] = $response->getDebugData(TRUE);
    }

    return $envelop;
  }


}
