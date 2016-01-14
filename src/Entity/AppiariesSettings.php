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
namespace Plugin\Appiaries\Entity;

use Eccube\Entity\Customer;

class AppiariesSettings extends \Eccube\Entity\AbstractEntity
{

    /** @private */ private $id;
    /** @private */ private $datastore_id;
    /** @private */ private $app_id;
    /** @private */ private $app_token;
    /** @private */ private $created;
    /** @private */ private $updated;


    /** @public */
    public function __construct() {
        $this->setCreated(new \DateTime());
        if ($this->getUpdated() == null) {
            $this->setUpdated(new \DateTime());
        }
    }


    // --------------------------------------------------
    // id
    // --------------------------------------------------

    /** @public */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /** @public */
    public function getId() { return $this->id; }


    // --------------------------------------------------
    // datastore_id
    // --------------------------------------------------

    /** @public */
    public function setDatastoreId($id) {
        $this->datastore_id = $id;
        return $this;
    }

    /** @public */
    public function getDatastoreId() { return $this->datastore_id; }


    // --------------------------------------------------
    // app_id
    // --------------------------------------------------

    /** @public */
    public function setAppId($app_id) {
        $this->app_id = $app_id;
        return $this;
    }

    /** @public */
    public function getAppId() { return $this->app_id; }


    // --------------------------------------------------
    // app_token
    // --------------------------------------------------

    /** @public */
    public function setAppToken($app_token) {
        $this->app_token = $app_token;
        return $this;
    }

    /** @public */
    public function getAppToken() { return $this->app_token; }


    // --------------------------------------------------
    // created
    // --------------------------------------------------

    /** @public */
    public function setCreated($datetime) {
        $this->created = $datetime;
        return $this;
    }

    /** @public */
    public function getCreated() { return $this->created; }


    // --------------------------------------------------
    // updated
    // --------------------------------------------------

    /** @public */
    public function setUpdated($datetime) {
        $this->updated = $datetime;
        return $this;
    }

    /** @public */
    public function getUpdated() { return $this->updated; }

}

