<?php


namespace flexiPIM\Hawthorne\API;


use Illuminate\Support\Facades\Schema;
use DB;

class HawthorneAuthentication
{
    public $token = [];

    public $hawthorneConfig;

    public function __construct()
    {
        $this->isAuthorize();
    }

    /**
     * Function To Authorize the Whether the Credentials are right or not
     *
     * @return array
     * @author KMG
     */
    public function isAuthorize()
    {
        if (Schema::hasTable('hawthorne_configuration')) {
            $this->hawthorneConfig = DB::table('hawthorne_configuration')->first();

            if(isset($this->hawthorneConfig)){
                $url = $this->getSignature($this->hawthorneConfig->access_url.'?X-ApiKey='.$this->hawthorneConfig->client_key,$this->hawthorneConfig->secret_key);
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POST, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $curl_response = curl_exec($curl);
                curl_close($curl);

                if($curl_response == 1){
                    $this->token['status'] = true;
                    $this->token['message'] = 'Token Verified Successfully.';
                }else{
                    $this->token['status'] = false;
                    $this->token['message'] = 'Invalid Credentials.';
                }
            }
        }else{
            $this->token['status'] = false;
            $this->token['message'] = 'Invalid Configuration.';
            return $this->token;
        }
    }

    /**
     * Function To Generate the Signature
     *
     * @param $url
     * @param $secret
     * @return string
     * @author KMG
     */
    public function getSignature($url,$secret)
    {
        // Get time stamp in correct format "2013-01-31T17:16:15Z"
        $time = gmdate("Y-m-d\TH:i:s\Z");
        // Add time stamp to url
        $urlWithTime = $url . "&time=" . $time;
        // Generate signature
        $signature = strtoupper(hash_hmac("sha256", $urlWithTime, $secret));

        // Return Signed Url
        return $urlWithTime . "&signature=" . $signature;
    }
}
