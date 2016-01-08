<?php
/**
 * Appiaries Push Notifications EC-Cube 3 Plugin v0.0.0 pre-alpha
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

// For our custom constraint closure function.
use Symfony\Component\Validator\ExecutionContext;

// For setting "validation_groups" per "mode".
use Symfony\Component\Form\FormInterface;

class AppiariesPushAdd extends AbstractType
{
	/** @private */ private $app;

	/** @public */
    public function __construct($app) {
        $this->app = $app;
    }

	/** @private */
	private function choices_for_sex() {
        $res = array();
		$app = & $this->app;
        $sex_list = $app['eccube.repository.master.sex']
			->createQueryBuilder('s')
			->getQuery()
			->getResult();
        foreach ($sex_list as $sex) {
            $res[$sex->getId()] = $sex->getName();
        }
        return $res;
    }

	/** @public */
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			// Change the validation group based on "mode".
			'validation_groups' => function(FormInterface $form) {
				$data = $form->getData();
				$group = 'search';
				if (isset($data['mode'])) {
					if (preg_match('/^(?:from_the_search|compose_message)$/', $data['mode'])) {
						$group = 'message';
					}
					else if ($data['mode'] == 'reserve_push_delivery') {
						$group = 'reserve';
					}
					else {
						$group = 'Default';
					}
				}
				return array($group);
			},
		));
    }

	/** @public */
    public function buildForm(FormBuilderInterface $builder, array $options) {

		$app = & $this->app;

        $config = $app['config'];

		$age_range_option = array(
			'min'        => 1,
			'max'        => 100,
			'minMessage' => '{{ limit }} 才以上を指定して下さい。',
			'maxMessage' => '{{ limit }} 才以下を指定して下さい。',
			'groups'     => 'search',
		);

		// Needed when using "createNamed" method for "PRE_SUBMIT".
		// $factory = $builder->getFormFactory();

		$builder

			// -------------------------------------------------
			// Shared
			// -------------------------------------------------

			->add('mode', 'hidden', array())

			// -------------------------------------------------
			// SEARCH
			// -------------------------------------------------

			->add('os', 'choice', array(
				'label'       => '対象OS',
				'required'    => true,
				'choices'     => array(
					'ios'     => 'iOS',
					'android' => 'Android',
				),
				'empty_value' => '選択して下さい',
				'constraints' => array(
                    new Assert\NotBlank()
				),
			))

			// Using definitions from "Eccube::Entity::Master::Pref".
			->add('pref', 'pref', array(
				'label'    => '都道府県',
				'required' => false,
				'empty_value' => '選択して下さい',
			))
			// Notice: it is NOT using "Eccube::Entity::Master::Sex", but
			// defining its own choices.
			->add('sex', 'choice', array(
				'label'       => '性別',
				'required'    => false,
				'choices'     => $this->choices_for_sex(),
				'empty_value' => '選択して下さい',
			))
			->add('age_min', 'integer', array(
				'label'       => '年齢(下限)',
				'required'    => false,
				'constraints' => array(
					new Assert\Range($age_range_option)
				),
			))
			->add('age_max', 'integer', array(
				'label'       => '年齢(上限)',
				'required'    => false,
				'constraints' => array(
					new Assert\Range($age_range_option)
				),
			))
			// Using definitions from "Eccube::Entity::Master::Job".
			->add('job', 'job', array(
				'label'       => '職業',
				'required'    => false,
				'multiple'    => true,
				'empty_value' => '選択して下さい',
			))
			->add('updated_min', 'date', array(
				'label'       => '更新日(下限)',
				'required'    => false,
				'input'       => 'datetime',
				'widget'      => 'single_text',
				'format'      => 'yyyy-MM-dd',
			))
			->add('updated_max', 'date', array(
				'label'       => '更新日(上限)',
				'required'    => false,
				'input'       => 'datetime',
				'widget'      => 'single_text',
				'format'      => 'yyyy-MM-dd',
			))
			->add('created_min', 'date', array(
				'label'       => '登録日(下限)',
				'required'    => false,
				'input'       => 'datetime',
				'widget'      => 'single_text',
				'format'      => 'yyyy-MM-dd',
			))
			->add('created_max', 'date', array(
				'label'       => '登録日(上限)',
				'required'    => false,
				'input'       => 'datetime',
				'widget'      => 'single_text',
				'format'      => 'yyyy-MM-dd',
			))
			->add('product_name', 'text', array(
				'label'       => '購入商品名',
				'required'    => false,
				'constraints' => array(
					new Assert\Length(array('max' => 20, 'groups' => array('search')))
				),
			))
			->add('purchase_total_min', 'integer', array(
				'label'       => '購入金額(下限)',
				'required'    => false,
				'constraints' => array(
					new Assert\Length(array('max' => 9, 'groups' => array('search')))
				),
			))
			->add('purchase_total_max', 'integer', array(
				'label'       => '購入金額(上限)',
				'required'    => false,
				'constraints' => array(
					new Assert\Length(array('max' => 9, 'groups' => array('search')))
				),
			))
			->add('purchase_count_min', 'integer', array(
				'label'       => '購入回数(下限)',
				'required'    => false,
				'constraints' => array(
					new Assert\Length(array('max' => 3, 'groups' => array('search')))
				),
			))
			->add('purchase_count_max', 'integer', array(
				'label'       => '購入回数(上限)',
				'required'    => false,
				'constraints' => array(
					new Assert\Length(array('max' => 3, 'groups' => array('search')))
				),
			))
			->add('purchase_last_min', 'date', array(
				'label'       => '最終購入日(下限)',
				'required'    => false,
				'input'       => 'datetime',
				'widget'      => 'single_text',
				'format'      => 'yyyy-MM-dd',
			))
			->add('purchase_last_max', 'date', array(
				'label'       => '最終購入日(上限)',
				'required'    => false,
				'input'       => 'datetime',
				'widget'      => 'single_text',
				'format'      => 'yyyy-MM-dd',
			))

			// -------------------------------------------------
			// MESSAGE
			// -------------------------------------------------

			->add('title', 'text', array(
				'label'       => 'タイトル',
				'required'    => true,
				'constraints' => array(
                    new Assert\NotBlank(array('groups' => array('message'))),
					new Assert\Length(array('max' => 32, 'groups' => array('message')))
				),
			))

			->add('message', 'textarea', array(
				'label'       => 'メッセージ',
				'required'    => true,
				'constraints' => array(
                    new Assert\NotBlank(array('groups' => array('message'))),
					new Assert\Length(array('max' => 240, 'groups' => array('message')))
				),
			))

			// -------------------------------------------------
			// RESERVE
			// -------------------------------------------------

			// Default will be assigned by Javascript.
            ->add('delivery_date', 'datetime', array(
                'label'       => '配信日時',
                'required'    => true,
                'input'       => 'datetime',
                'widget'      => 'single_text',
                'format'      => 'yyyy-MM-dd HH:mm',
				'constraints' => array(
                   new Assert\NotBlank(array('groups' => array('reserve'))),
				),
            ))

			->addEventListener(
				FormEvents::POST_BIND,
				function ($event) {
					$form = $event->getForm();
					// Validate if "min" is greater than "max".
					foreach (array('age','updated','created',
								   'purchase_total','purchase_count','purchase_last')
							 as $key) {
						$label = $form[$key.'_max']->getConfig()->getOption('label');
						$min   = $form[$key.'_min']->getData();
						$max   = $form[$key.'_max']->getData();
						if ($label && $min && $max && $min > $max) {
							$form[$key.'_min']->
								addError(new FormError("「{$label}」よりも小さい値を指定して下さい。"));
							break;
						}
					}
					$form = null;
					$event = null;
				}
			)
			->addEventSubscriber(new \Eccube\Event\FormEventSubscriber());
	}
	// END OF: buildForm

    public function getName() {
        return 'appiaries_push_add';
    }
}

