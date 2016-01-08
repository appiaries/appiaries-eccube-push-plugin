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
namespace Plugin\Appiaries\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class AppiariesDevicesRepository extends EntityRepository
{

    const DEBUG_OUT = false;

    /** @public */
    public function save(\Plugin\Appiaries\Entity\AppiariesDevices $device) {
        $customer    = $device->getCustomer();
        $customer_id = $customer->getId();
        $data        = $this->findOneBy(array('customer_id' => $customer_id));

        // For the first time, create "created".
        if (!$data) {
            $data = $device;
            $data->setCreated(new \DateTime());
        }
        // If already exists, use the data to update.
        else {
            $data->setCustomer($customer);
            $data->setDeviceId($device->getDeviceId());
            $data->setAttr($device->getAttr());
        }
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $data->setUpdated(new \DateTime());
            $data = $em->persist($data);
            $em->flush();
            $em->getConnection()->commit();
        }
        catch (\Exception $e) {
            // mosaikekkan
            error_log($e->getMessage());
            $em->getConnection()->rollback();
            return false;
        }
        return true;
    }


    /**
     * Given the search conditions, construct a query builder for searching
     * target devices for a new push delivery.
     * Optional:
     *    [act]
     *       "count" --- Return total count for the match.
     *       "get"   --- Return actual rows.
     * @public
     * @param object &$data Given search conditions for searching the target devices.
     * @param object &$options Having "give" option to return the executed result.
     * @return void|array
     */
    public function search(&$data, $options = array()) {

        // "act" can be either "count" or "get".
        $act = (count($options) && isset($options['act'])) ? $options['act'] : null;

        // mosaikekkan
        // error_log('[AppiariesDevicesRepository]  act: ' . $act);

        // Integrate EC-CUBE "Customer" table with "appiaries_device" table.
        $qb = $this->createQueryBuilder('d');
        $qb->leftJoin('d.customer', 'c');
        if ($act == 'count') {
            $qb->select('COUNT(d.id)');
        }
        else {
            $qb->select(
                'd.device_id as device_id',
                'c.id as customer_id',
                'c.name01 as name01',
                'c.name02 as name02',
                'c.tel01 as tel01',
                'c.tel02 as tel02',
                'c.tel03 as tel03',
                'c.email as email'
            );
        }

        if ($data) {

            // OS
            $os = $data['os'] == 'ios' ? 1 : 2;
            if (self::DEBUG_OUT) { error_log("[AppiariesDevicesRepository]   Searching \"os = {$os}\""); }
            $qb->where('d.os = :os')->setParameter('os', $os);

            // Sex
            if (isset($data['sex'])) {
                if (self::DEBUG_OUT) {
                    error_log('[AppiariesDevicesRepository]   Searching'
                              . ' "sex = ' . $data['sex'] . '"');
                }
                $qb->andWhere('c.Sex = :sex')->setParameter('sex', $data['sex']);
            }

            // Pref
            if (isset($data['pref'])) {
                if (self::DEBUG_OUT) {
                    error_log('[AppiariesDevicesRepository]   Searching'
                              . ' "pref = ' . $data['pref'] . '"');
                }
                $qb->andWhere('c.Pref = :pref')->setParameter('pref', $data['pref']);
            }

            // Age (min)
            if (isset($data['age_min'])) {
                list($min, $max) =
                    $this->get_birthday_range($data['age_min']);
                if (self::DEBUG_OUT) {
                    error_log('[AppiariesDevicesRepository]   Searching'
                              . ' "birth <= ' . $max . '" (age: "'
                              . $data['age_min'] . '")');
                }
                $qb->andWhere('c.birth <= :age_max')->setParameter('age_max', $max);
            }

            // Age (max)
            if (isset($data['age_max'])) {
                list($min, $max) =
                    $this->get_birthday_range($data['age_max']);
                if (self::DEBUG_OUT) {
                    error_log('[AppiariesDevicesRepository]   Searching'
                              . ' "birth >= ' . $min . '" (age: "'
                              . $data['age_max'] . '")');
                }
                $qb->andWhere('c.birth >= :age_min')->setParameter('age_min', $min);
            }

            // Jobs
            if (isset($data['jobs']) && count($data['jobs'])) {
                if (self::DEBUG_OUT) {
                    error_log('[AppiariesDevicesRepository]   Searching "job IN('
                              . implode(',', $data['jobs']) . ')');
                }
                $qb->andWhere('c.Job IN(:jobs)')
                    ->setParameter('jobs', array_values($data['jobs']));
            }

            // User Last Updated (min)
            if (isset($data['updated_min'])) {
                $min = (gettype($data['updated_min']) == 'string') ?
                    $data['updated_min'] : $data['updated_min']->format('Y-m-d H:i:s');
                if (self::DEBUG_OUT) { error_log('[AppiariesDevicesRepository]   Searching "c.update_date >= ' . $min . '" (min)'); }
                $qb->andWhere('c.update_date >= :updated_min')
                    ->setParameter('updated_min', $min);
            }

            // User Last Updated (max)
            if (isset($data['updated_max'])) {
                $max = (gettype($data['updated_max']) == 'string') ?
                    $data['updated_max'] : $data['updated_max']
                    ->modify('+1 days')->format('Y-m-d H:i:s');
                if (self::DEBUG_OUT) { error_log('[AppiariesDevicesRepository]   Searching "c.update_date < ' . $max . '" (max)'); }
                $qb->andWhere('c.update_date < :updated_max')
                    ->setParameter('updated_max', $max);
            }

            // User Created (min)
            if (isset($data['created_min'])) {
                $min = (gettype($data['created_min']) == 'string') ?
                    $data['created_min'] : $data['created_min']->format('Y-m-d H:i:s');
                if (self::DEBUG_OUT) { error_log('[AppiariesDevicesRepository]   Searching "c.create_date >= ' . $min . '" (min)'); }
                $qb->andWhere('c.create_date >= :created_min')
                    ->setParameter('created_min', $min);
            }

            // User Created (max)
            if (isset($data['created_max'])) {
                $max = (gettype($data['created_max']) == 'string') ?
                    $data['created_max'] : $data['created_max']
                    ->modify('+1 days')->format('Y-m-d H:i:s');
                if (self::DEBUG_OUT) { error_log('[AppiariesDevicesRepository]   Searching "c.created_date < ' . $max . '" (max)'); }
                $qb->andWhere('c.create_date < :created_max')
                    ->setParameter('created_max', $max);
            }

            // Product Name
            if (isset($data['product_name'])) {
                if (self::DEBUG_OUT) {
                    error_log('[AppiariesDevicesRepository]   Searching "det.product_name LIKE %'
                              . $data['product_name'] . '% OR det.product_code LIKE %"'
                              . $data['product_name'] . '%');
                }
                $qb->leftJoin('c.Orders', 'ord')
                    ->leftJoin('ord.OrderDetails', 'det')
                    ->andWhere('det.product_name LIKE :product_name OR det.product_code LIKE :product_name')
                    ->setParameter('product_name', ('%' . $data['product_name'] . '%'));
            }

            // Total Price for the Purchases (min)
            if (isset($data['purchase_total_min'])) {
                if (self::DEBUG_OUT) {
                    error_log('[AppiariesDevicesRepository]   Searching "c.buy_total >= '
                              . $data['purchase_total_min'] . '" (min)');
                }
                $qb->andWhere('c.buy_total >= :purchase_total_min')
                    ->setParameter('purchase_total_min', $data['purchase_total_min']);
            }

            // Total Price for the Purchases (max)
            if (isset($data['purchase_total_max'])) {
                if (self::DEBUG_OUT) {
                    error_log('[AppiariesDevicesRepository]   Searching "c.buy_total <= '
                              . $data['purchase_total_max'] . '" (max)');
                }
                $qb->andWhere('c.buy_total <= :purchase_total_max')
                    ->setParameter('purchase_total_max', $data['purchase_total_max']);
            }

            // Number of Purchases (min)
            if (isset($data['purchase_count_min'])) {
                if (self::DEBUG_OUT) {
                    error_log('[AppiariesDevicesRepository]   Searching "c.buy_times >= '
                              . $data['purchase_count_min'] . '" (min)');
                }
                $qb->andWhere('c.buy_times >= :purchase_count_min')
                    ->setParameter('purchase_count_min', $data['purchase_count_min']);
            }

            // Number of Purchases (max)
            if (isset($data['purchase_count_max'])) {
                if (self::DEBUG_OUT) {
                    error_log('[AppiariesDevicesRepository]   Searching "c.buy_times <= '
                              . $data['purchase_count_max'] . '" (max)');
                }
                $qb->andWhere('c.buy_times <= :purchase_count_max')
                    ->setParameter('purchase_count_max', $data['purchase_count_max']);
            }

            // Last Purchased (min)
            if (isset($data['purchase_last_min'])) {
                $min = (gettype($data['purchase_last_min']) == 'string') ?
                    $data['purchase_last_min'] : $data['purchase_last_min']->format('Y-m-d H:i:s');
                if (self::DEBUG_OUT) { error_log('[AppiariesDevicesRepository]   Searching "c.last_buy_date >= ' . $min . '" (min)'); }
                $qb->andWhere('c.last_buy_date >= :purchase_last_min')
                    ->setParameter('purchase_last_min', $min);
            }

            // Last Purchased (max)
            if (isset($data['purchase_last_max'])) {
                $max = (gettype($data['purchase_last_max']) == 'string') ?
                    $data['purchase_last_max'] : $data['purchase_last_max']
                    ->modify('+1 days')->format('Y-m-d H:i:s');
                if (self::DEBUG_OUT) { error_log('[AppiariesDevicesRepository]   Searching "c.last_buy_date < ' . $max . '" (max)'); }
                $qb->andWhere('c.last_buy_date < :purchase_last_max')
                    ->setParameter('purchase_last_max', $max);
            }
        }
        // END OF: if (data)

        if (self::DEBUG_OUT) { error_log('[AppiariesDevicesRepository]   ' . $qb->getQuery()->getSQL()); }

        return ($act == 'count') ? $qb->getQuery()->getSingleScalarResult() :
            ($act == 'get' ? $qb->getQuery()->getResult(Query::HYDRATE_ARRAY) :
             $qb);
    }


    /**
     * From the "age" given, calculates the range of birthday
     * which is valid for the "age".
     * @private
     * @param integer $age
     * @return array
     */
    private function get_birthday_range($age) {
        $min = mktime(
            0, 0, 0, date('m'),
            (date('d') + 1),
            (date('Y') - $age - 1)
        );
        $max = mktime(
            0, 0, 0, date('m'),
            date('d'),
            (date('Y') - $age)
        );
        return array(
            date('Y-m-d', $min),
            date('Y-m-d', $max)
        );
    }

}
