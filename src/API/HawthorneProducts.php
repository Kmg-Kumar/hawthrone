<?php


namespace flexiPIM\Hawthorne\API;


use Illuminate\Support\Facades\Schema;

class HawthorneProducts extends HawthorneAuthentication
{
    public function getProductData()
    {
        if (Schema::hasTable('hawthorne_configuration')) {
            if($this->token['status']){

                if(isset($this->hawthorneConfig)){
                    $url = $this->getSignature($this->hawthorneConfig->products_access_url.'?X-ApiKey='.$this->hawthorneConfig->client_key,$this->hawthorneConfig->secret_key);
                    $curl = curl_init($url);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POST, false);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    $curl_response = curl_exec($curl);
                    curl_close($curl);

                    $this->token['status'] = true;
                    $this->token['message'] = 'Token Verified Successfully.';
                    $this->token['data'] = json_decode($curl_response);
                    return $this->token;
                }
            }else{
                $this->token['status'] = false;
                $this->token['message'] = 'Invalid Configuration.';
                return $this->token;
            }
        }else{
            $this->token['status'] = false;
            $this->token['message'] = 'Invalid Configuration.';
            return $this->token;
        }
    }
}
