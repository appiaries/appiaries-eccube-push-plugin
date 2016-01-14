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

use Eccube\Application;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;


class AppiariesServiceProvider implements ServiceProviderInterface
{

    /** @public */
    public function register(BaseApplication $app) {

        // ----------------------------------------------------
        // Repositories
        // ----------------------------------------------------

        $app['eccube.plugin.appiaries.repository.devices'] = $app->share(
            function () use ($app) {
                return $app['orm.em']->
                getRepository('Plugin\Appiaries\Entity\AppiariesDevices');
            }
        );

        $app['eccube.plugin.appiaries.repository.settings'] = $app->share(
            function () use ($app) {
                return $app['orm.em']->
                getRepository('Plugin\Appiaries\Entity\AppiariesSettings');
            }
        );

        // ----------------------------------------------------
        // Event Listeners
        // ----------------------------------------------------


        // Hook for "security.interactive_login". Just like how
        // it is done in "Eccube::Application::initSecurity()".

        $app['eccube.event_listner.security'] = $app->share(
            function () use ($app) {
                return new AppiariesSecurityEventListener($app['orm.em'], $app);
            }
        );

        // Registering the custom listener.
        $app['dispatcher']->
            addListener(
                \Symfony\Component\Security\Http\SecurityEvents::INTERACTIVE_LOGIN,
                array(
                    $app['eccube.event_listner.security'],
                    // This is the method to handle query parameters posted
                    // from login form, namely, "login_device_id".
                    'on_interactive_login_appiaries'
                )
            );


        // ----------------------------------------------------
        // Services
        // ----------------------------------------------------

        $app['eccube.plugin.appiaries.service'] = $app->share(
            function () use ($app) {
                return new \Plugin\Appiaries\Service\AppiariesService($app);
            });


        // ----------------------------------------------------
        // Administrative Side Menus
        // ----------------------------------------------------

        $app['config'] = $app->share(
            $app->extend('config', function ($config){
                    $data = array(
                        'id' => 'appiaries',
                        'name' => "プッシュ配信管理",
                        'has_child' => true,
                        'icon' => 'cb-comment',
                        'child' => array(
                            array(
                                'id' => "push_add",
                                'name' => "配信予約",
                                'url' => "admin_appiaries_push_add_search",
                            ),
                            array(
                                'id' => "push_list",
                                'name' => "配信予約一覧",
                                'url' => "admin_appiaries_push_list",
                            )
                        ),
                    );
                    $list = $config['nav'];
                    foreach ($list as $key => $val) {
                        if ($val['id'] == 'setting') {
                            array_splice($list, $key, 0, array($data));
                            break;
                        }
                    }
                    $config['nav'] = $list;
                    return $config;
                }
            )
        );

        // ----------------------------------------------------
        // Translators
        // ----------------------------------------------------

        // Just like how it is done in "Eccube::InstallApplication"
        // except we get parameters out-of-the-box.
        $app['translator'] = $app->share(
            $app->extend(
                'translator',
                function ($translator, \Silex\Application $app) {
                    // Not loading YMLs.
                    $translator->addLoader('array', new ArrayLoader());
                    $translator->addResource('array', array(
                        'admin_appiaries_server_error' => 'サーバエラーが発生しました。',
                        'admin_appiaries_database_error' => 'データベース処理に失敗しました。',
                        'admin_appiaries_api_server_error' => 'API サーバ処理に失敗しました。',
                        'admin_appiaries_validation_failed' => '入力内容に不正な項目があります。',
                        'admin_appiaries_method_must_be_post' => 'POST リクエストである必要があります。',
                        'admin_appiaries_settings_save_success' => '設定が保存されました。',
                        'admin_appiaries_settings_save_failed' => '設定の保存に失敗しました。',
                        'admin_appiaries_add_search_failed' => '配信対象の検索に失敗しました。',
                        'admin_appiaries_add_reserve_failed' => '配信予約に失敗しました。',
                        'admin_appiaries_push_add_final_data_failed' => '最終データの作成に失敗しました。',
                        'admin_appiaries_no_datastore_id' => 'データストア ID が見つかりません。',
                        'admin_appiaries_no_app_id' => 'アプリ ID が見つかりません。',
                        'admin_appiaries_no_app_token' => 'アプリトークンが見つかりません。',
                        'admin_appiaries_market_not_set' => 'アピアリーズのマーケット設定がされていません。',
                        'admin_appiaries_no_token_or_auth_failed' => 'アプリトークンをご確認ください。',
                        'admin_appiaries_no_good_params' => 'API 通信時のパラメータが適切ではありません。',
                        'admin_appiaries_exceed_max_push' => '契約におけるプッシュ配信上限を超過しています。',
                        'admin_appiaries_invalid_delivery_date' =>
                        '過去または一ヶ月以上先を「配信日時」に指定することはできません。',
                        'admin_appiaries_no_device_list' => '対象デバイスが見つかりませんでした。',
                        'admin_appiaries_push_data_error' => '取得した配信データの処理に失敗しました。',
                        'admin_appiaries_no_id' => 'ID が指定されていません。',
                        'admin_appiaries_no_push_data' => '指定された ID の配信予約が見つかりませんでした。',
                        'admin_appiaries_already_delivered' => 'この配信はすでに送信されました。',
                        'admin_appiaries_push_edit_success' => '編集が反映されました。',
                        'admin_appiaries_push_edit_failed' => '編集に失敗しました。',
                        'admin_appiaries_push_delete_success' => '削除されました。',
                        'admin_appiaries_push_delete_failed' => '削除に失敗しました。',
                        'admin_appiaries_no_such_reservation' => '指定された予約配信は存在しません。',
                        'admin_appiaries_already_delivered_or_deleted' => 'すでに送信されたか削除されています。',
                    ), $app['locale']);
                    return $translator;
                }
            )
        );

        // ----------------------------------------------------
        // Forms
        // ----------------------------------------------------

        $app['form.types'] = $app->share(
            $app->extend('form.types', function ($types) use ($app) {
                    $types[] = new \Plugin\Appiaries\Form\AppiariesSettings($app);
                    $types[] = new \Plugin\Appiaries\Form\AppiariesPush($app);
                    return $types;
                }));


        // ----------------------------------------------------
        // Routes
        // ----------------------------------------------------

        $admin_route = $app['config']['admin_route'];

        $app->match(
            "/{$admin_route}/appiaries/config",
            '\\Plugin\\Appiaries\\Controller\\ConfigController::index'
        )->bind('plugin_Appiaries_config');

        $app->match(
            "/{$admin_route}/appiaries/push/list/{pg}",
            '\\Plugin\\Appiaries\\Controller\\AppiariesAdminPushController::list_all_the_push_reservations'
        )->value('pg', null)->assert('pg', '\d+|')
            ->bind('admin_appiaries_push_list');

        $app->match(
            "/{$admin_route}/appiaries/push/view/{id}",
            '\\Plugin\\Appiaries\\Controller\\AppiariesAdminPushController::view'
        )->value('id', null)->assert('id', '.+|')
            ->bind('admin_appiaries_push_view');

        $app->match(
            "/{$admin_route}/appiaries/push/edit/{id}",
            '\\Plugin\\Appiaries\\Controller\\AppiariesAdminPushController::edit'
        )->value('id', null)->assert('id', '.+|')
            ->bind('admin_appiaries_push_edit');

        $app->match(
            "/{$admin_route}/appiaries/push/delete/{id}",
            '\\Plugin\\Appiaries\\Controller\\AppiariesAdminPushController::delete'
        )->value('id', null)->assert('id', '.+|')
            ->bind('admin_appiaries_push_delete');

        $app->match(
            "/{$admin_route}/appiaries/push/add",
            '\\Plugin\\Appiaries\\Controller\\AppiariesAdminPushController::add_search'
        )->bind('admin_appiaries_push_add_search');

        $app->match(
            "/{$admin_route}/appiaries/push/add_reserve",
            '\\Plugin\\Appiaries\\Controller\\AppiariesAdminPushController::add_reserve'
        )->bind('admin_appiaries_push_add_reserve');


    } // END OF: register


    /** @public */
    public function boot(BaseApplication $app) {}


}


