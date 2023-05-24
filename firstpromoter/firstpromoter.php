<?php

if (! defined('ABSPATH')) {
    exit;
}

class FirstPromoter
{
    private static $instance = null;
    private $api_base_url = 'https://firstpromoter.com/api/';
    private $apiKey = '5c4ebdf7622bea1aa2c2f8be467d0c01';

    private static $promoter = null;
    private static $default_ref_id = "";
    private static $promoter_id = "";
    private $auth_token = "";
    private $leads = [];
    private $leads_properties = [];
    private $promoterFlag = false;
    private $defaultRefIdFlag = false;
    private $promoterIdFlag = false;

    private $monthlyReport = [];
    private $dailyReport = [];
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new FirstPromoter();
        }
        return self::$instance;
    }
    public function __construct()
    {

        $this->getCurrentPromoter();
        $this->getDefaultRefId();
        add_action('rest_api_init', array($this, "api_inits"));
        //shortcode order
        add_shortcode('getnumberofvisitorscount', array($this, 'getnumberofvisitorscount'));
        add_shortcode('getnumberofactiveleads', array($this, 'getnumberofactiveleads'));
        add_shortcode('getnumberofactivesales', array($this, 'getnumberofactivesales'));
        //2nd row
        add_shortcode('getupcomingcommissions', array($this, 'getupcomingcommissions'));
        add_shortcode('getpaidcommissions', array($this, 'getpaidcommissions'));
        add_shortcode('gettotalcommissions', array($this, 'gettotalcommissions'));
        add_shortcode('getperformancedetails', array($this, 'getperformancedetails'));

        add_shortcode('getmemberreferralUrl1', array($this, 'getmemberreferralUrl1'));
        add_shortcode('getmemberreferralUrl2', array($this, 'getmemberreferralUrl2'));
        add_shortcode('getmemberreferralUrl3', array($this, 'getmemberreferralUrl3'));
        add_shortcode('getmemberreferralUrl4', array($this, 'getmemberreferralUrl4'));

        add_action('wp_ajax_get_leads', array($this, 'getLeadsTable'));
        add_action('wp_ajax_nopriv_get_leads', array($this, 'getLeadsTable'));

        add_action('wp_ajax_get_daily_performance', array($this, 'get_daily_performanceTable'));
        add_action('wp_ajax_nopriv_get_daily_performance', array($this, 'get_daily_performanceTable'));
        add_action('wp_ajax_get_weekly_performance', array($this, 'get_weekly_performanceTable'));
        add_action('wp_ajax_nopriv_get_weekly_performance', array($this, 'get_weekly_performanceTable'));
        add_action('wp_ajax_get_monthly_performance', array($this, 'get_monthly_performanceTable'));
        add_action('wp_ajax_nopriv_get_monthly_performance', array($this, 'get_monthly_performanceTable'));

        add_action('wp_ajax_get_rewards', array($this, 'getRewardsTable'));
        add_action('wp_ajax_nopriv_get_rewards', array($this, 'getRewardsTable'));

        add_action('wp_ajax_monthly_reports_action', array($this, 'getMonthlyReportGraph'));
        add_action('wp_ajax_nopriv_monthly_reports_action', array($this, 'getMonthlyReportGraph'));

    }
    public function getMemberReferralUrl()
    {
        $promoter = $this->getCurrentPromoter();
        $promotions = $promoter->promotions;
        foreach ($promotions as $promotion) {
            if (strpos($promotion->referral_link, "7dayshift") !== false) {
                return $promotion->referral_link;
            }

        }
        return "";
    }
    public function getmemberreferralUrl1()
    {
        $default_url = $this->getMemberReferralUrl();
        $domain_url = explode("?", $default_url)[0];
        $referral_code = explode("?", $default_url)[1];
        return $domain_url . "/show-me?" . $referral_code;
    }
    public function getmemberreferralUrl2()
    {
        $default_url = $this->getMemberReferralUrl();
        $domain_url = explode("?", $default_url)[0];
        $referral_code = explode("?", $default_url)[1];
        return $domain_url . "/light?" . $referral_code;
    }
    public function getmemberreferralUrl3()
    {
        $default_url = $this->getMemberReferralUrl();
        $domain_url = explode("?", $default_url)[0];
        $referral_code = explode("?", $default_url)[1];
        return $domain_url . "/dark?" . $referral_code;
    }
    public function getmemberreferralUrl4()
    {
        $default_url = $this->getMemberReferralUrl();
        $domain_url = explode("?", $default_url)[0];
        $referral_code = explode("?", $default_url)[1];
        return "https://gophantom.io?" . $referral_code;
    }

    public function api_inits()
    {
        register_rest_route("FP/v1", "PromoterAdd", array(
            "methods" => "POST",
            "callback" => array($this, "PromoterAddHook"),
            "permission_callback" => "__return_true",
        ));

    }
    public function getperformancedetails()
    {
        ?>
        <iframe height="850px" width="100%" frameborder="0"
            src="https://otherlife.firstpromoter.com/view_dashboard_as?at=<?php echo $this->getPromoterAuthToken(); ?>"></iframe>
        <?php
    }
    public function PromoterAddHook(WP_REST_Request $request)
    {


        $promoter = $request['data']['promoter'];
        $email = $promoter['email'];

        if (! isset($email) || empty($email)) {
            return;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            //check if user exists
            if (email_exists($email)) {
                return;
            }

            $userdata = array(
                'user_pass' => 'Password#1',
                'user_login' => $email,
                'user_nicename' => $promoter['profile']['first_name'] . ' ' . $promoter['profile']['last_name'],
                'user_email' => $email,
                'first_name' => $promoter['profile']['first_name'],
                'last_name' => $promoter['profile']['last_name'],
                'role' => get_option('default_role'),
            );

            $user_id = wp_insert_user($userdata);
            // On success.
            if (! is_wp_error($user_id)) {
                update_user_meta($user_id, 'auth_token', $promoter['auth_token']);
                update_user_meta($user_id, 'promoter_id', $promoter['id']);

            } else {

            }
        }

    }

    public function getDailyLeads()
    {
        if (! is_user_logged_in()) {
            return;
        }

        //If we know nothing about leads, then make the first api call, grab some info then save them in leads_properties variable
        if (! isset($this->leads_properties['pages'])) {
            $this->leads = [];
            $uri = $this->api_base_url . 'v1/leads/list?ref_id=' . $this->getDefaultRefId();

            $response = $this->getRequestwithHeader($uri);
            $per_page = intval(trim($response['header']['Per-Page']));
            $total = intval(trim($response['header']['Total']));

            $this->leads[1] = $response['body'];
            $this->leads_properties['per_page'] = $per_page;
            $this->leads_properties['total'] = $total;
            $this->leads_properties['pages'] = intval($total / $per_page);

            if (($total % $per_page) !== 0) {
                $this->leads_properties['pages']++;
            }

        }

        $ret = get_user_meta(wp_get_current_user()->ID, "daily_leads", true);

        if (! is_array($ret)) {
            $ret = [];
        }

        $startDay = new DateTime("now", new DateTimeZone("UTC"));
        $startDay->setTime(0, 0, 0);

        $today = clone $startDay;
        //Calculate max 7 days
        for ($i = 0; $i < 7; $i++) {
            if (isset($ret[$startDay->format('Y-m-d')]) && $today->format('Y-m-d') !== $startDay->format('Y-m-d')) {
                $startDay->modify('-1 day');
                continue;
            }

            $endDay = clone $startDay;
            $endDay->setTime(23, 59, 59);

            $countPosition = $this->getMonthlyLeadsCountByTime($startDay, $endDay);

            if ($countPosition == 0) {
                $count = 0;
            } else {
                $count = $countPosition['startPosition']["page"] * 50 + $countPosition['startPosition']["index"] - $countPosition['endPosition']["page"] * 50 - $countPosition['endPosition']["index"] + 1;
            }

            $ret[$startDay->format('Y-m-d')] = $count;
            update_user_meta(wp_get_current_user()->ID, "daily_leads", $ret);

            $startDay->modify('-1 day');
        }

    }
    public function getWeeklyLeads()
    {
        if (! is_user_logged_in()) {
            return;
        }

        //If we know nothing about leads, then make the first api call, grab some info then save them in leads_properties variable
        if (! isset($this->leads_properties['pages'])) {
            $this->leads = [];
            $uri = $this->api_base_url . 'v1/leads/list?ref_id=' . $this->getDefaultRefId();

            $response = $this->getRequestwithHeader($uri);
            $per_page = intval(trim($response['header']['Per-Page']));
            $total = intval(trim($response['header']['Total']));

            $this->leads[1] = $response['body'];
            $this->leads_properties['per_page'] = $per_page;
            $this->leads_properties['total'] = $total;
            $this->leads_properties['pages'] = intval($total / $per_page);

            if (($total % $per_page) !== 0) {
                $this->leads_properties['pages']++;
            }

        }

        $ret = get_user_meta(wp_get_current_user()->ID, "weekly_leads", true);

        if (! is_array($ret)) {
            $ret = [];
        }

        $startWeek = new DateTime('this week', new DateTimeZone('UTC'));
        $startWeek->setTime(0, 0, 0);

        $thisWeek = clone $startWeek;
        //Calculate max 4 weeks
        for ($i = 0; $i < 4; $i++) {
            if (isset($ret[$startWeek->format('Y-m-d')]) && $startWeek->format('Y-m-d') !== $thisWeek->format('Y-m-d')) {
                $startWeek->modify('-1 week');
                continue;
            }

            $endWeek = clone $startWeek;
            $endWeek->modify('+6 days');
            $endWeek->setTime(23, 59, 59);

            $countPosition = $this->getMonthlyLeadsCountByTime($startWeek, $endWeek);

            if ($countPosition == 0) {
                $count = 0;
            } else {
                $count = $countPosition['startPosition']["page"] * 50 + $countPosition['startPosition']["index"] - $countPosition['endPosition']["page"] * 50 - $countPosition['endPosition']["index"] + 1;
            }

            $ret[$startWeek->format('Y-m-d')] = $count;
            update_user_meta(wp_get_current_user()->ID, "weekly_leads", $ret);

            $startWeek->modify('-7 days');
        }

    }

    public function getMonthlyReportGraph()
    {
        if (! is_user_logged_in()) {
            echo json_encode([]);
            wp_die();
        }
        if (! isset($this->monthlyReport) || empty($this->monthlyReport)) {
            $this->monthlyReport = $this->getPromoterReport('month');
        }

        echo json_encode($this->monthlyReport);
        wp_die();

    }
    public function getPromoterReport($group_by)
    {

        $uri = $this->api_base_url . 'v1/reports/promoters?selected_fields%5B%5D=revenue_amount&selected_fields%5B%5D=clicks_count&selected_fields%5B%5D=referrals_count&selected_fields%5B%5D=customers_count&selected_fields%5B%5D=active_customers&selected_fields%5B%5D=monthly_churn&selected_fields%5B%5D=net_revenue_amount&selected_fields%5B%5D=sales_count&selected_fields%5B%5D=refunds_count&selected_fields%5B%5D=monthly_churn&selected_fields%5B%5D=cancelled_customers_count&selected_fields%5B%5D=promoter_earnings_amount&selected_fields%5B%5D=non_link_customers&selected_fields%5B%5D=referrals_to_customers_cr&selected_fields%5B%5D=3m_epc&selected_fields%5B%5D=6m_epc&selected_fields%5B%5D=clicks_to_customers_cr&selected_fields%5B%5D=clicks_to_referrals_cr&selected_fields%5B%5D=promoter_paid_amount&group_by=' . $group_by . '&page=1';
        $response = $this->getRequestwithHeader($uri);
        $per_page = intval(trim($response['header']['Per-Page']));
        $total = intval(trim($response['header']['Total']));

        $pages = ($per_page != 0) ? intval($total / $per_page) : 0;

        if ($per_page !== 0 && ($total % $per_page) !== 0) {
            $pages++;
        }

        $promoter_id = $this->getPromoterId();

        $page = 1;
        $found = false;
        $promoterReport = [];
        while ($page <= $pages) {
            foreach ($response['body'] as $promoter) {
                if ($promoter->promoter_id == $promoter_id) {
                    $found = true;
                    $promoterReport = $promoter;
                    break;
                }

            }
            if ($found) {
                break;
            }
            $page++;
            if ($page == $pages) {
                break;
            }

            $uri = $this->api_base_url . 'v1/reports/promoters?selected_fields%5B%5D=revenue_amount&selected_fields%5B%5D=clicks_count&selected_fields%5B%5D=referrals_count&selected_fields%5B%5D=customers_count&selected_fields%5B%5D=active_customers&selected_fields%5B%5D=monthly_churn&selected_fields%5B%5D=net_revenue_amount&selected_fields%5B%5D=sales_count&selected_fields%5B%5D=refunds_count&selected_fields%5B%5D=monthly_churn&selected_fields%5B%5D=cancelled_customers_count&selected_fields%5B%5D=promoter_earnings_amount&selected_fields%5B%5D=non_link_customers&selected_fields%5B%5D=referrals_to_customers_cr&selected_fields%5B%5D=3m_epc&selected_fields%5B%5D=6m_epc&selected_fields%5B%5D=clicks_to_customers_cr&selected_fields%5B%5D=clicks_to_referrals_cr&selected_fields%5B%5D=promoter_paid_amount&group_by=' . $group_by . '&page=' . $page;
            $response = $this->getRequestwithHeader($uri);

        }
        return $promoterReport;
    }

    public function get_daily_performanceTable()
    {
        if (! is_user_logged_in()) {
            echo json_encode([]);
            wp_die();
        }

        $draw = $_POST['draw'];
        $offset = $_POST['start'];
        $count = $_POST['length'];
        $order = $_POST['order'][0]['column'] ?? "";
        $direction = $_POST['order'][0]['dir'] ?? "";
        $search_value = $_POST['search']['value'] ?? "";

        $return = array();
        $return['draw'] = $draw;
        $return['recordsTotal'] = 7;
        $return['recordsFiltered'] = 7;
        $return['data'] = array();

        if (! isset($this->dailyReport) || empty($this->dailyReport)) {
            $this->dailyReport = $this->getPromoterReport('day');

        }

        for ($i = 0; $i < 7; $i++) {
            if (! isset($this->dailyReport->items[$i])) {
                break;
            }

            $row_obj = new stdClass();
            $row_obj->day = $this->dailyReport->items[$i]->period;
            $row_obj->customers = $this->dailyReport->items[$i]->customers_count;
            $row_obj->earning = substr($this->dailyReport->items[$i]->promoter_earnings_amount, 1);
            $return['data'][] = $row_obj;

        }

        echo json_encode($return);
        wp_die();

    }
    public function get_monthly_performanceTable()
    {
        $draw = $_POST['draw'];
        $offset = $_POST['start'];
        $count = $_POST['length'];
        $order = $_POST['order'][0]['column'] ?? "";
        $direction = $_POST['order'][0]['dir'] ?? "";
        $search_value = $_POST['search']['value'] ?? "";

        $return = array();
        $return['draw'] = $draw;
        $return['recordsTotal'] = 6;
        $return['recordsFiltered'] = 6;
        $return['data'] = array();

        if (! isset($this->monthlyReport) || empty($this->monthlyReport)) {
            $this->monthlyReport = $this->getPromoterReport('month');
        }

        for ($i = 0; $i < 7; $i++) {
            if (! isset($this->monthlyReport->items[$i])) {
                break;
            }

            $row_obj = new stdClass();
            $row_obj->day = $this->monthlyReport->items[$i]->period;
            $row_obj->customers = $this->monthlyReport->items[$i]->customers_count;
            $row_obj->earning = substr($this->monthlyReport->items[$i]->promoter_earnings_amount, 1);
            $return['data'][] = $row_obj;

        }
        echo json_encode($return);
        wp_die();

    }
    public function get_weekly_performanceTable()
    {
        $draw = $_POST['draw'];
        $offset = $_POST['start'];
        $count = $_POST['length'];
        $order = $_POST['order'][0]['column'] ?? "";
        $direction = $_POST['order'][0]['dir'] ?? "";
        $search_value = $_POST['search']['value'] ?? "";

        $return = array();
        $return['draw'] = $draw;
        $return['recordsTotal'] = 4;
        $return['recordsFiltered'] = 4;
        $return['data'] = array();

        if (! isset($this->dailyReport) || empty($this->dailyReport)) {
            $this->dailyReport = $this->getPromoterReport('day');

        }

        $startWeek = new DateTime("this week", new DateTimeZone("UTC"));
        $startWeek->setTime(0, 0, 0);

        for ($dayIndex = 0; $dayIndex < 28; $dayIndex++) {
            if (! isset($this->dailyReport->items[$dayIndex])) {
                break;
            }

            $customers = 0;
            $earning = 0;
            while (true) {
                if (! isset($this->dailyReport->items[$dayIndex])) {
                    break;
                }
                $reportDay = new DateTime($this->dailyReport->items[$dayIndex]->period);
                if ($reportDay < $startWeek) {
                    break;
                }

                $customers += $this->dailyReport->items[$dayIndex]->customers_count;
                $earning += preg_replace('/\$|\,/', '', $this->dailyReport->items[$dayIndex]->promoter_earnings_amount);
                $dayIndex++;
            }
            $row_obj = new stdClass();
            $row_obj->day = $startWeek->format('F d');
            $row_obj->customers = $customers;
            $row_obj->earning = number_format($earning, 2);

            $dayIndex--;
            $return['data'][] = $row_obj;
            $startWeek->modify('-1 week');

        }
        echo json_encode($return);
        wp_die();

    }
    public function getRewardsTable()
    {
        $draw = $_POST['draw'];
        $offset = $_POST['start'];
        $count = $_POST['length'];
        $order = $_POST['order'][0]['column'] ?? "";
        $direction = $_POST['order'][0]['dir'] ?? "";
        $search_value = $_POST['search']['value'] ?? "";

        $response = $this->getRequestwithHeader("https://firstpromoter.com/api/v1/rewards/list?page=" . (($offset / 50) + 1) . "&ref_id=" . $this->getDefaultRefId());
        $per_page = intval(trim($response['header']['Per-Page']));
        $total = intval(trim($response['header']['Total']));

        $return = array();
        $return['draw'] = $draw;
        $return['recordsTotal'] = $total;
        $return['recordsFiltered'] = $total;
        $return['data'] = array();

        foreach ($response['body'] as $reward) {
            $row_obj = new stdClass();
            $row_obj->status = $reward->status;
            $row_obj->amount = "$" . $reward->amount / 100;
            $row_obj->from_customer = $reward->lead->email;
            $row_obj->created = $reward->created_at;
            $return['data'][] = $row_obj;
        }
        echo json_encode($return);
        wp_die();

    }
    public function getLeadsTable()
    {
        $draw = $_POST['draw'];
        $offset = $_POST['start'];
        $count = $_POST['length'];
        $order = $_POST['order'][0]['column'] ?? "";
        $direction = $_POST['order'][0]['dir'] ?? "";
        $search_value = $_POST['search']['value'] ?? "";

        $response = $this->getRequestwithHeader("https://firstpromoter.com/api/v1/leads/list?page=" . (($offset / 50) + 1) . "&ref_id=" . $this->getDefaultRefId());
        $per_page = intval(trim($response['header']['Per-Page']));
        $total = intval(trim($response['header']['Total']));

        $return = array();
        $return['draw'] = $draw;
        $return['recordsTotal'] = $total;
        $return['recordsFiltered'] = $total;
        $return['data'] = array();

        foreach ($response['body'] as $lead) {
            $row_obj = new stdClass();
            $row_obj->email = $lead->email;
            $row_obj->created = $lead->created_at;
            $return['data'][] = $row_obj;
        }
        echo json_encode($return);
        wp_die();

    }

    public function getCurrentPromoter()
    {
        while ($this->promoterFlag) {
            usleep(200000);
        }
        $this->promoterFlag = true;
        if ($this->promoter == null) {
            $ret = $this->getRequest($this->api_base_url . 'v1/promoters/show?promoter_email=' . urlencode(wp_get_current_user()->user_email));
            $this->promoter = $ret;
        }
        $this->promoterFlag = false;
        return $this->promoter;
    }
    public function getPromoterId()
    {
        while ($this->promoterIdFlag) {
            usleep(200000);
        }
        $this->promoterIdFlag = true;
        $this->promoter_id = get_user_meta(wp_get_current_user()->ID, 'promoter_id', true);

        if (! isset($this->promoter_id) || $this->promoter_id == "") {
            $promoter = $this->getCurrentPromoter();
            $this->promoter_id = $promoter->id;
            update_user_meta(wp_get_current_user()->ID, 'promoter_id', $this->promoter_id);

        }

        $this->promoterIdFlag = false;
        return $this->promoter_id;
    }
    public function getDefaultRefId()
    {
        while ($this->defaultRefIdFlag) {
            usleep(200000);
        }

        $this->defaultRefIdFlag = true;
        $this->default_ref_id = get_user_meta(wp_get_current_user()->ID, 'default_ref_id', true);
        if (! isset($this->default_ref_id) || $this->default_ref_id == "") {
            $promoter = $this->getCurrentPromoter();
            $this->default_ref_id = $promoter->default_ref_id;
            update_user_meta(wp_get_current_user()->ID, 'default_ref_id', $this->default_ref_id);

        }

        $this->defaultRefIdFlag = false;

        return $this->default_ref_id;

    }
    public function getPromoterAuthToken()
    {
        $this->auth_token = get_user_meta(wp_get_current_user()->ID, 'auth_token', true);
        if (! isset($this->auth_token) || $this->auth_token == "") {
            $promoter = $this->getCurrentPromoter();
            $this->auth_token = $promoter->auth_token;
            update_user_meta(wp_get_current_user()->ID, 'auth_token', $this->auth_token);
        }
        return $this->auth_token;
    }
    public function getnumberofvisitorscount()
    {
        $promoter = $this->getCurrentPromoter();
        $promotions = $promoter->promotions;

        $visitor_count = 0;
        foreach ($promotions as $promotion) {
            $visitor_count = $visitor_count + $promotion->visitors_count;
        }
        return number_format($visitor_count);

    }

    public function gettotalcommissions()
    {
        $promoter = $this->getCurrentPromoter();
        return "$" . number_format(($promoter->earnings_balance->cash) / 100, 0);

    }
    public function getnumberofactivesales()
    {
        $promoter = $this->getCurrentPromoter();
        $promotions = $promoter->promotions;

        $active_customers_count = 0;
        foreach ($promotions as $promotion) {
            $active_customers_count = $active_customers_count + $promotion->customers_count;
        }
        return number_format($active_customers_count);

    }
    public function getpaidcommissions()
    {
        $promoter = $this->getCurrentPromoter();
        return "$" . number_format(($promoter->paid_balance->cash) / 100, 0);

    }
    public function getupcomingcommissions()
    {
        $promoter = $this->getCurrentPromoter();
        return "$" . number_format(($promoter->current_balance->cash) / 100, 0);

    }
    public function getnumberofactiveleads()
    {
        $promoter = $this->getCurrentPromoter();
        $promotions = $promoter->promotions;

        $leads_count = 0;
        foreach ($promotions as $promotion) {
            $leads_count = $leads_count + $promotion->leads_count;
        }
        return number_format($leads_count);
    }
    public function getRequest($uri)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'x-api-key: 5c4ebdf7622bea1aa2c2f8be467d0c01',
            ),
        ));

        $response = json_decode(curl_exec($curl));
        curl_close($curl);
        return $response;

    }
    public function getRequestwithHeader($uri)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array(
                'x-api-key: 5c4ebdf7622bea1aa2c2f8be467d0c01',
            ),
        ));

        $response = curl_exec($curl);

        // echo $response;
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headerStr = substr($response, 0, $headerSize);
        $bodyStr = substr($response, $headerSize);

        // convert headers to array
        $headers = $this->headersToArray($headerStr);
        $res = json_decode($bodyStr);
        curl_close($curl);

        return [
            "header" => $headers,
            "body" => $res,
        ];

    }
    public function postRequest($uri, $payload)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HEADER => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_HTTPHEADER => array(
                'x-api-key: ' . $this->apiKey,
                'Content-Type: application/x-www-form-urlencoded',
            ),
        ));

        $response = curl_exec($curl);

        // echo $response;
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headerStr = substr($response, 0, $headerSize);
        $bodyStr = substr($response, $headerSize);

        // convert headers to array
        $headers = $this->headersToArray($headerStr);
        curl_close($curl);
        return [
            "header" => $headers,
            "body" => json_decode($bodyStr),
        ];

    }
    public function headersToArray($str)
    {
        $headers = array();
        $headersTmpArray = explode("\r\n", $str);
        for ($i = 0; $i < count($headersTmpArray); ++$i) {
            // we dont care about the two \r\n lines at the end of the headers
            if (strlen($headersTmpArray[$i]) > 0) {
                // the headers start with HTTP status codes, which do not contain a colon so we can filter them out too
                if (strpos($headersTmpArray[$i], ":")) {
                    $headerName = substr($headersTmpArray[$i], 0, strpos($headersTmpArray[$i], ":"));
                    $headerValue = substr($headersTmpArray[$i], strpos($headersTmpArray[$i], ":") + 1);
                    $headers[$headerName] = $headerValue;
                }
            }
        }
        return $headers;

    }

}
FirstPromoter::getInstance();