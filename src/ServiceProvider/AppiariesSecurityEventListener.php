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
namespace Plugin\Appiaries\ServiceProvider;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AppiariesSecurityEventListener
{

    /** @public */ public $app;
    /** @public */ public $em;


    /** @public */
    public function __construct(\Doctrine\ORM\EntityManager $em, \Eccube\Application & $app) {
        $this->app = $app;
        $this->em  = $em;
    }


    /** @public */
    public function on_interactive_login_appiaries(InteractiveLoginEvent $event) {
        $app       = & $this->app;
        $em        = & $this->em;
        $token     = $event->getAuthenticationToken();
        $user      = $token->getUser();
        $request   = $event->getRequest();
        $device_id = $request->get('login_device_id');
        $os        = $request->get('login_os');
        if (!isset($os)) { $os = 0; }
        else {
            $os = $os == 'ios' ? 1 : 2;
        }

        $key_list = array(
            'id','name','kana','company','zip','address',
            'email','tel','fax','birth',
            'purchase_first','purchase_last','purchase_count','purchase_total',
            'note','created','updated','del'
        );

        if (isset($device_id) && $user instanceof \Eccube\Entity\Customer) {
            $device = new \Plugin\Appiaries\Entity\AppiariesDevices();
            try {
                $tmp = array();
                $tmp['id'] = $user->getId();
                $name = array();
                $name[] = $user->getName01();
                $name[] = $user->getName02();
                if (count($name)) {
                    $tmp['name'] = implode(' ', $name);
                }
                $kana = array();
                $kana[] = $user->getKana01();
                $kana[] = $user->getKana02();
                if (count($kana)) {
                    $tmp['kana'] = implode(' ', $kana);
                }
                $tmp['company'] = $user->getCompanyName();
                $zip = $user->getZip01();
                if (isset($zip)) {
                    $zip2 = $user->getZip02();
                    if (isset($zip2)) { $zip .= '-' . $zip2; }
                    $tmp['zip'] = $zip;
                }
                $address = $user->getAddr01();
                if (isset($address)) {
                    $address2 = $user->getAddr02();
                    if (isset($address2)) { $address .= '-' . $address2; }
                    $tmp['address'] = $address;
                }
                $tel = $user->getTel01();
                if (isset($tel)) {
                    $tel2 = $user->getTel02();
                    $tel3 = $user->getTel03();
                    if (isset($tel2)) { $tel .= '-' . $tel2; }
                    if (isset($tel3)) { $tel .= '-' . $tel3; }
                    $tmp['tel'] = $tel;
                }
                $fax = $user->getFax01();
                if (isset($fax)) {
                    $fax2 = $user->getFax02();
                    $fax3 = $user->getFax03();
                    if (isset($fax2)) { $fax .= '-' . $fax2; }
                    if (isset($fax3)) { $fax .= '-' . $fax3; }
                    $tmp['fax'] = $fax;
                }
                $tmp['birth']           = $user->getBirth();
                $tmp['purchase_first']  = $user->getFirstBuyDate();
                $tmp['purchase_last']   = $user->getLastBuyDate();
                $tmp['purchase_count']  = $user->getBuyTimes();
                $tmp['purchase_total']  = $user->getBuyTotal();
                $tmp['note']            = $user->getNote();
                $tmp['created']         = $user->getCreateDate();
                $tmp['updated']         = $user->getUpdateDate();
                $tmp['del']             = $user->getDelFlg() ? true : false;
                $data = array();
                foreach ($key_list as $key) {
                    if (isset($tmp[$key])) {
                        if (preg_match('/^(?:birth|purchase_first|purchase_last|created|updated)$/', $key)) {
                            $data[$key] = ($key == 'birth') ?
                                $tmp[$key]->format('Y-m-d') : $tmp[$key]->format('Y-m-d H:i:s');
                        }
                        else {
                            $data[$key] = $tmp[$key];
                        }
                    }
                }
                $device->setCustomer($user); // "customer_id" will be automatically set.
                $device->setOs($os);
                $device->setDeviceId($device_id);
                $device->setAttr(json_encode($data));
                $res = $app['eccube.plugin.appiaries.repository.devices']->save($device);
            }
            catch (\Exception $e) {
                error_log($e->getMessage());
            }
        }
    }

}

