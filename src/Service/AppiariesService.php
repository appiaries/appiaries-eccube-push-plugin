<?php
/**
 * Appiaries Push Notifications EC-CUBE 3 Plugin v1.0.0
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
namespace Plugin\Appiaries\Service;

use Eccube\Application;
use Eccube\Common\Constant;

class AppiariesService
{

    /** @const */
    const API_URL = 'https://api-datastore.appiaries.com/v1';

    /** @public */ public $app;

    /** @public */
    public function __construct(Application $app) {
        $this->app = $app;
    }

    /**
     * @public
     * @param object $params
     * @param object &$response
     * @param string &$err
     * @return object
     */
    public function request_appiaries($params, &$response = array(), &$err = null) {
        $params['url'] = rtrim(self::API_URL, '/') . $params['path'];
        if (!isset($params['data'])) {
            $params['data'] = '';
        }
        $is_arr = false;
        if (is_array($params['data'])) {
            $params['data'] = ($params['data'] == null) ?
                '{}' : json_encode($params['data']);
            $is_arr = true;
        }
        if (preg_match('/^(?:POST|PUT)$/', $params['method'])) {
            $params['header']['Content-Type'] = $is_arr ?
                'application/json' : 'text/plain';
        }
        if (!isset($params['header']['X-Appiaries-Token']) && $params['token']) {
            $params['header']['X-Appiaries-Token'] = $params['token'];
        }
        return json_decode($this->make_request($params, $response, $err));
    }
    // END OF: request_appiaries()


    /**
     * @public
     * @param object $params
     * @param object &$response
     * @param string &$err
     * @return object
     */
    public function make_request($params, &$response, &$err) {
        $method  = $params['method'];
        $url     = $params['url'];
        $data    = $params['data'];
        $headers = array();
        $params['header']['Content-Length'] = strlen($data);
        foreach ($params['header'] as $k => $v) {
            $headers[] = "{$k}: {$v}";
        }
        $context = array(
            'http' => array(
                'method'          => $method,
                'header'          => implode("\r\n", $headers),
                'content'         => $data,
                'ignore_errors'   => true,
                'follow_location' => 0,
                'max_redirects'   => 0,
            )
        );
        $res = file_get_contents($url, false, stream_context_create($context));
        $response['header'] = $http_response_header;
        $response['header_array'] = array();
        foreach ($response['header'] as $k => $v) {
            $tmp = explode(':', $v, 2);
            if (isset($s[1])) {
                $response['header_array'][$tmp[0]] = $tmp[1];
            }
        }
        $response['body'] = $res;
        if (preg_match('/\d{3}/', $response['header'][0], $m)) {
            $response['status_code'] = $m[0];
        }
        $method  = null;
        $url     = null;
        $data    = null;
        $headers = null;
        $context = null;
        $params  = null;

        return $res;
    }
    // END OF: make_request()


    /**
     * Given *ALL* the data records in array, construct a pager tool
     * so that easily handle the data using pagination.
     * @public
     * @param array $data All the data records.
     * @param object $options
     * @param object &$err
     * @return void
     */
    public function easy_pager($data, $options, &$err) {
        $pager = new \stdClass;
        $per   = 25; // Number of items per page.
        $pg    = 1;
        if (!isset($data)) { $err = 'no_data'; }
        else if (gettype($data) != 'array') { $err = 'data_must_be_array'; }
        else if ($options) {
            if (isset($options['per'])) {
                $per = (int) $options['per'];
                if (gettype($per) != 'integer' || $per < 1) { $err = 'invalid_per'; }
            }
            if (isset($options['pg'])) {
                $pg = (int) $options['pg'];
                if (gettype($pg) != 'integer' || $pg < 1) { $err = 'invalid_pg'; }
            }
        }
        if (!$err) {
            $total = count($data);
            $pager->list = array();
            $pager->per  = $per;
            $pager->all_the_pages = array();
            $pager->total = $total;
            if ($total > 0) {
                $pages  = (int) ($total / $per);
                $remain = (int) $total - ($per * $pages);
                if ($remain > 0) {
                    $pages++;
                }
                if ($pages == 0) { $pages = 1; }
                for ($i=1; $i<=$pages; $i++) { $pager->all_the_pages[] = $i; }
                if ($pg > $pages) {
                    $pg = $pages;
                }
                $pager->current = $pg;

                if ($pg >= 1) {
                    if ($pg != 1) {
                        $pager->prev = $pg - 1;
                    }
                    if (($pg + 1) <= $pages) {
                        $pager->next = $pg + 1;
                    }
                }
                $i = 0;
                $__cnt = 1;
                $__pg  = 1;
                foreach ($data as $row) {
                    if ($__pg == $pg) {
                        $pager->list[] = $row;
                    }
                    if ($__cnt >= $per) {
                        $__cnt = 1;
                        $__pg++;
                    }
                    else {
                        $__cnt++;
                    }
                    $i++;
                }
            }
        }
        $data    = null;
        $options = null;

        return $pager;
    }
    // END OF: easy_pager()

}
