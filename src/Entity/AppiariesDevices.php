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
namespace Plugin\Appiaries\Entity;

use Eccube\Entity\Customer;

class AppiariesDevices extends \Eccube\Entity\AbstractEntity
{

    /** @private */ private $id;
    /** @private */ private $customer;
    /** @private */ private $customer_id;
    /** @private */ private $os;
    /** @private */ private $device_id;
    /** @private */ private $attr;
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
    /*
    public function setId($id) {
        $this->id = $id;
        return $this;
    }
    */

    /** @public */
    public function getId() { return $this->id; }


    // --------------------------------------------------
    // customer_id
    // --------------------------------------------------

    /** @public */
    public function setCustomerId($customer_id) {
        $this->customer_id = $customer_id;
        return $this;
    }

    /** @public */
    public function getCustomerId() { return $this->customer_id; }


    // --------------------------------------------------
    // customer
    // --------------------------------------------------

    /**
     * Bound to "Eccube::Entity::Customer". When it is set,
     * then "customer_id" is to be automatically set as well.
     * @public
     */
    public function setCustomer(Customer $customer) {
        $this->customer = $customer;
        return $this;
    }


    /** @public */
    public function getCustomer() {
        return $this->customer;
    }


    // --------------------------------------------------
    // os
    // --------------------------------------------------

    /** @public */
    public function setOs($os) {
        $this->os = $os;
        return $this;
    }

    /** @public */
    public function getOs() { return $this->os; }


    // --------------------------------------------------
    // device_id
    // --------------------------------------------------

    /** @public */
    public function setDeviceId($device_id) {
        $this->device_id = $device_id;
        return $this;
    }

    /** @public */
    public function getDeviceId() { return $this->device_id; }


    // --------------------------------------------------
    // attr
    // --------------------------------------------------

    /** @public */
    public function setAttr($attr) {
        $this->attr = $attr;
        return $this;
    }

    /** @public */
    public function getAttr() { return $this->attr; }


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

