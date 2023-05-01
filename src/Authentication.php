<?php
namespace Thinesjs\ValorAuth;

use Thinesjs\ValorAuth\Utils;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class Authentication {
    private $client;
    private $username;
    private $password;
    public $shard;
    public $remember;
    public $accessToken;
    public $idToken;
    private $ssid;
    private $clid;
    private $csid;
    private $headers;
    private $address;
    private $clientPlatform;

    public function __construct(Array $credentials = null){
        $this->client = new Client(array('curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_3],'cookies' => true,'http_errors' => false, 'verify'=>false));
        $this->headers = [
            "Accept-Encoding" => "gzip, deflate, br",
            'Content-Type' => 'application/json',
            'User-Agent' => 'RiotClient/62.0.1.4852117.4789131 rso-auth (Windows;10;;Professional, x64)',
            'Host' => 'auth.riotgames.com',
            'Accept-Language' => 'en-US,en;q=0.9',
        ];
        $this->clientPlatform = "ew0KCSJwbGF0Zm9ybVR5cGUiOiAiUEMiLA0KCSJwbGF0Zm9ybU9TIjogIldpbmRvd3MiLA0KCSJwbGF0Zm9ybU9TVmVyc2lvbiI6ICIxMC4wLjE5MDQyLjEuMjU2LjY0Yml0IiwNCgkicGxhdGZvcm1DaGlwc2V0IjogIlVua25vd24iDQp9";
        $this->address = "auth.riotgames.com";
        if($credentials != null){
            if(!isset($credentials["password"])){
                $this->accessToken = $credentials["username"];
                $this->shard = $credentials["shard"];
            }else{
                $this->username = $credentials["username"];
                $this->password = $credentials["password"];
                $this->shard = $credentials["shard"];
                $this->remember = true;

            }
        }
    }

    public function reAuth(){
        if (!isset($_COOKIE["ssid"]) ) return;
        $utils = new Utils();
        $reauth = CookieJar::fromArray([
            'ssid' => $_COOKIE["ssid"]
        ], 'auth.riotgames.com');

        $authResponse = $this->client->request("GET","https://auth.riotgames.com/authorize?redirect_uri=https%3A%2F%2Fplayvalorant.com%2Fopt_in&client_id=play-valorant-web-prod&response_type=token%20id_token&nonce=1&scope=account%20openid", ["cookies"=>$reauth, "allow_redirects"=>false]);
        $location = $authResponse->getHeader("location")[0];
        $this->accessToken = $utils->getBetween("access_token=","&scope",$location);
        $this->idToken = $utils->getBetween("id_token=","&token_type",$location);
        $this->shard = $this->getRegion($this->accessToken);
        $entitlement = $this->getEntitlements($this->accessToken);

        session(['accessToken' => $this->accessToken]);
        session(['entitlements_token' => $entitlement]);
        session(['shard' => $this->shard]);

        return array("accessToken"=>$this->accessToken, "entitlements_token"=>$entitlement,);
    }

    public function collectCookies(){
        $jar = new CookieJar();
        $addr = $this->address;
        $postData = json_decode('{"client_id": "play-valorant-web-prod","nonce": "1","redirect_uri": "https://playvalorant.com/opt_in","response_type": "token id_token","scope": "account openid"}');
        $this->client->request("POST", "https://$addr/api/v1/authorization", ["json"=>$postData, "cookies"=>$jar, "headers"=>$this->headers]);
        return $jar;
    }

    public function authUser(){
        $session = $this->collectCookies();
        $utils = new Utils();
        $addr = $this->address;

        $postData = json_decode('{"type":"auth", "username":"'.$this->username.'", "password":"'.$this->password.'", "remember":'.json_encode($this->remember).'}');
        $response = $this->client->request("PUT","https://$addr/api/v1/authorization",["json"=>$postData, "cookies"=>$session, "headers"=>$this->headers]);
        if(isset(json_decode((string) $response->getBody(),true)["error"])) return json_decode((string) $response->getBody());
        if (json_decode((string)$response->getBody())->type == "multifactor")
        {
            setcookie("asid",$session->getCookieByName("asid")->getValue(),$session->getCookieByName("asid")->getExpires(), "/");

            return "2FA";
        }
        //2FA
        if($this->remember){
            setcookie("csid",$session->getCookieByName("csid")->getValue(),$session->getCookieByName("csid")->getExpires(), "/");
            setcookie("clid",$session->getCookieByName("clid")->getValue(),$session->getCookieByName("clid")->getExpires(), "/");
            setcookie("ssid",$session->getCookieByName("ssid")->getValue(),$session->getCookieByName("ssid")->getExpires(), "/");
            setcookie("shard",$this->shard,$session->getCookieByName("ssid")->getExpires(), "/");
            $this->ssid = $session->getCookieByName("ssid")->getValue();
            $this->csid = $session->getCookieByName("csid")->getValue();
            $this->clid = $session->getCookieByName("clid")->getValue();
        }

        $this->accessToken = $utils->getBetween("access_token=","&scope",(string)$response->getBody());
        $this->idToken = $utils->getBetween("id_token=","&token_type",(string)$response->getBody());
        //dd(json_decode((string)$response->getBody()));
        return $this->accessToken;
    }

    public function requestMfa($code)
    {
        $cookieJar = CookieJar::fromArray([
            'asid' => $_COOKIE['asid']
        ], 'auth.riotgames.com');

        $utils = new Utils();
        $addr = $this->address;
        $putData = json_decode('{"type":"multifactor", "code":"'.$code.'", "rememberDevice":true}');
        $mfaResponse = $this->client->request("PUT","https://auth.riotgames.com/api/v1/authorization",["json"=>$putData, "cookies"=>$cookieJar, "headers"=>$this->headers]);
        if(isset(json_decode((string) $mfaResponse->getBody(),true)["error"])) return json_decode((string) $mfaResponse->getBody());
        setcookie("ssid",$cookieJar->getCookieByName("ssid")->getValue(),$cookieJar->getCookieByName("ssid")->getExpires(), "/");
        setcookie("shard",$this->shard,$cookieJar->getCookieByName("ssid")->getExpires(), "/");
        if($this->remember){
            setcookie("csid",$cookieJar->getCookieByName("csid")->getValue(),$cookieJar->getCookieByName("csid")->getExpires(), "/");
            setcookie("clid",$cookieJar->getCookieByName("clid")->getValue(),$cookieJar->getCookieByName("clid")->getExpires(), "/");
            setcookie("ssid",$cookieJar->getCookieByName("ssid")->getValue(),$cookieJar->getCookieByName("ssid")->getExpires(), "/");
            setcookie("shard",$this->shard,$cookieJar->getCookieByName("ssid")->getExpires(), "/");
            $this->ssid = $cookieJar->getCookieByName("ssid")->getValue();
            $this->csid = $cookieJar->getCookieByName("csid")->getValue();
            $this->clid = $cookieJar->getCookieByName("clid")->getValue();
        }
        $this->accessToken = $utils->getBetween("access_token=","&scope",(string)$mfaResponse->getBody());
        $this->idToken = $utils->getBetween("id_token=","&token_type",(string)$mfaResponse->getBody());
        return $this->accessToken;
    }

    public function getEntitlements(String $accessToken){
        $postData = json_decode('{}');
        $response = $this->client->request("POST","https://entitlements.auth.riotgames.com/api/token/v1",["json"=>$postData, "headers"=>["Authorization"=>"Bearer $accessToken"]]);
        return json_decode((string)$response->getBody())->entitlements_token;
    }

    public function getRegion(String $accessToken){
        $response = $this->client->request("PUT","https://riot-geo.pas.si.riotgames.com/pas/v1/product/valorant",["json"=>['id_token' => $this->idToken], "headers"=>["Authorization"=>"Bearer $accessToken"]]);
        $this->shard = json_decode((string)$response->getBody())->affinities->live;
        return json_decode((string)$response->getBody())->affinities->live;
    }

    public function authenticate($mfa = false, $code = 0){
        $this->collectCookies();
        if (!$mfa)
        {
            $authSession = $this->authUser();
            if ($authSession == "2FA") return "2FA";
        }else
        {
            $authSession = $this->requestMfa($code);
        }
        if(isset($authSession->error)){
            if($authSession->error == "auth_failure") return "{\"error\":\"Invalid username or password\"}";
            return "{\"error\":\"".$authSession->error."\"}";
        }
        $region = $this->getRegion($this->accessToken);
        $entitlement = $this->getEntitlements($this->accessToken);
        $returnArr = array("access_token"=>$this->accessToken,
                     "entitlements_token"=>$entitlement,
                     "shard"=>$this->shard);
        if(isset($this->ssid)){
            $returnArr["ssid"] = $this->ssid;
            $returnArr["csid"] = $this->csid;
            $returnArr["clid"] = $this->clid;
        }
        session(['accessToken' => $this->accessToken]);
        session(['entitlements_token' => $entitlement]);
        session(['shard' => $region]);
        return $returnArr;
    }
// AUTH END //
}
?>
