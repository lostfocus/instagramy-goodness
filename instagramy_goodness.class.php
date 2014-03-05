<?php
class instagramy_goodness {
    protected $token = false;
    protected $userId = false;

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    protected $queryUrl = "https://api.instagram.com/v1";

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token){
        $this->token = $token;
    }

    public function getLastPicture($cached = true){
        $feed = $this->getOwnMedia($cached);
        return $feed->data[0];
    }
    public function getOwnMedia($cached = true){
        $tkey = "ownmedia-".md5("ownmedia".$this->token);
        $feed = unserialize(get_transient($tkey));
        if(!$feed){
            $feed = $this->callApi("/users/self/media/recent",array("access_token" => $this->token));
            set_transient($tkey,$feed,MINUTE_IN_SECONDS);
        }
        return $feed;
    }

    protected function callApi($url,$options = array(),$method = "GET"){
        $queryurl = $this->queryUrl.$url;
        if($method == "GET"){
            $q = array();
            foreach($options as $key => $value){
                $q[] = $key ."=". $value;
            }
            $options = implode("&",$q);
            $queryurl .="?".$options;
            return json_decode(wp_remote_fopen($queryurl));
        }
    }
}
