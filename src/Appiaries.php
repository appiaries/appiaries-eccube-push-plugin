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

use Eccube\Event\RenderEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\DomCrawler\Crawler;
use Eccube\Common\Constant;
use Doctrine\ORM\Id\SequenceGenerator;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;


class Appiaries
{

    /** @private */ private $app;


    /** @public */
    public function __construct($app) {
        $this->app = $app;
    }


    /** @public */
    public function on_login_control_before(Event $event) {
    }


    /** @public */
    public function on_login_control_after(Event $event) {
    }


    /** @todo */
    const DEBUG_DEVICE_ID = '';

    /**
     * DEBUG DEBUG DEBUG DEBUG DEBUG !!!!
     * This is a mockup for "Device ID" embedding
     * which is done by iOS/Android apps.
     * @todo
     */
    public function on_login_render_before(FilterResponseEvent $event) {
        /*
        $app      = & $this->app;
        $req      = $event->getRequest();
        $response = $event->getResponse();
        $content  = $response->getContent();
        $dom      = new Crawler($content);
        $html     = $dom->html();

        // Embed a fake Device ID inside the HTML.
        $str  = $dom->filter('#login_mypage');
        $str  = $str->html();
        $str2 = $str . "\n\t"
            . '<input type="hidden" name="login_device_id" value="' . self::DEBUG_DEVICE_ID . '" />' . "\n\t"
            . '<input type="hidden" name="login_os" value="android" />';
        $html = str_replace($str, $str2, $html);

        $response->setContent($html);
        $event->setResponse($response);
        */
    }


    /** @public */
    public function on_login_render_after(FilterResponseEvent $event) {
    }


    /** @public */
    public function on_app_before(Event $event, $event_name, EventDispatcher $dispatcher) {
    }


    /** @public */
    public function on_app_after(Event $event, $event_name, EventDispatcher $dispatcher) {
    }

}
