<?php
//https://weichie.com/blog/curl-api-calls-with-php/
function callAPI($method, $url, $data, $headers = false){
   $curl = curl_init();
   switch ($method){
      case "POST":
         curl_setopt($curl, CURLOPT_POST, 1);
         if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
         break;
      case "PUT":
         curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
         if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);			 					
         break;
      default:
         if ($data)
            $url = sprintf("%s?%s", $url, http_build_query($data));
   }
   // OPTIONS:
   curl_setopt($curl, CURLOPT_URL, $url);
   curl_setopt($curl, CURLOPT_URL, $url);
   if(!$headers){
       curl_setopt($curl, CURLOPT_HTTPHEADER, array(
          'APIKEY: abc',
          'Content-Type: application/json',
       ));
   }else{
       curl_setopt($curl, CURLOPT_HTTPHEADER,array(
       'Content-Type: application/json',
       'User-Agent: php',
       $headers
       ));
   }
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
   // EXECUTE:
   $result = curl_exec($curl);
   if(!$result){die("Connection Failure");}
   curl_close($curl);
   return $result;
}