<?php
namespace BlockPlus;

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use Generic\AbstractModule;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;

/**
 * BlockPlus
 *
 * @copyright Daniel Berthereau, 2018-2020
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    protected function preInstall()
    {
        $js = __DIR__ . '/asset/vendor/ThumbnailGridExpandingPreview/js/grid.js';
        if (!file_exists($js)) {
            $services = $this->getServiceLocator();
            $t = $services->get('MvcTranslator');
            throw new ModuleCannotInstallException(
                sprintf(
                    $t->translate('The library "%s" should be installed.'), // @translate
                    'javascript'
                ) . ' '
                . $t->translate('See module’s installation documentation.')); // @translate
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_elements',
            [$this, 'handleSiteSettings']
            );
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_input_filters',
            [$this, 'handleSiteSettingsFilters']
            );
    }

    public function handleSiteSettings(Event $event)
    {
        parent::handleSiteSettings($event);

        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings\Site');

        $fieldset = $event
            ->getTarget()
            ->get('blockplus');

        $pageTypes = $settings->get('blockplus_page_types') ?: [];
        $value = '';
        foreach ($pageTypes as $name => $label) {
            $value .= $name . ' = ' . $label . "\n";
        }
        $fieldset
            ->get('blockplus_page_types')
            ->setValue($value);
    }

    public function handleSiteSettingsFilters(Event $event)
    {
        $inputFilter = $event->getParam('inputFilter');
        $inputFilter->get('blockplus')
            ->add([
                'name' => 'blockplus_page_types',
                'required' => false,
                'filters' => [
                    [
                        'name' => \Zend\Filter\Callback::class,
                        'options' => [
                            'callback' => [$this, 'stringToKeyValues'],
                        ],
                    ],
                ],
            ])
        ;
    }

    public function stringToKeyValues($string)
    {
        $result = [];
        $list = $this->stringToList($string);
        foreach ($list as $keyValue) {
            list($key, $value) = array_map('trim', explode('=', $keyValue, 2));
            if ($key !== '') {
                $result[$key] = strlen($value) ? $value : $key;
            }
        }
        return $result;
    }
}
