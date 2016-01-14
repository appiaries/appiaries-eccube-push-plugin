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

class ConfigController
{

    /** @public */
    public function __construct() {}


    /**
     * @public
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Application $app, Request $request, $id = null) {

        $err    = '';
        $data   = array();
        $method = $request->getMethod();

        $form = $app['form.factory']->createBuilder('appiaries_settings', null)->getForm();

        $app['eccube.plugin.appiaries.repository.settings']->get_settings($data, $err);

        // No problem with "no_data", "no_database_id", "no_app_id", or "no_app_token".
        if ($err && preg_match('/^no_(?:data|datastore_id|app_id|app_token)$/', $err)) {
            $err = '';
        }

        $queries = array();
        if (!$err && $method == 'POST') {
            $form->handleRequest($request);
            $queries = $form->getData();
            if (!$form->isValid()) {
                error_log('[AppiariesAdminPushControrller]   ' . $form->getErrors());
                $err = 'validation_failed';
            }
            else {
                $settings = new \Plugin\Appiaries\Entity\AppiariesSettings();
                $settings->setDatastoreId($queries['datastore_id']);
                $settings->setAppId($queries['app_id']);
                $settings->setAppToken($queries['app_token']);
                if (!$app['eccube.plugin.appiaries.repository.settings']->save($settings, $err)) {
                    error_log('[ConfigController]   ' . $err);
                    $err = 'settings_save_failed';
                }
                else {
                    $app->addSuccess('admin_appiaries_settings_save_success', 'admin');
                    $data['datastore_id'] = $queries['datastore_id'];
                    $data['app_id']       = $queries['app_id'];
                    $data['app_token']    = $queries['app_token'];
                }
                $settings = null;
            }
        }

        if ($err) {
            error_log('[ConfigController]   ' . $err);
            $app->addError(('admin_appiaries_' . $err), 'admin');
        }

        $data['config_form'] = $form->createView();

        $err     = null;
        $request = null;
        $form    = null;
        $queries = null;

        return $app->render('Appiaries/templates/admin/config/index.twig', $data);
    }
    // END OF: index()

}
