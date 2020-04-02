<?php

/**
 * PfizerJiraStaging OAuth2 Token Class
 *
 * @package             HybridAuth providers package
 * @author              Michael Bollman
 */

class Hybrid_Providers_PfizerJiraStaging extends Hybrid_Providers_PfizerJira{

  public function initialize(){
    parent::initialize();

    $this->tokenUrl = 'https://devfederate.pfizer.com/as/token.oauth2?grant_type=client_credentials';
    $this->echoUrl = 'https://easi-echo-service-dev.cloudhub.io/echo';
  }
}
