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
        $feed = $this->getOwnMedia(1, 0);
        return $feed->data[0];
    }
    public function getOwnMedia($count = 0, $min_timestamp = 0){
		$options = array("access_token" => $this->token);
		if($count > 0){
			$options["count"] = $count;
		}
		if($min_timestamp > 0){
			$options['min_timestamp'] = $min_timestamp;
		}
		$feed = $this->callApi("/users/self/media/recent",$options);
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
