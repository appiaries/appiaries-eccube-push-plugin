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
namespace Plugin\Appiaries\Form;

use Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\EntityRepository;

class AppiariesSettings extends AbstractType
{
    private $app;

    public function __construct($app) {
        $this->app = $app;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $config = $this->app['config'];
        $builder
            ->add('datastore_id', 'text', array(
                'label' => 'データストア ID',
                'required' => false
            ))
            ->add('app_id', 'text', array(
                'label' => 'アプリ ID',
                'required' => false
            ))
            ->add('app_token', 'text', array(
                'label' => 'アプリトークン',
                'required' => false
            ))
            ->addEventSubscriber(new \Eccube\Event\FormEventSubscriber());
    }

    public function getName() {
        return 'appiaries_settings';
    }
}
