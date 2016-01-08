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
namespace Plugin\Appiaries;

use Eccube\Plugin\AbstractPluginManager;

class PluginManager extends AbstractPluginManager
{

    private $appiaries_images = array(
        'arrow_h4.png',
        'arrow_h5.png',
        'arrow_point.png',
        'where_codes_are.png',
        'arrow_left.png',
        'logo.png',
        'logo_pcp.png',
        'switch.png',
        'market_ios.png',
        'market_android.png',
    );

    /** @public */
    public function install($config, $app) {
        $this->handle_appiaries_images($app);
        $this->migrationSchema($app, __DIR__ . '/Migration', $config['code']);
    }

    /** @public */
    public function uninstall($config, $app) {
        $this->handle_appiaries_images($app, true);
        $this->migrationSchema($app, __DIR__ . '/Migration', $config['code'], 0);
    }

    /** @public */
    public function enable($config, $app) {}

    /** @public */
    public function disable($config, $app) {}

    /** @public */
    public function update($config, $app) {}


    /** @private */
    private function handle_appiaries_images(&$app, $remove = false) {
        $dir = $app['config']['root_dir'] . '/html/plugin';
        if (!file_exists($dir)) { return; }
        $dir .= '/Appiaries';
        $sub_dir = "{$dir}/imgs";
        try {
            if ($remove !== true) {
                if (!file_exists($dir)) {
                    mkdir($dir, 0777);
                }
                if (!file_exists($sub_dir)) {
                    mkdir($sub_dir, 0777);
                }
            }
            foreach ($this->appiaries_images as $i => $filename) {
                $target = "{$sub_dir}/{$filename}";
                if ($remove === true) {
                    if (file_exists($target)) {
                        unlink($target);
                    }
                }
                else {
                    $src = $app['config']['plugin_realdir'] . '/Appiaries/imgs/' . $filename;
                    if (file_exists($src)) {
                        copy($src, $target);
                    }
                }
            }
            if ($remove === true) {
                @rmdir($sub_dir);
                @rmdir($dir);
            }
        }
        catch (\Exception $e) {
            error_log('[Appiaries.PluginManager]   ' . $e->getMessage());
            $app->addError("Failed to manage image files on: {$dir}", 'admin');
        }
        $sub_dir = null;
        $dir = null;
    }
    // END OF: handle_appiaries_images()

}
