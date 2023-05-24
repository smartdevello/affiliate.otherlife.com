<?php

if (! defined('ABSPATH')) {
    exit;
}

class GHL
{
    private static $instance = null;
    private $FP_API_URL = 'https://firstpromoter.com/api/v1/';
    private $FP_API_Key = '5c4ebdf7622bea1aa2c2f8be467d0c01';

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new GHL();
        }
        return self::$instance;
    }
    public function __construct()
    {
        add_action('rest_api_init', array($this, "api_inits"));
    }


    public function api_inits()
    {
        register_rest_route("GHL/", "getLeadSubmission", array(
            "methods" => "POST",
            "callback" => array($this, "getLeadSubmission"),
            "permission_callback" => "__return_true",
        ));
        register_rest_route("GHL/", "getSaleSubmission", array(
            "methods" => "POST",
            "callback" => array($this, "getSaleSubmission"),
            "permission_callback" => "__return_true",
        ));

        register_rest_route("GHL/", "getCustomerSupportSubmission", array(
            "methods" => "POST",
            "callback" => array($this, "getCustomerSupportSubmission"),
            "permission_callback" => "__return_true",
        ));
    }

    public function getAllEntities($entity)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.helpdesk.com/v1/' . $entity,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic OTUzODM3YjYtYzMyOS00ODg0LTg2NjQtODFmOGNhYTk1OGY5OmRhbDp2Q0FvdmY3eFROZ0xGOFQ2SEp1Nk5JR1lob0E='
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }
    public function getCustomerSupportSubmission(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();

        // error_log('===========================');
        // error_log(print_r($payload, true));
        // error_log('===========================');

        $support_category = $payload["Support Category"];

        $otherlife_issue = $payload["Otherlife Issue"];
        $phantom_issue = $payload["Phantom Issue"];
        $affiliate_issue = $payload["Affiliate Issue"];

        $subaccountName = $payload["Sub-Account Name"];
        $your_affiliate_id = $payload["Your Affiliate ID"];

        $support_description = $payload["Support Description"];
        $loom_url = $payload["Loom URL"];


        $request_payload = array();
        $teamIds = [
            "Phantom" => "a45b945b-1114-492c-aecc-f6363d6f57a6",
            "Affiliate" => "6eb7c50e-fcbb-4ec9-8274-9b04c7e9fe3b",
            "Otherlife" => "015479f5-3bc1-4eaa-8da0-57ff4c8d5c79"
        ];
        $all_agents = array(
            "Jessica" => "086485fa-2fbb-45b2-a4b7-7ce01de7d320",
            "Joel" => "27f94e93-9069-416c-9c12-a79f67160d3b",
            "Erika" => "3d5a4abb-fd69-41b8-a6de-5b50666f5c71",
            "Juan" => "54bf0a46-d221-4957-9e59-dc55d9a39f01",
            "Abraham" => "953837b6-c329-4884-8664-81f8caa958f9",
            "Nicole" => "f00028ec-27c9-468d-9506-92ff06b80a98"
        );
        $all_tags = array(
            "Commission/Sales Data" => "0c183e7a-e73f-4b88-8c50-7664a2957f7f",
            "Billing Question" => "21e125de-1685-41d7-ae4a-742897ac9dc0",
            "Other" => "21e125de-1685-41d7-ae4a-742897ac9dc0",
            "Login" => "5606ef57-dfde-4785-b4e0-fecb79e3ebb2",
            "Account Access" => "a1e9d85d-8a1b-4504-bb87-0c8f0428f69b",
            "Billing" => "c7013346-64c8-41d8-9598-b94dd602056b",
            "Sales Notifications" => "cb973625-0c0d-4a71-8d46-1784e9fad7bb",
            "Affiliate Application" => "f04b8100-abd5-495a-b374-711584d5d733",
            "Affiliate Funnel" => "f2aa8e8f-fdab-4047-b38c-3bbfbe74e476"
        );

        $request_payload["assignment"] = array();
        $request_payload["assignment"]["team"] = array("ID" => $teamIds[$support_category]);
        $request_payload["author"]["type"] = "client";


        if ($support_category == "Phantom") {
            $request_payload['subject'] = $support_category . " " . $phantom_issue;
            $request_payload["tagIDs"] = [$all_tags[$phantom_issue]];
            $agentName = "Joel";
        } else if ($support_category == "Otherlife") {
            $request_payload['subject'] = $support_category . " " . $otherlife_issue;
            $request_payload["tagIDs"] = [$all_tags[$otherlife_issue]];
            $agentName = "Erika";
        } else if ($support_category == "Affiliate") {
            $request_payload['subject'] = $support_category . " " . $affiliate_issue;
            $request_payload["tagIDs"] = [$all_tags[$affiliate_issue]];
            $agentName = "Juan";

        }
        $request_payload["assignment"]["agent"] = array("ID" => $all_agents[$agentName]);

        $request_payload['requester'] = array(
            "email" => $payload["email"],
            "name" => $payload["full_name"],
        );
        $request_payload['message'] = array(
            "text" => $support_description
        );

        $request_payload["customFields"] = new stdClass();




        if (! empty($loom_url))
            $request_payload["customFields"]->descriptionOfIssue = $loom_url;
        if (! empty($subaccountName))
            $request_payload["customFields"]->subaccountIdName = $subaccountName;
        if (! empty($your_affiliate_id))
            $request_payload["customFields"]->affiliateId = $your_affiliate_id;

        $request_payload["teamIDs"] = array(
            "a45b945b-1114-492c-aecc-f6363d6f57a6",
            "6eb7c50e-fcbb-4ec9-8274-9b04c7e9fe3b",
            "015479f5-3bc1-4eaa-8da0-57ff4c8d5c79"
        );


        $curl = curl_init();



        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.helpdesk.com/v1/tickets',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($request_payload),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic OTUzODM3YjYtYzMyOS00ODg0LTg2NjQtODFmOGNhYTk1OGY5OmRhbDp2Q0FvdmY3eFROZ0xGOFQ2SEp1Nk5JR1lob0E='
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        // curl_close($curl);
        // error_log('===========================');
        // error_log(json_encode($request_payload));
        // error_log('===========================');

    }
    public function getLeadSubmission(WP_REST_Request $request)
    {

        $payload = $request->get_json_params();
        // error_log('======leads submission=======');
        // error_log(print_r($payload, true));
        // error_log('===========================');

        $email = strtolower($payload['customData']['Affiliate Email']);
        $ref_id = strtolower($payload['customData']['Affiliate ID']);

        $status_code = $this->createLead($email, $ref_id);

        if ($status_code == 422) {
            $lead = $this->getCurrentLead($email);
            if ($lead->state == 'signup')
                $this->modifyExistingLead($email, $ref_id);
        }
        return new WP_REST_Response(['message' => 'created a lead for ' . $email . " " . $ref_id], 200);
    }
    public function getCurrentLead($email)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->FP_API_URL . 'leads/show?email=' . $email,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'x-api-key: ' . $this->FP_API_Key
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }
    public function getSaleSubmission(WP_REST_Request $request)
    {

        $payload = $request->get_json_params();
        // error_log('======sales submission=======');
        // error_log(print_r($payload, true));
        // error_log('===========================');
        try {
            $ref_id = strtolower($payload['Final Affiliate']);
            $email = strtolower($payload['email']);
            $amount = $payload['customData']['Price'];
            $amount = str_replace('$', '', $amount);
            $amount = $amount * 100;
            $status_code = $this->createLead($email, $ref_id);
            if ($status_code == 422) {
                $lead = $this->getCurrentLead($email);
                if ($lead->state == 'signup')
                    $this->modifyExistingLead($email, $ref_id);
            }
            $this->createSale($email, $amount);
            return new WP_REST_Response(['message' => 'created the sale in FP  ' . $email . ' ' . $ref_id . ' ' . $amount . ' ' . $status_code], 200);
        } catch (Exception $e) {
            return new WP_REST_Response(['message' => 'custom request'], 200);
        }

    }
    function createSale($email, $amount)
    {
        // Tracking sales and commissions

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->FP_API_URL . 'track/sale',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query([
                'email' => $email,
                // 'ref_id' => $ref_id,
                'amount' => $amount
            ]),
            CURLOPT_HTTPHEADER => array(
                'x-api-key: ' . $this->FP_API_Key,
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    function createLead($email, $ref_id)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->FP_API_URL . 'track/signup',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query([
                'email' => $email,
                'ref_id' => $ref_id
            ]),
            CURLOPT_HTTPHEADER => array(
                'x-api-key: ' . $this->FP_API_Key,
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);
        return $status_code;
    }
    function modifyExistingLead($email, $new_ref_id)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->FP_API_URL . 'leads/update',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => http_build_query([
                'email' => $email,
                'new_ref_id' => $new_ref_id
            ]),

            CURLOPT_HTTPHEADER => array(
                'x-api-key: ' . $this->FP_API_Key,
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;

    }


}
GHL::getInstance();