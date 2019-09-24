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

  protected $tokenUrl = 'https://devfederate.pfizer.com/as/token.oauth2?grant_type=client_credentials';
  protected $echoUrl = 'https://easi-echo-service-dev.cloudhub.io/echo';

  /**
   * Retrieve a current access_token
   *
   * @return stdClass
   */
  public function refreshToken(){

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

      $res = $client->get($this->echoUrl, [
        'headers' => [
          'Authorization' => 'Bearer '.$credentials['access_token']
        ],
      ]);

      $response = (string)$res->getBody();
      Stormboard\Log::info('PfizerDev Pong', [
        'code' => $res->getStatusCode(),
        'response' => (string)$res->getBody(),
      ]);

      return true;
    }catch(DataValidationException | BadResponseException $e){
      Stormboard\Log::error('PfizerDev Ping Error', [
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
      ]);
    }

    return false;
  }
}
