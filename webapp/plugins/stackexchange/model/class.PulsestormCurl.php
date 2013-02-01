<?php
class PulsestormCurl
{
    public function getUrlDoFollowWithHeader($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_FAILONERROR,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_ENCODING , "gzip");    //http://api.stackoverflow.com/1.1/usage/gzip
        $response = curl_exec($ch);
        return $response;        
    
    }
    
    public function getUrl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_FAILONERROR,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_ENCODING , "gzip");    //http://api.stackoverflow.com/1.1/usage/gzip
        $response = curl_exec($ch);
        return $response;        
    }
    
    public function postToUrl($url, $data)
    {
        $data = http_build_query($data);        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array (
            "Content-Type: application/x-www-form-urlencoded; charset=utf-8"
        ));
        $results = curl_exec($ch);
        return $results;    
    }
}