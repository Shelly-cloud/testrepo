<?php

function encrypt($data,$key,$iv){
    // Storingthe cipher method 
    $ciphering = "AES-128-CTR";

    // Using OpenSSl Encryption method 
    $iv_length = openssl_cipher_iv_length($ciphering);
    $options   = 0;

    // Using openssl_encrypt() function to encrypt the data 
    $result = openssl_encrypt($data, $ciphering, $key, $options, $iv);

    return $result;
}

function decrypt($data,$key,$iv){
    // Storingthe cipher method 
    $ciphering = "AES-128-CTR";

    // Using OpenSSl Encryption method 
    $iv_length = openssl_cipher_iv_length($ciphering);
    $options   = 0;

    // Using openssl_encrypt() function to encrypt the data 
    $result = openssl_decrypt($data, $ciphering, $key, $options, $iv);
    return $result;
}