<?php

use Stormboard\Exception\DataValidationException;
use Stormboard\JSON;
use GuzzleHttp\Client as HTTPClient;
use GuzzleHttp\Exception\BadResponseException;

/**
 * PfizerJira OAuth2 Token Class
 *
 * @package             HybridAuth providers package
 * @author              Michael Bollman
 */

class Hybrid_Providers_PfizerJira extends Hybrid_Provider_Model_OAuth2{

  protected $tokenUrl = 'https://prodfederate.pfizer.com/as/token.oauth2?grant_type=client_credentials';
  protected $echoUrl = 'https://easi-echo-service-dev.cloudhub.io/echo';

  /**
   * Retrieve a current access_token
   *
   * @return stdClass
   */
  public function refreshToken(){

    return $this->generateBasicAuthToken();
  }

  /**
   * February 2020, they switched to Basic Auth, but Jira like OAuth,
   * so we're piggybacking on the refreshToken to set the auth
   *
   * @return stdClass
   */
  private function generateBasicAuthToken(): stdClass{

    $stdClass = new stdClass();
    $stdClass->access_token = time();
    $stdClass->token_type = 'Basic';
    $stdClass->expires_in = 3600 * 24 * 365 * 5; // 5 years
    $stdClass->authorization = base64_encode($this->config['keys']['id'].':'.$this->config['keys']['secret']);

    return $stdClass;
  }
  /**
   * Retrieve a current access_token
   *
   * @return stdClass
   */
  private function refreshOAuthToken(): stdClass{

    $token = null;
    $client = new HTTPClient();

    try{
      $res = $client->post($this->tokenUrl, [
        'headers' => [
          'Authorization' => 'Basic '.base64_encode($this->config['keys']['id'].':'.$this->config['keys']['secret'])
        ],
      ]);

      $token = JSON::decode((string)$res->getBody(), false);
    }catch(BadResponseException $e){
      Stormboard\Log::error('PfizerDev RefreshToken Error', [
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
      ]);
    }

    return $token;
  }

  /**
   * Attempt to ping the /echo service on Pfizer's Cloud API Proxy
   * This is a debugging endpoint that is not wired up in any way (yet).
   *
   * @param $credentials
   * @return bool
   */
  public function ping($credentials): bool{

    $client = new HTTPClient();

    try{
      if(!is_array($credentials) || empty($credentials['access_token'])){
        throw new DataValidationException('Access Token is not defined', 500);
      }

      $params = [
        'first' => 'one',
        'second' => 'two'
      ];

      $res = $client->post($this->echoUrl, [
        'form_params' => $params,
        'headers' => [
          'Authorization' => 'Bearer '.$credentials['access_token']
        ],
      ]);

      $response = (string)$res->getBody();

      Stormboard\Log::info('PfizerDev Pong', [
        'code' => $res->getStatusCode(),
        'response' => $response,
      ]);

      return $response === http_build_query($params);
    }catch(DataValidationException | BadResponseException $e){
      Stormboard\Log::error('PfizerDev Ping Error', [
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
      ]);
    }

    return false;
  }
}
