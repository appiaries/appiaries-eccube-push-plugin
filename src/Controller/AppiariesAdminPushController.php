<?php
/**
 * Appiaries Push Notifications EC-CUBE 3 Plugin v1.0.1
 * melissa always loves you!
 * Copyright (c) 2015 Appiaries Co.
 * Under the terms of the MIT license.
 * http://www.appiaries.com/
 *
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE. 
 */
namespace Plugin\Appiaries\Controller;

use Eccube\Application;
use Eccube\Common\Constant;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

// Throwing our own exception.
use Eccube\Exception\PluginException as Exception;

class AppiariesAdminPushController
{

    /** @const */
    const PUSH_LIST_ITEMS_PER_PAGE = 10;

    /** @const */
    const PUSH_API_REQUEST_MAX = 1000;

    /** @private */
    private static $session_key = 'eccube.plugin.appiaries.admin'; // not in use

    /** @private */
    private static $all_the_keys = array(
        // (this is to distinguish which page are we on)
        'mode',
        // Search
        'os','pref','sex','age_min','age_max','job',
        'updated_min','updated_max',
        'created_min','created_max', 'product_name',
        'purchase_total_min','purchase_total_max',
        'purchase_count_min','purchase_count_max',
        'purchase_last_min','purchase_last_max',
        // Message
        'push_reserve_title','title','sound','badge','message','rich_push',
        // Reserve
        'delivery_date'
    );


    /** @public */
    public function __construct() {}


    /**
     * @public
     * @param Application $app
     * @param Request $request
     * @param integer $pg
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function list_all_the_push_reservations(Application $app, Request $request, $pg = '') {

        $err  = ''; // avoid setting "null" (for you may not validate with "isset")
        $data = array();

        $app['eccube.plugin.appiaries.repository.settings']->get_settings($data, $err);

        if ($err) {
            error_log('[AppiariesAdminPushController]   ' . $err);
            $err = 'database_error';
        }
        else {
            $data['pg'] = $pg ? (int) $pg : 1;
            $service    = $app['eccube.plugin.appiaries.service'];
            $res        = $this->get_reserved_push_list($service, $data, array(), $err);
            if (!$err && $res) {
                $data['pager'] = $service->easy_pager(
                    $res->data,
                    array('per' => self::PUSH_LIST_ITEMS_PER_PAGE, 'pg' => $data['pg']),
                    $err
                );
                if ($err) {
                    error_log('[AppiariesAdminPushController]   ' . $err);
                    $err = 'push_data_error';
                }
                else if (!$data['pager']) {
                    $err = 'no_pager';
                }
            }
            if (!$err && isset($data['pager']->list) && count($data['pager']->list)) {
                $tmp = array();
                $i = 0;
                foreach ($data['pager']->list as $row) {
                    if ($this->make_data_more_human_friendly($row, $err)) {
                        if ($row->_id) {
                            $tmp[] = $row;
                        }
                    }
                    else {
                        error_log('[AppiariesAdminPushController]     [i] ' . $i . ' seems to have error: ' . $err);
                        break;
                    }
                    $i++;
                }
                if (!$err) {
                    $data['pager']->list = $tmp;
                }
                $tmp = null;
            }
            $service = null;
        }
        // If coming from deleting push reservation.
        if ($request->query->get('delete_success')) {
            $app->addSuccess('admin_appiaries_push_delete_success', 'admin');
        }
        if ($err) {
            error_log('[AppiariesAdminPushController]   ' . $err);
            $app->addError(('admin_appiaries_' . $err), 'admin');
        }
        return $app->render("Appiaries/templates/admin/push/list.twig", $data);
    }
    // END OF: list_all_the_push_reservations()


    /**
     * @public
     * @param Application $app
     * @param Request $request
     * @param string $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function view(Application $app, Request $request, $id = null) {

        $err  = ''; // avoid setting "null" (for you may not validate with "isset")
        $data = array();

        if (!$id) { $err = 'no_id'; }
        else {
            $app['eccube.plugin.appiaries.repository.settings']->get_settings($data, $err);
            if ($err) {
                error_log('[AppiariesAdminPushController]   ' . $err);
                $err = 'database_error';
            }
        }
        if (!$err) {
            $pg = $request->query->get('pg');
            if (isset($pg)) {
                $data['pg'] = $pg; // Used for BACK button.
            }
            $data['_id'] = $id;
            $service = $app['eccube.plugin.appiaries.service'];
            $res     = $this->get_reserved_push($service, $data, array(), $err);
            if (!$err && (!$res || !count($res))) {
                $err = 'no_push_data';
            }
            if (!$err) {
                $res = $res[0];
                if ($this->make_data_more_human_friendly($res, $err)) {
                    $data['data'] = array();
                    foreach ($res as $k => $v) { $data['data'][$k] = $v; }
                }
            }
            $service = null;
            $res     = null;
        }
        if ($err) {
            error_log('[AppiariesAdminPushController]   ' . $err);
            $app->addError(('admin_appiaries_' . $err), 'admin');
        }
        return $app->render("Appiaries/templates/admin/push/view.twig", $data);
    }
    // END OF: view()


    /**
     * @public
     * @param Application $app
     * @param Request $request
     * @param string $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function edit(Application $app, Request $request, $id = null) {

        $err    = ''; // avoid setting "null" (for you may not validate with "isset")
        $data   = array();
        $form   = null;
        $method = $request->getMethod();

        $data['mode'] = 'edit';

        if (!$id) { $err = 'no_id'; }
        else {
            $app['eccube.plugin.appiaries.repository.settings']->get_settings($data, $err);
            if ($err) {
                error_log('[AppiariesAdminPushController]   ' . $err);
                $err = 'database_error';
            }
            if (!$err) {
                $form = $app['form.factory']->createBuilder('appiaries_push', null)->getForm();
            }
        }
        if (!$err) {
            $pg = $request->query->get('pg'); // GET
            if (!isset($pg)) {
                $pg = $request->request->get('pg'); // POST
            }
            if (isset($pg)) {
                $data['pg'] = $pg; // Used for BACK button (when coming from the list).
            }
            $data['_id'] = $id;
            $service = $app['eccube.plugin.appiaries.service'];
            $res     = $this->get_reserved_push($service, $data, array(), $err);
            if (!$err && (!$res || !count($res))) {
                $err = 'no_push_data';
            }
            if (!$err) {
                $res = $res[0];
                if ($this->make_data_more_human_friendly($res, $err)) {
                    // Copy values of "$res" to "$data['data']".
                    // For "delivery_date", "push_reserve_title", "title", and "message",
                    // we make them into a form friendly format (copying it to "$data").
                    $data['data'] = array();
                    foreach ($res as $k => $v) {
                        // For the form.
                        if ($k == 'reserve_for_form') {
                            $data['delivery_date'] = null;
                            $data['delivery_date_default'] = $v;
                        }
                        if ($k == 'push_reserve_title') {
                            $data['push_reserve_title'] = $v;
                        }
                        if ($k == '_openUrl') {
                            $data['rich_push'] = $v;
                        }
                        if ($res->type == 'apns') {
                            if ($k == 'apns') {
                                if (isset($v->alert) && isset($v->alert->body)) {
                                    $data['message'] = $v->alert->body;
                                }
                                if (isset($v->sound)) {
                                    $data['sound'] = $v->sound;
                                }
                                if (isset($v->badge)) {
                                    $data['badge'] = $v->badge;
                                }
                            }
                        }
                        if ($res->type == 'gcm') {
                            if ($k == 'custom' && count($v)) {
                                foreach ($v as $custom_key => $custom_value) {
                                    if (preg_match('/^(?:title|message)$/', $custom_key)) {
                                        $data[$custom_key] = $custom_value;
                                    }
                                }
                            }
                        }
                        $data['data'][$k] = $v; // Copy
                    }
                }
            }
            $res = null;

            if (!$err && $method == 'POST') {

                $form->handleRequest($request);
                $queries = $form->getData();

                // Simply extracting everything in "$queries" to set them to "$data".
                // Some of them will be modified when necessary.
                if ( !$this->convert_queries_into_data($queries, $data, $err) ) {
                    error_log('[AppiariesAdminPushControrller]   ' . $err);
                    $err = 'push_edit_failed';
                }
                else if ( !$form->isValid() ) {
                    error_log('[AppiariesAdminPushControrller]   ' . $form->getErrors());
                    $err = 'validation_failed';
                }
                // Only when the validation is through, you may change the mode.
                else {
                    // Sending API request to Appiaries.
                    $this->request_push_reservation($app, $data, array('method' => 'PUT', 'act' => 'edit'), $err);
                    if ($err) {
                        $data['error'] = $err;
                    }
                    else {
                        $data['success'] = 'must_be_very_proud_of_yourself';
                    }
                }
                $queries = null;
            }
            $service = null;
        }
        if ($err) {
            error_log('[AppiariesAdminPushController]   ' . $err);
            $app->addError(('admin_appiaries_' . $err), 'admin');
        }
        else if ($method == 'POST') {
            $app->addSuccess('admin_appiaries_push_edit_success', 'admin');
        }

        $data['edit_push_form'] = $form->createView();

        $err     = null;
        $request = null;
        $form    = null;

        return $app->render("Appiaries/templates/admin/push/edit.twig", $data);
    }
    // END OF: edit()


    /**
     * @public
     * @param Application $app
     * @param Request $request
     * @param string $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete(Application $app, Request $request, $id = null) {

        $err    = ''; // avoid setting "null" (for you may not validate with "isset")
        $data   = array();
        $form   = null;
        $method = $request->getMethod();

        if (!$id) { $err = 'no_id'; }
        else {
            $app['eccube.plugin.appiaries.repository.settings']->get_settings($data, $err);
            if ($err) {
                error_log('[AppiariesAdminPushController]   ' . $err);
                $err = 'database_error';
            }
        }
        if (!$err) {
            $pg = $request->query->get('pg');
            if (isset($pg)) {
                $data['pg'] = $pg; // Used for BACK button (when coming from the list).
            }
            $data['_id'] = $id;
            $service = $app['eccube.plugin.appiaries.service'];
            $res     = $this->get_reserved_push($service, $data, array(), $err);
            if (!$err && (!$res || !count($res))) {
                $err = 'no_push_data';
            }
            if (!$err) {
                $res = $res[0];
                if ($this->make_data_more_human_friendly($res, $err)) {
                    $data['data'] = array();
                    foreach ($res as $k => $v) { $data['data'][$k] = $v; }
                    $data['delete'] = $request->query->get('delete');
                }
            }
            if (!$err && isset($data['delete'])) {
                if ($this->request_push_deletion($app, $data, array(), $err)) {
                    // Redirect back to the list and show success message.
                    return $app->redirect(
                        $app->url(
                            'admin_appiaries_push_list',
                            array(
                                'pg' => $pg,
                                'delete_success' => 1
                            )
                        )
                    );
                }
            }
            $res     = null;
            $service = null;
        }
        if ($err) {
            error_log('[AppiariesAdminPushController]   ' . $err);
            $app->addError(('admin_appiaries_' . $err), 'admin');
        }
        else if ($method == 'POST') {
            $app->addSuccess('admin_appiaries_push_delete_success', 'admin');
        }

        $err     = null;
        $request = null;

        return $app->render("Appiaries/templates/admin/push/delete.twig", $data);
    }
    // END OF: delete()


    /**
     * Add Push Delivery (Step 1) SEARCH
     * Sets search conditions to extract target devices for a push delivery.
     * @public
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function add_search(Application $app, Request $request) {

        $err  = ''; // avoid setting "null" (for you may not validate with "isset")
        $data = array();

        // Because twig gives us error when receiving undefined variables,
        // we are settings "null" for all.
        $data['pager'] = null;
        foreach (self::$all_the_keys as $key) {
            $data[$key] = null;
        }

        $form = $app['form.factory']->createBuilder('appiaries_push', null)->getForm();

        // Checking for "datastore_id", "app_id", and "app_token".
        // Although they aren't needed for device search,
        // we need to inform users if they are missing.
        $app['eccube.plugin.appiaries.repository.settings']->get_settings($data, $err);

        if (!$err && $request->getMethod() == 'POST') {
            $form->handleRequest($request);
            $queries = $form->getData();
            $res = null;
            if ($this->convert_queries_into_data($queries, $data, $err)) {
                // When "job" is selected, Javascript will highlight
                // the already selected jobs for select menu.
                if (count($data['jobs'])) {
                    $data['jobs_json'] = json_encode($data['jobs']);
                }
                if ( !$form->isValid() ) {
                    error_log('[AppiariesAdminPushControrller]   ' . $form->getErrors());
                    $err = 'validation_failed';
                }
                else {
                    try {
                        $res = $this->get_device_list($app, $data, array('all' => true), $err);
                    }
                    catch (\Exception $e) {
                        error_log('[AppiariesAdminPushController]   ' . $e->getMessage());
                        $err = 'database_error';
                    }
                }
            }
            if (!$err && $res) {
                // "pg_max" is not in use (setting "25" for default max for pager).
                $pg_max = isset($data['page_max']) ? $data['page_max'] : 25;
                $pg = isset($data['pg']) ? $data['pg'] :
                    ($request->request->get('pg') ? $request->request->get('pg') : // POST
                     ($request->query->get('pg') ? $request->query->get('pg') : // GET
                      1
                     ));
                $data['pager'] = $app['eccube.plugin.appiaries.service']->easy_pager(
                    $res,
                    array('pg' => $pg, 'per' => $pg_max),
                    $err
                );
            }
            $queries = null;
            $res = null;
        }
        if ($err) {
            error_log('[AppiariesAdminPushController]   ' . $err);
            $app->addError(('admin_appiaries_' . $err), 'admin');
        }
        $data['add_search_form'] = $form->createView();
        $request = null;
        $form    = null;
        $err     = null;

        return $app->render('Appiaries/templates/admin/push/add_search.twig', $data);
    }
    // END OF: add_search


    /**
     * Add Push Delivery (Step 2) RESERVE
     * With the search conditions set (for target devices), the procedures follow:
     * 1. Compose a push notification message.
     * 2. Setting reservation datetime.
     * 3. Confirm registered entries (including the search conditions).
     * and when all these steps are complete, then:
     * 4. Request Appiaries for the push delivery.
     * @public
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function add_reserve(Application $app, Request $request) {

        $err  = ''; // avoid setting "null" (for you may not validate with "isset")
        $data = array();

        foreach (self::$all_the_keys as $key) {
            $data[$key] = null;
        }

        $form = $app['form.factory']->createBuilder('appiaries_push', null)->getForm();

        if ($request->getMethod() != 'POST') { $err = 'method_must_be_post'; }
        else {

            $form->handleRequest($request);
            $queries = $form->getData();

            // "mode" is not set if coming from the search, so setting "from_the_search".
            if (!isset($queries['mode'])) {
                $queries['mode'] = 'from_the_search';
            }
            // When "title" and "message" is submitted, change the mode to "compose_message".
            else if ($queries['mode'] == 'from_the_search') {
                $queries['mode'] = 'compose_message';
            }

            // Simply extracting everything in "$queries" to set them to "$data".
            // Some of them will be modified when necessary.
            if ( !$this->convert_queries_into_data($queries, $data, $err) ) {
                error_log('[AppiariesAdminPushControrller]   ' . $err);
                $err = 'add_reserve_failed';
            }
            else {
                // Checking for "datastore_id", "app_id", and "app_token".
                $app['eccube.plugin.appiaries.repository.settings']->get_settings($data, $err);
            }
            $queries = null;

            if ( !$err ) {

                if ($data['mode'] == 'compose_message') {
                    // RESERVE PUSH DELIVERY
                    // Having "compose_message" for the current mode,
                    // you are actually dealing with "reserve_push_delivery".
                    $dt = new \DateTime();
                    $dt->add(new \DateInterval('PT30M'));
                    $dt = $dt->format('Y-m-d H:i');
                    $data['delivery_date_default'] = $dt; // for "delivery_date"
                    $dt = null;
                }

                if ($data['mode'] == 'reserve_push_delivery') {
                    // CONFIRM RESERVATION
                    // Having "reserve_push_delivery" for the current mode,
                    // you are actually dealing with "confirm_reservation".
                    if ($this->count_device_list($app, $data, $err)) {
                        // Generating a check-list for confirmation.
                        $this->make_check_list($app, $form, $data, $err);
                    }
                    if ($err && $err != 'database_error') {
                        error_log('[AppiariesAdminPushControrller]   ' . $err);
                        $err = 'push_add_final_data_failed';
                    }
                }
            }
            // END OF: if (!err)

            if ( !$err ) {
                // You are actually validating previously submitted entries.
                if ( !$form->isValid() ) {
                    error_log('[AppiariesAdminPushControrller]   ' . $form->getErrors());
                    $err = 'validation_failed';
                }
                // Only when the validation is through, you may change the mode.
                else {
                    $data['mode'] = $data['mode'] == 'compose_message' ? 'reserve_push_delivery' :
                        ($data['mode'] == 'reserve_push_delivery' ? 'confirm_reservation' :
                         ($data['mode'] == 'confirm_reservation' ? 'done' :
                          'from_the_search'));

                    if ($data['mode'] == 'done') {
                        // Sending API request to Appiaries.
                        $this->request_push_reservation($app, $data, array(), $err);
                        if ($err) {
                            $data['error'] = $err;
                        }
                    }
                }
                // END OF: isValid()
            }
        }
        // END OF: if ( !POST )

        if ($err) {
            error_log('[AppiariesAdminPushController]   ' . $err);
            $app->addError(('admin_appiaries_' . $err), 'admin');
        }

        $data['add_reserve_form'] = $form->createView();

        $err     = null;
        $request = null;
        $form    = null;

        return $app->render('Appiaries/templates/admin/push/add_reserve.twig', $data);
    }
    // END OF: add_reserve()



    /**
     * Convert HTTP query parameters to "string" and set them to "$data".
     * @public
     * @param array $queries
     * @param object &$data
     * @param object &$err
     * @return boolean
     */
    private function convert_queries_into_data($queries, &$data, &$err) {
        if (!isset($err)) { throw new Exception('Need placeholder for error.'); }
        if (!isset($queries)) { $err = 'no_queries'; }
        else if (!isset($data)) { $err = 'no_data'; }
        else if (gettype($data) != 'array') { $err = 'must_be_array_for_data'; }
        else {
            foreach ($queries as $k => $v) {
                if ($k == 'pref' && gettype($v) == 'object') {
                    $v = $v->getId();
                }
                if (preg_match('/(?:(?:updated|created|last)_|delivery_date)/', $k)) {
                    $v = ($v && gettype($v) != 'string') ? $v->format('Y-m-d H:i:s') : $v;
                }
                if ($k == 'job') {
                    if (!isset($data['jobs'])) {
                        $data['jobs'] = array(); // Each "job" pushed to "jobs".
                        if (gettype($v) == 'object') {
                            foreach ($v as $i => $job) {
                                $data['jobs'][] = $job->getId();
                            }
                        }
                    }
                }
                $data[$k] = $v;
            }
            $queries = null;
        }
        return $err ? false : true;
    }
    // END OF: convert_queries_into_data()


    /**
     * Given the search conditions set in "$data", retrieves and count the actual target devices.
     * @public
     * @param object &$app;
     * @param object &$data
     * @param object &$err
     * @return boolean
     */
    private function count_device_list(&$app, &$data, &$err) {
        if (!isset($err)) { throw new Exception('Need placeholder for error.'); }
        else if (!isset($app)) { $err = 'no_app'; }
        else if (!isset($data)) { $err = 'no_data'; }
        else if (gettype($data) != 'array') { $err = 'must_be_array_for_data'; }
        else {
            $cnt = 0;
            try {
                $cnt = $app['eccube.plugin.appiaries.repository.devices']
                    ->search($data, array('act' => 'count'));
            }
            catch (\Exception $e) {
                error_log('[AppiariesAdminPushController]   ' . $e->getMessage());
                $err = 'database_error';
            }
            $data['delivery_count'] = $cnt;
            $delta = $cnt - self::PUSH_API_REQUEST_MAX;
            if ($delta < 0) { $delta = 0; }
            if ($delta > 0) {
                $data['exceeds_push_api_request_max'] = $delta;
            }
        }
        return $err ? false : true;
    }
    // END OF: count_device_list()


    /**
     * Retrieves the actual target devices and make it a list.
     * @public
     * @param object &$app;
     * @param object &$data
     * @param object $options
     * @param object &$err
     * @return array
     */
    private function get_device_list(&$app, &$data, $options, &$err) {
        $list = array();
        if (!isset($err)) { throw new Exception('Need placeholder for error.'); }
        else if (!isset($app)) { $err = 'no_app'; }
        else if (!isset($data)) { $err = 'no_data'; }
        else if (gettype($data) != 'array') { $err = 'must_be_array_for_data'; }
        else {
            $is_all_data_requested = false;
            if ($options && isset($options['all']) && $options['all'] === true) {
                $is_all_data_requested = true;
            }
            try {
                $res = $app['eccube.plugin.appiaries.repository.devices']
                    ->search($data, array('act' => 'get'));
            }
            catch (\Exception $e) {
                error_log('[AppiariesAdminPushController]   ' . $e->getMessage());
                $err = 'database_error';
            }
            if (!$err && $res) {
                // If all the data is requested.
                if ($is_all_data_requested === true) {
                    $list = $res;
                }
                // Needs "devices_id" only.
                else {
                    foreach ($res as $row) {
                        $list[] = $row['device_id'];
                    }
                }
                $cnt = count($list);
                if ($cnt) {
                    $delta = $cnt - self::PUSH_API_REQUEST_MAX;
                    if ($delta < 0) { $delta = 0; }
                    if ($delta > 0) {
                        // doraemon
                        $data['exceeds_push_api_request_max'] = $delta;
                    }
                }
                $res = null;
            }
        }
        return $list;
    }


    /**
     * Generate "$data['check_list']" out of the current "$data".
     * @public
     * @param object &$app;
     * @param object &$form;
     * @param object &$data
     * @param object &$err
     * @return boolean
     */
    private function make_check_list(&$app, &$form, &$data, &$err) {
        if (!isset($err)) { throw new Exception('Need placeholder for error.'); }
        else if (!isset($app)) { $err = 'no_app'; }
        else if (!isset($form)) { $err = 'no_form'; }
        else if (!isset($data)) { $err = 'no_data'; }
        else if (gettype($data) != 'array') { $err = 'must_be_array_for_data'; }
        else {
            $data['check_list'] = array();

            foreach (self::$all_the_keys as $key) {
                $config  = $form->get($key)->getConfig();
                $type    = $config ? $config->getType()->getName() : null;
                $options = $config ? $config->getOptions() : null;
                $label   = $options ? $options['label'] : null;
                if ($key == 'mode') {
                    continue;
                }
                $value = $data[$key];
                if ( !isset($value) ) {
                    continue;
                }
                if (preg_match('/(?:pref|sex)/', $key)) {
                    $path   = 'Eccube\\Entity\\Master\\' . ucfirst($key);
                    $master = $app['orm.em']->getRepository($path)->find($value);
                    $value  = $master ? $master->getName() : $value;
                    $path   = null;
                    $master = null;
                }
                if (preg_match('/^age/', $key)) {
                    $value .= '才';
                }
                if ($key == 'job') {
                    $value = null;
                    if (isset($data['jobs']) && count($data['jobs'])) {
                        $tmp = array();
                        foreach ($data['jobs'] as $job) {
                            $master = $app['orm.em']
                                ->getRepository('Eccube\Entity\Master\Job')
                                ->find($job);
                            if ($master) {
                                $tmp[] = $master->getName();
                            }
                            $master = null;
                        }
                        if (count($tmp)) { $value = implode("\n", $tmp); }
                        $tmp = null;
                    }
                }
                if (preg_match('/(?:(?:updated|created|last)_)/', $key) &&
                    preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m)) {
                    $value = sprintf('%d年%d月%d日',
                                     (int) $m[1], (int) $m[2], (int) $m[3] );
                }
                if (preg_match('/purchase_total/', $key)) {
                    $value .= '円';
                }
                if (preg_match('/purchase_count/', $key)) {
                    $value .= '回';
                }
                if ($key == 'os') {
                    $value = $value == 'ios' ? 'iOS' : 'Android';
                }
                if ($key == 'delivery_date' &&
                    preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/', $value, $m)) {
                    $value = sprintf('%d年%d月%d日%d時%d分',
                                     (int) $m[1], (int) $m[2], (int) $m[3],
                                     (int) $m[4], (int) $m[5] );
                }
                if ($key && $label && $value) {
                    $data['check_list'][$key] = array(
                        'key'   => $key,
                        'label' => $label,
                        'value' => $value,
                    );
                }
                $config  = null;
                $options = null;
                $value   = null;
            }
            // END OF: foreach

            if (isset($data['delivery_count'])) {
                $data['check_list']['delivery_count'] = array(
                    'key'   => 'delivery_count',
                    'label' => '配信対象合計',
                    'value' => $data['delivery_count'] . '人',
                );
            }
        }

        return $err ? false : true;
    }
    // END OF: make_check_list()


    /**
     * Requests Appiaries and returns all the already reserved push deliveries.
     * When "beg" and "end" specified, then returns the data respectively.
     * When not specified, calls "get_reserved_push_list_aux()" recursively to retrieve them all.
     * @private
     * @param object &$service
     * @param object &$data
     * @param object $options
     * @param object &$err
     * @return array
     */
    private function get_reserved_push_list(&$service, &$data, $options, &$err) {
        if (!isset($err)) { throw new Exception('Need placeholder for error.'); }

        $res        = new \stdClass;
        $res->total = 0;
        $res->data  = array();
        $beg        = 0;
        $end        = 0;
        $step       = 100;
        $need_all   = true;

        if (!isset($service))                    { $err = 'no_service'; }
        else if (!isset($data))                  { $err = 'no_data'; }
        else if (gettype($data) != 'array')      { $err = 'must_be_array_for_data'; }
        else if (!isset($data['datastore_id']))  { $err = 'no_datastore_id'; }
        else if (!isset($data['app_id']))        { $err = 'no_app_id'; }
        else if (!isset($data['app_token']))     { $err = 'no_app_token'; }
        else {
            if ($options) {
                if (isset($options['beg']) || isset($options['end'])) {
                    if (isset($options['beg'])) { $beg = $options['beg']; }
                    if (isset($options['end'])) { $end = $options['end']; }
                    if (!( $beg && $end )) { $err = 'needs_both_beg_and_end'; }
                    else {
                        $delta = $end - $beg;
                        if ($delta < 0) { $err = 'end_is_greater'; }
                        else if ($delta > 100) { $err = 'more_than_100'; }
                        else {
                            if ($delta > $step) {
                                $end = $beg + $step;
                            }
                            $need_all = false;
                        }
                    }
                }
            }
        }
        if (!$err) {
            $total = 0;
            if ($need_all === true) {
                // First, getting "_total" (grand total).
                $chk = $this->get_reserved_push_list_aux($service, $data, null, $err);
                if (!$err && isset($chk) && isset($chk->_total)) {
                    // Setting "total" in "$res" as a grand total.
                    $total = $res->total = $chk->_total;
                    $loop_counter = 0;
                    $cnt   = 0;
                    while (1) {
                        // 1000000 records (---> 10000 x 100)
                        if (($loop_counter >= 1 && ($cnt + 1) >= $total) || $loop_counter >= 10000) {
                            break;
                        }
                        $_beg = ($loop_counter == 0) ? '' : $beg;
                        $_end = ($loop_counter == 0) ? '' : $end;
                        $list = $this->get_reserved_push_list_aux($service, $data, array('beg' => $_beg, 'end' => $_end), $err);
                        if ($err) {
                            break;
                        }
                        if (isset($list) && isset($list->_objs) && $list->_total) {
                            if ($loop_counter > 0) { $beg = $end + 1; }
                            else {
                                $total = $res->total = $list->_total;
                                $beg = $step + 1;
                            }
                            $end = $beg + $step - 1;
                            foreach ($list->_objs as $row) {
                                $res->data[] = $row;
                                $cnt++;
                            }
                        }
                        $list = null;
                        $loop_counter++;
                    }
                    // END OF: while
                }
                $chk = null;
            }
            else {
                $list = $this->get_reserved_push_list_aux($service, $data, array('beg' => $beg, 'end' => $end), $err);
                if (!$err && isset($list) && isset($list->_total) && $list->_total) {
                    $total = $res->total = $list->_total;
                    $res->data = $list->_objs;
                }
                $list = null;
            }
            // END OF: if (is_retrieve_all)
        }
        return $res;
    }
    // END OF: get_reserved_push_list()


    /**
     * Being recursively called by "get_reserved_push_list()" (a public method)
     * @private
     * @param object &$service
     * @param object &$data
     * @param object $options
     * @param object &$err
     * @return boolean
     */
    private function get_reserved_push_list_aux(&$service, &$data, $options, &$err) {
        $res = array();

        if (!isset($service))                    { $err = 'no_service'; }
        else if (!isset($data['datastore_id']))  { $err = 'no_datastore_id'; }
        else if (!isset($data['app_id']))        { $err = 'no_app_id'; }
        else if (!isset($data['app_token']))     { $err = 'no_app_token'; }
        else {
            $beg = '';
            $end = '';
            if ($options) {
                if (isset($options['beg'])) { $beg = $options['beg']; }
                if (isset($options['end'])) { $end = $options['end']; }
            }
            $datastore_id     = $data['datastore_id'];
            $app_id           = $data['app_id'];
            $app_token        = $data['app_token'];
            $params           = array();
            $response         = array();
            $params['method'] = 'GET';
            $params['token']  = $app_token;
            $params['path']   = "/push/list/{$datastore_id}/{$app_id}/{$beg}-{$end}?order=-reservedate&get=true";
            try {
                $res = $service->request_appiaries($params, $response, $err);
            }
            catch(\Exception $e) {
                error_log('[AppiariesAdminPushController]   ' . $e->getMessage());
                $err = 'api_server_error';
            }
            if (!$err) {
                $status = $response ? $response['status_code'] : null;
                if ($status == '200') { }
                else if ($status == '400') { $err = 'invalid_request'; }
                else if ($status == '401') { $err = 'no_token_or_auth_failed'; }
                else if ($status == '403') { $err = 'not_authorized'; }
                else if ($status == '422') { $err = 'no_good_params'; }
                else if ($status == '500') { $err = 'server_error'; }
                else { $err = 'unknown'; }
            }
            if ($err) {
                error_log('[AppiariesAdminPushController]   ' . $err);
                // For errors other than listed, just let it be "api_server_error".
                $tmp = array(
                    'no_token_or_auth_failed',
                    'no_good_params',
                );
                if (!preg_match('/^(?:'.implode('|', $tmp).')$/', $err)) {
                    $err = 'api_server_error';
                }
            }
            $params   = null;
            $response = null;
        }
        return $res;
    }
    // END OF: get_reserved_push_list_aux()


    /**
     * Given the specific reservation ID, requests Appiaries and returns the corresponding data.
     * @private
     * @param object &$service
     * @param object &$data
     * @param object $options
     * @param object &$err
     * @return array
     */
    private function get_reserved_push(&$service, &$data, $options, &$err) {
        if (!isset($err)) { throw new Exception('Need placeholder for error.'); }

        $res           = new \stdClass;
        $res->total    = 0;
        $res->data     = array();
        $delivery_date = '';
        $app_token     = '';
        $reserve_id    = '';

        if (!isset($service))                    { $err = 'no_service'; }
        else if (!isset($data))                  { $err = 'no_data'; }
        else if (gettype($data) != 'array')      { $err = 'must_be_array_for_data'; }
        else if (!isset($data['datastore_id']))  { $err = 'no_datastore_id'; }
        else if (!isset($data['app_id']))        { $err = 'no_app_id'; }
        else if (!isset($data['app_token']))     { $err = 'no_app_token'; }
        else if (!isset($data['_id']))           { $err = 'no_id'; }
        else {
            if ($options) {
            }
            $datastore_id  = $data['datastore_id'];
            $app_id        = $data['app_id'];
            $app_token     = $data['app_token'];
            $reserve_id    = $data['_id'];
        }
        if (!$err) {
            $params    = array();
            $response  = array();
            $params['method'] = 'GET';
            $params['token']  = $app_token;
            $params['path']   = "/push/manage/{$datastore_id}/{$app_id}?reserveId={$reserve_id}&get=true";
            try {
                $res = $service->request_appiaries($params, $response, $err);
            }
            catch(\Exception $e) {
                error_log('[AppiariesAdminPushController]   ' . $e->getMessage());
                $err = 'api_server_error';
            }
            if (!$err) {
                $status = $response ? $response['status_code'] : null;
                if ($status == '200') { }
                else if ($status == '400') { $err = 'invalid_request'; }
                else if ($status == '401') { $err = 'no_token_or_auth_failed'; }
                else if ($status == '403') { $err = 'not_authorized'; }
                else if ($status == '422') { $err = 'no_good_params'; }
                else if ($status == '500') { $err = 'server_error'; }
                else { $err = 'unknown'; }
            }
            if ($err) {
                error_log('[AppiariesAdminPushController]   ' . $err);
                // For errors other than listed, just let it be "api_server_error".
                $tmp = array(
                    'no_token_or_auth_failed',
                    'no_good_params',
                );
                if (!preg_match('/^(?:'.implode('|', $tmp).')$/', $err)) {
                    $err = 'api_server_error';
                }
            }
            $params   = null;
            $response = null;
        }
        return $res;
    }
    // END OF: get_reserved_push()


    /**
     * Requests Appiaries to manage push delivery reservation.
     * Has 2 features:
     *   1. Add new push reservation
     *   2. Edit existing push reservation
     * @private
     * @param object &$app
     * @param object &$data
     * @param object $options
     * @param object &$err
     * @return boolean
     */
    private function request_push_reservation(&$app, &$data, $options, &$err) {
        if (!isset($err)) { throw new Exception('Need placeholder for error.'); }

        $delivery_date = '';
        $act           = 'add';
        $device_list   = array();
        $total         = 0;
        $loop_the_whole_device_list = false;

        if (!isset($app))                        { $err = 'no_app'; }
        else if (!isset($data))                  { $err = 'no_data'; }
        else if (gettype($data) != 'array')      { $err = 'must_be_array_for_data'; }
        else if (!isset($data['delivery_date'])) { $err = 'no_delivery_date'; }
        else if (!isset($data['datastore_id']))  { $err = 'no_datastore_id'; }
        else if (!isset($data['app_id']))        { $err = 'no_app_id'; }
        else if (!isset($data['app_token']))     { $err = 'no_app_token'; }
        else {
            if ($options && isset($options['act'])) {
                $act = $options['act'];
            }
            $checktime       = strtotime('now');
            $delivery_date   = $data['delivery_date'];
            $t_delivery_date = strtotime($delivery_date);
            if ($t_delivery_date < $checktime) {
                $err = 'already_delivered';
            }
            if (!$err && $act == 'add') {
                $device_list = $this->get_device_list($app, $data, null, $err);
                $total = count($device_list);
                if (!$err) {
                    if (!$total) {
                        $err = 'no_device_list';
                    }
                    else if (isset($data['exceeds_push_api_request_max'])) {
                        $loop_the_whole_device_list = true;
                    }
                    else {
                        $options['device_list'] = & $device_list;
                    }
                }
            }
        }
        if ($err) {
            return false;
        }
        // Exceeding 1000 push request max for Push API. Looping the request per 1000.
        if ($loop_the_whole_device_list === true) {
            $orig_title = $data['push_reserve_title'];
            // Original: "Christmas Campaign"
            // ---> "Christmal Campaign (1)", "Christmas Campagin (2)"....
            //--------------------------------------------------
            $__exec_recursive = function($_cnt, $_beg, $_end) use(&$app, &$data, &$option, &$err, &$device_list, $orig_title) {
                // error_log('[AppiariesAdminPushController]     [cnt] ' . $_cnt . ' [beg] ' . $_beg . ' [end] ' . $_end);
                $tmp = array();
                for ($i=$_beg; $i<=$_end; $i++) {
                    $tmp[] = $device_list[$i];
                }
                $options['device_list'] = & $tmp;
                $data['push_reserve_title'] = $orig_title . ' (' . ($_cnt + 1) . ')';
                $this->request_push_reservation_aux($app, $data, $options, $err);
            };
            //--------------------------------------------------
            $done = false;
            $cnt  = 0;
            $beg  = 0;
            $end  = self::PUSH_API_REQUEST_MAX - 1;
            while ($beg < $total && $end < $total) {
                // 9,000,000 deliveries are pretty silly.
                if ($err || $cnt >= 9000 || $beg > ($total - 1)) {
                // doraemon
                // if ($err || $cnt >= 5) {
                    $done = true;
                    if (!$err) { $err = 'recursive_push_request_error'; }
                    break;
                }
                $__exec_recursive($cnt, $beg, $end);
                $beg += self::PUSH_API_REQUEST_MAX;
                $end += self::PUSH_API_REQUEST_MAX;
                if ($end > ($total - 1)) {
                    $end = $total - 1;
                }
                $cnt++;
            }
            if ($done) {
                return $err ? false : true;
            }
        }
        // Normal push request.
        else {
            return $this->request_push_reservation_aux($app, $data, $options, $err);
        }
    }
    // END OF: request_push_reservation()


    private function request_push_reservation_aux(&$app, &$data, $options, &$err) {
        if (!isset($err)) { throw new Exception('Need placeholder for error.'); }

        $method        = 'POST';
        $reserve_id    = '';
        $act           = 'add';
        $device_list   = array();

        $delivery_date = $data['delivery_date'];
        $datastore_id  = $data['datastore_id'];
        $app_id        = $data['app_id'];
        $app_token     = $data['app_token'];

        if ($options) {
            if (isset($options['act'])) { $act = $options['act']; }
            if (isset($options['method'])) {
                $method = strtoupper($options['method']);
            }
        }
        if (!$err && $act == 'add') {
            if (!$options || !isset($options['device_list'])) {
                $err = 'no_device_list';
            }
            else {
                $device_list = $options['device_list'];
            }
        }
        if (!$err && $act == 'edit') {
            if (!isset($data['_id'])) { $err = 'no_reserve_id'; }
            else {
                $reserve_id = $data['_id'];
            }
        }
        if ( !$err ) {
            $params = array();
            $params['method'] = $method;
            $params['token']  = $app_token;
            $params['path']   = "/push/manage/{$datastore_id}/{$app_id}";
            if ($act == 'edit') {
                $params['path'] .= "/{$reserve_id}/";
            }
            $dt = new \DateTime($delivery_date);
            $dt = $dt->format(\DateTime::ISO8601);
            $dt = preg_replace('/:00((?:\+|\-){1}\d{4})$/', '$1', $dt);
            $params['data']['reserve_datetime'] = $dt;
            $dt = null;
            $params['data']['title'] = $data['push_reserve_title'];

            // Note that "$hash" will be substituted to "$params[data][apns/gcm]".
            $hash = array();
            $platform = ($data['os'] == 'ios') ? 'apns' : 'gcm';
            if ($act == 'add') {
                // doraemon
                $hash['devices'] = $device_list;
                // $hash['device_search_conditions'] = 'ALL';
            }
            if ($platform == 'apns') {
                $hash['alert'] = array('body' => $data['message']);
                if (isset($data['sound']) && $data['sound'] != '') {
                    $hash['sound'] = $data['sound'];
                }
                if (isset($data['badge']) && $data['badge'] != '') {
                    $hash['badge'] = (int) $data['badge'];
                }
                if (isset($data['rich_push']) && $data['rich_push'] != '') {
                    $hash['custom_data'] = array();
                    $hash['custom_data']['_openUrl'] = $data['rich_push'];
                }
            }
            if ($platform == 'gcm') {
                $hash['delay_while_idle'] = false;
                $hash['data']['title']    = $data['title'];
                $hash['data']['message']  = $data['message'];
                if (isset($data['rich_push']) && $data['rich_push'] != '') {
                    $hash['data']['_openUrl'] = $data['rich_push'];
                }
            }
            $params['data'][$platform] = $hash;
            $hash     = null;
            $platform = null;
            // error_log('[AppiariesAdminPushController]   params[data]: ' . json_encode($params['data']));

            $response = array();
            $res = null;
            try {
                $res = $app['eccube.plugin.appiaries.service']
                    ->request_appiaries($params, $response, $err);
            }
            catch(\Exception $e) {
                error_log('[AppiariesAdminPushController]   ' . $e->getMessage());
                $err = 'api_server_error';
            }
            if (!$err) {
                $status = $response ? $response['status_code'] : null;
                // error_log('[AppiariesAdminPushController]   status: ' . $status);

                if ($status == '200') { } // No problem
                else if ($status == '201') { } // No duplicate device IDs.
                elseif ($status == '202') { } // Duplicate device IDs were automatically excluded.
                else if ($status == '400') { $err = 'invalid_request'; }
                else if ($status == '401') { $err = 'no_token_or_auth_failed'; }
                else if ($status == '403') { $err = 'not_authorized'; }
                else if ($status == '412') { $err = 'market_not_set'; }
                else if ($status == '415') { $err = 'unsupported_media_type'; }
                else if ($status == '422') {
                    // For both "POST" and "PUT":
                    //  - "apns" and "gcm" are both set.
                    //  - "reserve_datetime" being the past.
                    //  - "reserve_datetime" being more than 1 month ahead.
                    //
                    // Specifically for "POST" (newly added) only:
                    //  - Exceeds max push delivery (or invalid params).
                    //
                    // Specifically for "PUT" (edit) only:
                    //  - Either "devices" or "device_search_conditions" is specified.
                    //  - Platform differs from the one when registered.
                    //  - Already delivered.
                    if (isset($res->_error) && count($res->_error)) {
                        if ($res->_error[0] == '0010101') { $err = 'exceed_max_push'; }
                        if ($res->_error[0] == '0120104') { $err = 'invalid_delivery_date'; }
                        error_log('[AppiariesAdminPushController]   _error[0]: ' . $res->_error[0]);
                    }
                    if (!$err) {
                        $err = 'no_good_params';
                    }
                }
                else if ($status == '500') { $err = 'server_error'; }
                else {
                    $err = 'unknown';
                }
                if ($err) {
                    error_log('[AppiariesAdminPushController]   ' . $err);
                    // For errors other than listed, just let it be "api_server_error".
                    $tmp = array(
                        'market_not_set',
                        'no_token_or_auth_failed',
                        'exceed_max_push',
                        'invalid_delivery_date',
                        'no_good_params',
                        'already_delivered',
                    );
                    if (!preg_match('/^(?:'.implode('|', $tmp).')$/', $err)) {
                        $err = 'api_server_error';
                    }
                }
//              if ($res) {
//                  error_log('[AppiariesAdminPushController]   res: ' . json_encode($res));
//                  error_log('[AppiariesAdminPushController]   response: ' . json_encode($response));
//              }
            }
        }
        return $err ? false : true;
    }
    // END OF: request_push_reservation_aux()


    /**
     * Delete push delivery reservation for the specified reserve ID.
     * @private
     * @param object &$app
     * @param object &$data
     * @param object $options
     * @param object &$err
     * @return boolean
     */
    private function request_push_deletion(&$app, &$data, $options, &$err) {

        if (!isset($err)) { throw new Exception('Need placeholder for error.'); }

        $app_token     = '';
        $reserve_id    = '';

        if (!isset($app))                        { $err = 'no_app'; }
        else if (!isset($data))                  { $err = 'no_data'; }
        else if (gettype($data) != 'array')      { $err = 'must_be_array_for_data'; }
        else if (!isset($data['datastore_id']))  { $err = 'no_datastore_id'; }
        else if (!isset($data['app_id']))        { $err = 'no_app_id'; }
        else if (!isset($data['app_token']))     { $err = 'no_app_token'; }
        else if (!isset($data['_id']))           { $err = 'no_id'; }
        else {
            $datastore_id  = $data['datastore_id'];
            $app_id        = $data['app_id'];
            $app_token     = $data['app_token'];
            $reserve_id    = $data['_id'];
            if ($options) {
            }
        }
        if ( !$err ) {
            $params = array();
            $params['method'] = 'DELETE';
            $params['token']  = $app_token;
            $params['path']   = "/push/manage/{$datastore_id}/{$app_id}/{$reserve_id}";
            $response = array();
            $res = null;
            try {
                $res = $app['eccube.plugin.appiaries.service']
                    ->request_appiaries($params, $response, $err);
            }
            catch(\Exception $e) {
                error_log('[AppiariesAdminPushController]   ' . $e->getMessage());
                $err = 'api_server_error';
            }
            if (!$err) {
                $status = $response ? $response['status_code'] : null;

                if ($status == '204') { } // No problem
                else if ($status == '400') { $err = 'invalid_request'; }
                else if ($status == '401') { $err = 'no_token_or_auth_failed'; }
                else if ($status == '403') { $err = 'not_authorized'; }
                else if ($status == '404') { $err = 'no_such_reservation'; }
                else if ($status == '412') { $err = 'already_delivered_or_deleted'; }
                else if ($status == '500') { $err = 'server_error'; }
                else {
                    $err = 'unknown';
                }
                if ($err) {
                    error_log('[AppiariesAdminPushController]   ' . $err);
                    // For errors other than listed, just let it be "api_server_error".
                    $tmp = array(
                        'market_not_set',
                        'no_token_or_auth_failed',
                        'exceed_max_push',
                        'invalid_delivery_date',
                        'no_good_params',
                        'no_such_reservation',
                        'already_delivered_or_deleted',
                    );
                    if (!preg_match('/^(?:'.implode('|', $tmp).')$/', $err)) {
                        $err = 'api_server_error';
                    }
                }
//                if ($res) {
//                    error_log('[AppiariesAdminPushController]   res: ' . json_encode($res));
//                    error_log('[AppiariesAdminPushController]   response: ' . json_encode($response));
//                }
            }
        }
        return $err ? false : true;
    }
    // END OF: request_push_deletion()


    /**
     * Since the retrieved push delivery reservation data is not very friendly to humans,
     * we will make the data more friendly to humans.
     * @private
     * @param object &$data
     * @param object &$err
     * @return boolean
     */
    private function make_data_more_human_friendly(&$data, &$err) {

        if (!isset($data->_id)) { $err = 'no_id'; }
        else {
            $checktime = strtotime('now');
            try {
                if (isset($data->title)) {
                    $data->push_reserve_title = $data->title;
                }
                $t_start = (isset($data->send_start_date) && !preg_match('/^0001/', $data->send_start_date))
                    ? $this->iso8601_to_timestamp($data->send_start_date)
                    : null;
                $t_end = (isset($data->sample_date) && !preg_match('/^0001/', $data->sample_date))
                    ? $this->iso8601_to_timestamp($data->sample_date)
                    : null;
                $t_reserve               = $this->iso8601_to_timestamp($data->reserve_datetime);
                $t_reserve_msec          = $t_reserve . '000';
                $data->push_type         = 'new';
                $data->reserve           = date('Y年m月d日 H時i分', $t_reserve);
                $data->reserve_for_form  = date('Y-m-d H:i', $t_reserve);
                $data->start             = $t_start ? date('Y年m月d日 H時i分', $t_start) : null;
                $data->end               = $t_end ? date('Y年m月d日 H時i分', $t_end) : null;
                $data->reserve_timestamp = $t_reserve;
                $data->start_timestamp   = $t_start ? $t_start : null;
                $data->end_timestamp     = $t_end ? $t_end : null;
                $data->nochange          = ($t_reserve < $checktime) ? true : false;
                $data->push_id           = null;
                if ($data->type == 'gcm' && isset($data->gcm)) {
                    if (isset($data->gcm->data)) {
                        foreach ($data->gcm->data as $k => $v) {
                            if ($k == '_openUrl') { $data->_openUrl = $v; }
                            else if ($k == 'pushId') { $data->push_id = $v; }
                            else {
                                $data->custom[$k] = $v;
                            }
                        }
                    }
                }
                if ($data->type == 'apns' && isset($data->apns)) {
                    if (isset($data->apns->custom_data)) {
                        foreach ($data->apns->custom_data as $k => $v) {
                            if ($k == '_openUrl') { $data->_openUrl = $v; }
                            else if ($k == 'pushId') { $data->push_id = $v; }
                            else {
                                $data->custom[$k] = $v;
                            }
                        }
                    }
                }
                $data->is_success = (
                    (!isset($data->send_error) || $data->send_error == 0) &&
                    !isset($data->status_error_code)) ? true : false;

                if ($data->is_success !== true) {
                    $tmp = array();
                    $send_error = isset($data->send_error) ? $data->send_error : 0;
                    $send_error_content = ($send_error > 0 && isset($data->send_error_content)) ? $data->send_error_content : null;
                    $status_error_code  = isset($data->status_error_code) ? $data->status_error_code : null;
                    if ($send_error > 0) {
                        if ($send_error_content) {
                            foreach ($send_error_content as $code => $cnt) {
                                $tmp[] = (object) array('msg' => $code, 'cnt' => $cnt);
                            }
                            $send_error_content = null;
                        }
                    }
                    if ($status_error_code) {
                        $tmp[] = (object) array('msg' => "[code] {$status_error_code}");
                        $status_error_code = null;
                    }
                    if (!count($tmp)) {
                        $tmp[] = (object) array('msg' => 'unknown');
                    }
                    $data->errors = $tmp;
                    $tmp = null;
                }
            }
            catch (\Exception $e) {
                error_log('[AppiariesAdminPushController]   ' . $e->getMessage());
                $err = 'push_data_error';
            }
        }
        return $err ? false : true;
    }
    // END OF: make_data_more_human_friendly()


    /*
     * For ISO8601 datetime, suffice with ":00" if its SECONDS is missing.
     * @private
     */
    private function make_valid_iso8601($s) {
        return preg_replace('/(T\d{2}:\d{2})((?:\+|\-){1}\d{4})$/', '$1:00$2', $s);
    }

    /*
     * For ISO8601 datetime, (1) omit the seconds notation from the time, and (2) omit ":" from the timezone.
     * Ex) "2015-09-01T11:28:42+09:00" ---> "2015-09-01T11:28+0900"
     * @private
     */
    private function make_appiaries_iso8601($s) {
        return preg_replace('/(T\d{2}:\d{2}):\d{2}((?:\+|\-){1}\d{2}):(\d{2})$/', '$1$2$3', $s);
    }


    /*
     * Convert ISO8601 datetime to UNIX timestamp.
     * @private
     */
    private function iso8601_to_timestamp($dt) {
        if (!isset($dt)) { return; }
        $dt  = $this->make_valid_iso8601($dt);
        $obj = \DateTime::createFromFormat(\DateTime::ISO8601, $dt);
        $ts  = $obj ? $obj->getTimestamp() : null;
        return $ts;
    }

    /*
     * Convert UNIX timestamp into ISO8601 datetime format.
     * @private
     */
    private function timestamp_to_iso8601($t) {
        $dt = new \DateTime();
        $dt->setTimestamp($t);
        return $this->make_appiaries_iso8601($dt->format('c'));
    }


    /** @public */
    // public function up(Application $app, Request $request, $id) {}

    /** @public */
    // public function down(Application $app, Request $request, $id) {}

}
