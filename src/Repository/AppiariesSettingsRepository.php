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
namespace Plugin\Appiaries\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class AppiariesSettingsRepository extends EntityRepository
{

    /**
     * This method does not return "null" when no records are found like "find" does.
     * Instead, it returns a new instance of "Appiaries::Entity::AppiariesSettings".
     * @public
     */
    public function get() {
        // We only have one record with its primay key being "1".
        $query = $this->createQueryBuilder('s')->where('s.id = 1')->getQuery();
        try {
            return $query->getSingleResult();
        }
        catch (\Doctrine\ORM\NoResultException $e) {
            // Notice it does not return "null" for we never know
            // they may attempt to blindly use the accessors.
            return new \Plugin\Appiaries\Entity\AppiariesSettings;
        }
    }

    /** @public */
    public function save(\Plugin\Appiaries\Entity\AppiariesSettings $setting, &$err = '') {
        // For the first time, set the "created".
        if (!$this->find(1)) {
            $setting->setCreated(new \DateTime());
        }
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            // Remember this table only has one record with primary key being "1".
            $setting->setId(1);
            $setting->setUpdated(new \DateTime());
            // For we always have a duplicate error for we deal with the same record all the time,
            // we need to use "merge" instead of "persist" to safely update the existing record.
            $setting = $em->merge($setting);
            $em->flush();
            $em->getConnection()->commit();
        }
        catch (\Exception $e) {
            $em->getConnection()->rollback();
            $err = $e->getMessage();
            return false;
        }
        return true;
    }


    /** @public */
    public function get_settings(&$res = array(), &$err = '') {
        $res['datastore_id'] = null;
        $res['app_id']       = null;
        $res['app_token']    = null;
        try { $data = $this->get(); }
        catch (\Exception $e) {
            error_log($e->getMessage());
            $err = 'database_error';
        }
        if (!$err) {
            if (!$data) { $err = 'no_data'; }
            else {
                $res['datastore_id'] = $data->getDatastoreId();
                $res['app_id']       = $data->getAppId();
                $res['app_token']    = $data->getAppToken();
                if (!$res['datastore_id'])   { $err = 'no_datastore_id'; }
                else if (!$res['app_id'])    { $err = 'no_app_id'; }
                else if (!$res['app_token']) { $err = 'no_app_token'; }
            }
        }
        return $err ? false : true;
    }

}
