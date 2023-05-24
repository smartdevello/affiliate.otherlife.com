<?php

if (!defined('ABSPATH')) {
    exit;
}

class ReferralRock_Member
{
    private static $instance = null;
    private $members = [];

    // private $programID = '9d761b47-c67b-43ee-a8b9-f048bacca756';

    private $memberAdd_hook_id = "284496c0-a952-42f6-9738-1fb1feb4e328";
    private $referralUpdate_hook_id = "a75d115b-4806-4a8f-95ed-29653fdc7793";
    private $programs = [];

    private $apibaseUrl = 'https://api.referralrock.com/api/';
    private $current_user = null;
    private $lasthttpcode = null;
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new ReferralRock_Member();
        }
        return self::$instance;
    }
    public function __construct()
    {
        if ($this->current_user == null) {
            $this->current_user = wp_get_current_user();
        }

        // add_shortcode('getmemberdetails', array($this, 'getmemberdetails_function'));
        // add_shortcode('getmemberreferralUrl1', array($this, 'getmemberreferralUrl1'));
        // add_shortcode('getmemberreferralUrl2', array($this, 'getmemberreferralUrl2'));

        // add_shortcode('getleadschart', array($this, 'getleadschart'));

        // [getmemberdetails section_name="referral_link" field1="views" field2="referrals"]
        // add_action('wp_ajax_add_referral', array($this, 'addReferral'));
        // add_action('wp_ajax_nopriv_add_referral', array($this, 'addReferral'));

        // add_action('wp_ajax_get_members', array($this, 'getMembersTable'));
        // add_action('wp_ajax_nopriv_get_members', array($this, 'getMembersTable'));

        // add_action('rest_api_init', array($this, "api_inits"));

    }
    public function api_inits()
    {
        // register_rest_route("RR/v1", "RecurringReward", array(
        //     "methods" => "POST",
        //     "callback" => array($this, "RecurringReward"),
        //     "permission_callback" => "__return_true",
        // ));
        // register_rest_route("RR/v1", "getSingleReferral", array(
        //     "methods" => "POST",
        //     "callback" => array($this, "getSingleReferral"),
        //     "permission_callback" => "__return_true",
        // ));

        // //staging_hook_id = '54bfa460-5b26-450e-ac92-c334eeded332'
        // //prod_hook_id = 'd6bc305a-7cf4-45fc-be20-63c4a12b8c36'
        // register_rest_route("RR/v1", "memberAdd", array(
        //     "methods" => "POST",
        //     "callback" => array($this, "memberAddHook"),
        //     "permission_callback" => "__return_true",
        // ));

        //staging_hook_id = '6caa422a-a580-441f-b4e2-89cd5e1b7bd9'
        //prod_hook_id = '9cad1196-01dd-45be-a958-1a76c4795113'
        // register_rest_route("RR/v1", "memberDelete", array(
        //     "methods" => "POST",
        //     "callback" => array($this, "memberDeleteHook"),
        //     "permission_callback" => "__return_true",
        // ));

        // register_rest_route("RR/v1", "referralAdd", array(
        //     "methods" => "POST",
        //     "callback" => array($this, "referralAddHook"),
        //     "permission_callback" => "__return_true",
        // ));

        // register_rest_route("RR/v1", "referralDelete", array(
        //     "methods" => "POST",
        //     "callback" => array($this, "referralDeleteHook"),
        //     "permission_callback" => "__return_true",
        // ));

        // register_rest_route("RR/v1", "referralUpdate", array(
        //     "methods" => "POST",
        //     "callback" => array($this, "referralUpdateHook"),
        //     "permission_callback" => "__return_true",
        // ));

        // register_rest_route("RR/v1", "getMonthlyReferralCount", array(
        //     "methods" => "GET",
        //     "callback" => array($this, "getMonthlyReferralCount"),
        //     "permission_callback" => "__return_true",
        // ));

    }
    public function getMonthlySales()
    {
        if ($this->current_user == null) {
            return [];
        }

        $arr_count = get_user_meta($this->current_user->ID, "salesgraphvalue", true);
        if (is_array($arr_count) && count($arr_count) >= 12) {
            $start_date = new DateTime('first day of this month');
            $end_date = new DateTime('last day of this month');

            $programID = $this->getDefaultProgramID();
            $request_uri = $this->apibaseUrl . 'rewards?programId=' . $programID . '&memberId=' . $this->getMember($programID)->id . '&offset=0' . '&dateFrom=' . $start_date->format('Y-m-d') . '&dateTo=' . $end_date->format('Y-m-d');
            $rewards = $this->getRequest($request_uri);
            $amount = 0;

            foreach ($rewards->rewards as $reward) {
                $amount = $amount + $reward->amount;
            }
            $arr_count[$start_date->format('Y-m-d')] = $amount;
            return $arr_count;
        } else {
            $ret = [];

            $start_date = new DateTime('first day of this month');
            $end_date = new DateTime('last day of this month');

            for ($i = 0; $i < 12; $i++) {
                $programID = $this->getDefaultProgramID();
                $request_uri = $this->apibaseUrl . 'rewards?programId=' . $programID . '&memberId=' . $this->getMember($programID)->id . '&offset=0' . '&dateFrom=' . $start_date->format('Y-m-d') . '&dateTo=' . $end_date->format('Y-m-d');
                $rewards = $this->getRequest($request_uri);
                $amount = 0;

                foreach ($rewards->rewards as $reward) {
                    $amount = $amount + $reward->amount;
                }
                $ret[$start_date->format('Y-m-d')] = $amount;

                $start_date->modify('-1 month');
                $end_date->modify('-1 month');
            }
            update_user_meta($this->current_user->ID, "salesgraphvalue", $ret);
            return $ret;
        }

    }
    public function getMonthlyReferralCount()
    {

        if ($this->current_user == null) {
            return [];
        }

        $arr_count = get_user_meta($this->current_user->ID, "leadsgraphvalue", true);
        if (is_array($arr_count) && count($arr_count) >= 12) {
            $start_date = new DateTime('first day of this month');
            $end_date = new DateTime('last day of this month');

            $programID = $this->getDefaultProgramID();
            $request_uri = $this->apibaseUrl . 'referrals?programId=' . $programID . '&memberId=' . $this->getMember($programID)->id . '&offset=0&count=10' . '&dateFrom=' . $start_date->format('Y-m-d') . '&dateTo=' . $end_date->format('Y-m-d');
            $res = $this->getRequest($request_uri);

            $arr_count[$start_date->format('Y-m-d')] = $res->total;

            return $arr_count;
        } else {
            $ret = [];

            $start_date = new DateTime('first day of this month');
            $end_date = new DateTime('last day of this month');

            for ($i = 0; $i < 12; $i++) {
                $programID = $this->getDefaultProgramID();
                $request_uri = $this->apibaseUrl . 'referrals?programId=' . $programID . '&memberId=' . $this->getMember($programID)->id . '&offset=0&count=10' . '&dateFrom=' . $start_date->format('Y-m-d') . '&dateTo=' . $end_date->format('Y-m-d');
                $res = $this->getRequest($request_uri);
                $ret[$start_date->format('Y-m-d')] = $res->total;
                $start_date->modify('-1 month');
                $end_date->modify('-1 month');
            }
            update_user_meta($this->current_user->ID, "leadsgraphvalue", $ret);

            return $ret;
        }

    }

    public function referralDeleteHook($request)
    {

        $email = $request['Email'];
        $programId = $request['ProgramId'];
        $memberEmail = $request['MemberEmail'];

        $programs = $this->getPrograms();
        $payload = [];
        foreach ($programs as $program) {
            if ($programId == $program->id) {
                continue;
            }

            $obj = new stdClass();
            $obj->query = new stdClass();
            $obj->query->primaryInfo = new stdClass();
            $obj->query->secondaryInfo = new stdClass();
            $obj->query->secondaryInfo->email = $email;
            $obj->query->tertiaryInfo = new stdClass();
            $obj->query->tertiaryInfo->programId = $program->id;
            $payload[] = $obj;
        }
        $request_uri = $this->apibaseUrl . 'referral/remove';
        $response = $this->postRequest($request_uri, $payload);

    }
    public function referralAddHook($request)
    {
        $email = $request['Email'];
        $programId = $request['ProgramId'];
        $memberEmail = $request['MemberEmail'];

        if (!isset($email) || empty($email)) {
            return;
        }
        $programs = $this->getPrograms();
        foreach ($programs as $program) {
            if ($programId == $program->id) {
                continue;
            }

            $request_uri = $this->apibaseUrl . 'members?programId=' . $program->id . '&query=' . $memberEmail;
            $response = $this->getRequest($request_uri);
            if ($response->total == 1) {
                $member = $response->members[0];
                $referralCode = $member->referralCode;

                $request_uri = $this->apibaseUrl . 'referrals';
                $payload = array(
                    'referralCode' => $referralCode,
                    'firstName' => $request['FirstName'],
                    'lastName' => $request['LastName'],
                    'email' => $request['Email'],
                    'status' => $request['Status'],
                );
                $response = $this->postRequest($request_uri, $payload);
            }

        }
    }

    public function memberDeleteHook($request)
    {

        $email = $request['Email'];
        $programId = $request['ProgramId'];
        if (!isset($email) || empty($email)) {
            return;
        }

        $programs = $this->getPrograms();
        $payload = [];
        foreach ($programs as $program) {
            if ($program->id == $programId) {
                continue;
            }

            $obj = new stdClass();
            $obj->query = new stdClass();
            $obj->query->primaryInfo = new stdClass();
            $obj->query->secondaryInfo = new stdClass();
            $obj->query->secondaryInfo->email = $email;
            $obj->query->tertiaryInfo = new stdClass();
            $obj->query->tertiaryInfo->programId = $program->id;
            $payload[] = $obj;
        }

        $request_uri = $this->apibaseUrl . 'members/remove';
        $response = $this->deleteRequest($request_uri, $payload);

    }
    public function memberAddHook($request)
    {

        $programId = $request['ProgramId'];
        $email = $request['Email'];

        if (!isset($email) || empty($email)) {
            return;
        }

        $programs = $this->getPrograms();
        foreach ($programs as $program) {
            if ($program->id == $programId) {
                continue;
            }

            $payload = array(
                "programId" => $program->id,
                "firstName" => $request['FirstName'],
                "lastName" => $request['LastName'],
                "email" => $request['Email'],
            );
            $url = $this->apibaseUrl . 'members';
            $response = $this->postRequest($url, $payload);

        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            //check if user exists
            if (email_exists($email)) {
                return;
            }

            $userdata = array(
                'user_pass' => 'Password#1',
                'user_login' => $request['referralCode'],
                'user_nicename' => $request['firstName'] . ' ' . $request['lastName'],
                'user_email' => $email,
                'display_name' => $request['displayName'],
                'first_name' => $request['firstName'],
                'last_name' => $request['lastName'],
                'role' => get_option('default_role'),
            );

            $user_id = wp_insert_user($userdata);

            // On success.
            if (!is_wp_error($user_id)) {
                echo "User created : " . $email;
            } else {
                echo "error while trying to create a user with " . $email;
            }
        }
    }
    public function getDefaultProgramID()
    {
        $programs = $this->getPrograms();
        $programID = $programs[0]->id;
        return $programID;
    }
    public function getnumberofactiveleads()
    {
        $programID = $this->getDefaultProgramID();
        $member = $this->getMember($programID);
        echo $member->referrals;
    }

    public function getrewardsearned()
    {
        $programs = $this->getPrograms();
        $ret = 0;
        foreach ($programs as $program) {
            $programID = $program->id;
            $member = $this->getMember($programID);
            $ret = $ret + $member->rewardAmountTotal;
        }
        echo "$" . $ret;

    }
    public function getnumberofactivesales()
    {
        $programs = $this->getPrograms();
        $ret = 0;
        foreach ($programs as $program) {
            $programID = $program->id;
            $member = $this->getMember($programID);
            $ret = $ret + $member->rewards;
        }
        echo $ret;
    }
    public function getSingleReferral($request)
    {
        $email = $request['email'];
        $programID = $request['programID'];

        $request_uri = $this->apibaseUrl . 'referrals?programId=' . $programID . '&query=' . $email;

        $response = $this->getRequest($request_uri);
        if ($response->total == 1) {
            $res = new WP_REST_Response($response->referrals[0]);
            $res->set_status(200);
        } else {
            $res = new WP_REST_Response("not found");
            $res->set_status(200);
        }

        return $res;
    }
    public function RecurringReward($request)
    {
        $programID = $request['programID'];
        $payload = array(
            "amount" => $request['amount'],
            "referralQuery" => $request['email'],
            "programQuery" => $programID,
        );
        $url = $this->apibaseUrl . 'referralaction';
        $response = $this->postRequest($url, $payload);

        $res = new WP_REST_Response($response);
        $res->set_status(200);
        return ['success' => $res];
    }
    private function getRequest($url)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic YWJkZGE1YWEtNTVhYy00MWMxLTk1YTYtZWI1ODhkMmUzNWUzOjEzMGYyMmFkLTk5OGItNDkzOS1hNTA0LWQ2NDlkYTZmNTQ1Nw==',
            ),
        ));

        $response = curl_exec($curl);
        $this->lasthttpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return json_decode($response);

    }
    private function postRequest($url, $data)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic YWJkZGE1YWEtNTVhYy00MWMxLTk1YTYtZWI1ODhkMmUzNWUzOjEzMGYyMmFkLTk5OGItNDkzOS1hNTA0LWQ2NDlkYTZmNTQ1Nw==',
            ),
        ));

        $response = curl_exec($curl);
        $this->lasthttpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return json_decode($response);
    }

    private function deleteRequest($url, $data)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic YWJkZGE1YWEtNTVhYy00MWMxLTk1YTYtZWI1ODhkMmUzNWUzOjEzMGYyMmFkLTk5OGItNDkzOS1hNTA0LWQ2NDlkYTZmNTQ1Nw==',
            ),
        ));

        $response = curl_exec($curl);
        $this->lasthttpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return json_decode($response);
    }
    public function getmemberreferralUrl1()
    {
        $programs = $this->getPrograms();
        return $this->getMember($programs[0]->id)->referralUrl;
    }
    public function getmemberreferralUrl2()
    {
        $programs = $this->getPrograms();
        return $this->getMember($programs[1]->id)->referralUrl;
    }
    public function addReferral($programID)
    {
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post['ajaxrequest']) {
            $full_name = trim($post['form_fields']['name']);

            // Get First name and last name from full name
            $parts = explode(" ", $full_name);
            if (count($parts) > 1) {
                $lastname = array_pop($parts);
                $firstname = implode(" ", $parts);
            } else {
                $firstname = $full_name;
                $lastname = " ";
            }

            $email = $post['form_fields']['email'];
            $phone = $post['form_fields']['phone'];
            $member_note = $post['form_fields']['member_note'];
            $url = $this->apibaseUrl . 'referrals';
            $payload = array(
                "referralCode" => $this->getMember($programID)->referralCode,
                "firstName" => $firstname,
                "lastName" => $lastname,
                "email" => $email,
                "phoneNumber" => $phone,
            );

            $response = $this->postRequest($url, $payload);
            if (isset($response->message)) {
                wp_send_json($response->message);
            }
            wp_send_json_error("something went wrong");
            // echo json_encode($response);
        }
        wp_die();
    }
    public function getPrograms()
    {
        if (count($this->programs) == 0) {
            $request_uri = $this->apibaseUrl . '/programs';
            $data = $this->getRequest($request_uri);
            if (is_array($data->programs)) {
                $this->programs = $data->programs;
                return $this->programs;
            }
            return [];
        }
        return $this->programs;
    }
    public function getMembers($programID)
    {
        $request_uri = $this->apibaseUrl . '/members?programId=' . $programID;
        $data = $this->getRequest($request_uri);
        $members = [];
        if (is_array($data->members)) {
            $members = $data->members;
            return $members;
        }
        return null;
    }
    public function getMember($programID, $updateFlag = false)
    {
        if (!isset($this->members[$programID]) || $updateFlag) {
            $request_uri = $this->apibaseUrl . 'members?programId=' . $programID . '&query=' . $this->current_user->user_email;
            $data = $this->getRequest($request_uri);

            if (is_array($data->members) && count($data->members) == 1) {
                $this->members[$programID] = $data->members[0];
            }
        }
        return $this->members[$programID];
    }
    public function getMembersTable()
    {
        // $programID = $this->getDefaultProgramID();
        // $draw = $_POST['draw'];
        // $offset = $_POST['start'];
        // $count = $_POST['length'];
        // $order = $_POST['order'][0]['column'] ?? "";
        // $direction = $_POST['order'][0]['dir'] ?? "";
        // $search_value = $_POST['search']['value'] ?? "";

        // $request_uri = $this->apibaseUrl . 'members?programId=' . $programID . '&offset=' . $offset . '&count=' . $count;
        // if (!empty($search_value)) {
        //     $request_uri .= '&query=' . $search_value;
        // }

        // $data = $this->getRequest($request_uri);
        // $return = array();
        // $return['draw'] = $draw;
        // $return['recordsTotal'] = $data->total;
        // $return['recordsFiltered'] = empty($search_value) ? $data->total : count($data->members);
        // $return['data'] = array();

        // foreach ($data->members as $row) {
        //     $row_obj = new stdClass();
        //     $row_obj->displayName = $row->displayName;
        //     $row_obj->programName = $row->programName;
        //     $row_obj->referralCode = $row->referralCode;
        //     $row_obj->referrals = array($row->referralsQualified, $row->referralsApproved);
        //     $row_obj->create_date = $row->createDt;
        //     $row_obj->rewards = array($row->rewardsPendingAmount, $row->rewardsIssuedAmount);
        //     $return['data'][] = $row_obj;
        // }
        // echo json_encode($return);
        wp_die();

    }
    public function getRewardsTable($programID)
    {
        $draw = $_POST['draw'];
        $offset = $_POST['start'];
        $count = $_POST['length'];
        $order = $_POST['order'][0]['column'] ?? "";
        $direction = $_POST['order'][0]['dir'] ?? "";
        $search_value = $_POST['search']['value'] ?? "";

        $request_uri = $this->apibaseUrl . 'rewards?programId=' . $programID . '&offset=' . $offset . '&count=' . $count;
        if (!empty($search_value)) {
            $request_uri .= '&query=' . $search_value;
        }
        if (!current_user_can('administrator')) {
            $request_uri = $request_uri . '&memberId=' . $this->getMember($programID)->id;
        }

        $data = $this->getRequest($request_uri);
        $return = array();
        $return['draw'] = $draw;
        $return['recordsTotal'] = $data->total;
        $return['recordsFiltered'] = empty($search_value) ? $data->total : count($data->rewards);
        $return['data'] = array();

        foreach ($data->rewards as $row) {
            $row_obj = new stdClass();
            $row_obj->reward = $row->payoutDescription;
            $row_obj->status = $row->status;
            $row_obj->reward_amount = $row->amount;
            $row_obj->issue_information = $row->description;
            $row_obj->create_date = $row->createDate;
            $return['data'][] = $row_obj;
        }
        echo json_encode($return);
        wp_die();

    }
    public function getReferralsTable()
    {
        $programs = $this->getPrograms();
        $programID = $programs[0]->id;
        $draw = $_POST['draw'];
        $offset = $_POST['start'];
        $count = $_POST['length'];
        $order = $_POST['order'][0]['column'] ?? "";
        $direction = $_POST['order'][0]['dir'] ?? "";
        $search_value = $_POST['search']['value'] ?? "";

        $request_uri = $this->apibaseUrl . 'referrals?programId=' . $programID . '&memberId=' . $this->getMember($programID)->id . '&offset=' . $offset . '&count=' . $count;
        if (!empty($search_value)) {
            $request_uri .= '&query=' . $search_value;
        }
        $data1 = $this->getRequest($request_uri);

        $programID = $programs[1]->id;
        $request_uri = $this->apibaseUrl . 'referrals?programId=' . $programID . '&memberId=' . $this->getMember($programID)->id . '&offset=' . $offset . '&count=' . $count;
        if (!empty($search_value)) {
            $request_uri .= '&query=' . $search_value;
        }
        $data2 = $this->getRequest($request_uri);

        $return = array();
        $return['draw'] = $draw;
        $return['recordsTotal'] = $data1->total;
        $return['recordsFiltered'] = empty($search_value) ? $data1->total : count($data1->referrals);
        $return['data'] = array();

        for ($i = 0; $i < count($data1->referrals); $i++) {
            $row_obj = new stdClass();
            $row_obj->referral = $data1->referrals[$i]->fullName;
            $row_obj->status = $data1->referrals[$i]->status;
            $row_obj->deal_amount = $data1->referrals[$i]->amount + $data2->referrals[$i]->amount;
            $row_obj->public_note = $data1->referrals[$i]->publicNote;
            $row_obj->last_update = $data1->referrals[$i]->updateDate;
            $return['data'][] = $row_obj;

        }
        echo json_encode($return);
        wp_die();
    }
    public function getmemberdetails_function($args)
    {
        $section_name = $args['section_name'];
        $field1 = $args['field1'];
        $field2 = $args['field2'];
        $programs = $this->getPrograms();
        $default_programID = $programs[0]->id;

        if ($section_name == "referral_link") {

            ?>
<div>
    <span style="font-size: 18px;">Referral Link Clicks</span><br>
    <strong style="font-size: 20px;"><?php echo $this->getMember($default_programID)->{$field1}; ?></strong><br>
    <span class="my-dashboard-card-detail-value"><?php echo $this->getMember($default_programID)->{$field2}; ?>
    </span>Unique Visitors
</div>
<?php
} else if ($section_name == "approved") {
            ?>
<div>
    <span style="font-size: 18px;">Approved</span><br>
    <strong style="font-size: 20px;"><?php echo $this->getMember($default_programID)->{$field1}; ?></strong><br>
    <span class="my-dashboard-card-detail-value"><?php echo $this->getMember($default_programID)->{$field2}; ?>
    </span>Referrals
</div>
<?php
} else if ($section_name == "issued") {
            ?>
<div>
    <span style="font-size: 18px;">Issued</span><br>
    <strong style="font-size: 20px;"><?php echo $this->getMember($default_programID)->{$field1}; ?></strong><br>
    <span class="my-dashboard-card-detail-value"><?php echo $this->getMember($default_programID)->{$field2}; ?>
    </span>Pending
</div>
<?php
}

    }

}
ReferralRock_Member::getInstance();