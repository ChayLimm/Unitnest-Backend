<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;

class FormService{

    // get prefill link of registration form
    public function getPrefillLink($landlordId, $chatId){

        $formId = env("GOOGLE_REGISTRATION_FORM_ID");

        if(empty($formId)){
            Log::error("GOOGLE_REGISTRATION_FORM_ID is not set in .env");
            throw new \Exception("Form ID is not config");
        }

        $params =[
            'entry.' . env('FORM_ENTRY_LANDLORD_ID') => $landlordId,
            'entry.' . env('FORM_ENTRY_CHAT_ID') => $chatId,
        ];
        // map params to query string 
        $queryString = http_build_query($params);

        $url = "https://docs.google.com/forms/d/e/{$formId}/viewform?usp=pp_url&{$queryString}";

        return $url;
    }

}