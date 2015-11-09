<?php

class DropboxV2Client extends OAuth2Client
{
  public $curl_header = array('Authorization: Bearer ' . $this->api->access_token);


	// public function authenticate( $code )
	// {
	// 	$params = array(
	// 		"grant_type"    => "authorization_code",
	// 		"code"          => $code,
	// 		"redirect_uri"  => $this->redirect_uri,
	// 	);
  //
	// 	$response = $this->request( $this->token_url, $params, $this->curl_authenticate_method );
  //
	// 	$response = $this->parseRequestResult( $response );
  //
	// 	if( ! $response || ! isset( $response->access_token ) ){
	// 		throw new Exception( "The Authorization Service has return: " . $response->message );
	// 	}
  //
	// 	if( isset( $response->access_token  ) )  $this->access_token           = $response->access_token;
	// 	if( isset( $response->refresh_token ) ) $this->refresh_token           = $response->refresh_token;
	// 	if( isset( $response->expires_in    ) ) $this->access_token_expires_in = $response->expires_in;
  //
	// 	// calculate when the access token expire
	// 	if( isset($response->expires_in)) {
	// 		$this->access_token_expires_at = time() + $response->expires_in;
	// 	}
  //
	// 	return $response;
	// }


	// -- utilities
  private function request( $url, $params=false, $type="GET" )
	{
		Hybrid_Logger::info( "Enter OAuth2Client::request( $url )" );
		Hybrid_Logger::debug( "OAuth2Client::request(). dump request params: ", serialize( $params ) );

		if( $type == "GET" ){
			$url = $url . ( strpos( $url, '?' ) ? '&' : '?' ) . http_build_query($params, '', '&');
		}

		$this->http_info = array();
		$ch = curl_init();
    if( $type == "POST" ){
      $this->curl_header[] = 'Content-Type: application/json';
    }

		curl_setopt($ch, CURLOPT_URL            , $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1 );
		curl_setopt($ch, CURLOPT_TIMEOUT        , $this->curl_time_out );
		curl_setopt($ch, CURLOPT_USERAGENT      , $this->curl_useragent );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $this->curl_connect_time_out );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , $this->curl_ssl_verifypeer );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , $this->curl_ssl_verifyhost );
		curl_setopt($ch, CURLOPT_HTTPHEADER     , $this->curl_header );

		if($this->curl_proxy){
			curl_setopt( $ch, CURLOPT_PROXY        , $this->curl_proxy);
		}

    if( $type == "POST" ){
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode( $params ));
    }

		Hybrid_Logger::debug( "OAuth2Client::request(). dump request url: ", serialize( $url ) );

		$response = curl_exec($ch);
		if( $response === FALSE ) {
				Hybrid_Logger::error( "OAuth2Client::request(). curl_exec error: ", curl_error($ch) );
		}
		Hybrid_Logger::debug( "OAuth2Client::request(). dump request info: ", serialize( curl_getinfo($ch) ) );
		Hybrid_Logger::debug( "OAuth2Client::request(). dump request result: ", serialize( $response ) );

		$this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ch));

		curl_close ($ch);

		return $response;
	}
