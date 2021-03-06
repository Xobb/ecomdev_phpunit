<?php
/**
 * PHP Unit test suite for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_PHPUnit
 * @copyright  Copyright (c) 2011 Ecommerce Developers (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Application model for phpunit tests
 *
 */
class EcomDev_PHPUnit_Model_App extends Mage_Core_Model_App
{
    // Run types constants,
    // Don't know why
    // they are not defined in core application
    const RUN_TYPE_STORE = 'store';
    const RUN_TYPE_WEBSITE = 'website';
    const RUN_TYPE_GROUP = 'group';

    // Admin store code
    const ADMIN_STORE_CODE = 'admin';

    /**
     * Old configuration model to be returned back
     * after unit tests are finished
     *
     * @var Mage_Core_Model_Config
     */
    protected static $_oldConfig = null;

    /**
     * Old application model to be returned back
     * after unit tests are finished
     *
     * @var Mage_Core_Model_App
     */
    protected static $_oldApplication = null;

    /**
     * Old event collection to be returned back
     * after the unit tests are finished
     *
     * @var Varien_Event_Collection
     */
    protected static $_oldEventCollection = null;

    /**
     * List of singletons in original application
     *
     * @var array
     */
    protected static $_oldRegistry = null;

    /**
     * Configuration model class name for unit tests
     *
     * @var string
     */
    protected static $_configClass = 'EcomDev_PHPUnit_Model_Config';

    /**
     * Configuration model class name for unit tests
     *
     * @var string
     */
    protected static $_eventCollectionClass = 'Varien_Event_Collection';

    /**
     * Configuration model class name for unit tests
     *
     * @var string
     */
    protected static $_cacheClass = 'EcomDev_PHPUnit_Model_Cache';

    /**
     * Enabled events flag
     *
     * @var boolean
     */
    protected $_eventsEnabled = true;

    /**
     * This method replaces application, event and config objects
     * in Mage to perform unit tests in separate Magento steam
     *
     */
    public static function applyTestScope()
    {
        // Save old environment variables
        self::$_oldApplication = EcomDev_Utils_Reflection::getRestrictedPropertyValue('Mage', '_app');
        self::$_oldConfig = EcomDev_Utils_Reflection::getRestrictedPropertyValue('Mage', '_config');
        self::$_oldEventCollection = EcomDev_Utils_Reflection::getRestrictedPropertyValue('Mage', '_events');
        self::$_oldEventCollection = EcomDev_Utils_Reflection::getRestrictedPropertyValue('Mage', '_registry');


        // Setting environment variables for unit tests
        EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_config', new self::$_configClass);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', new self);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_events', new self::$_eventCollectionClass);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_registry', array());

        // All unit tests will be runned in admin scope, to get rid of frontend restrictions
        Mage::app()->initTest();
    }

    /**
     * Initializes test scope for PHPUnit
     *
     * @return EcomDev_PHPUnit_Model_App
     */
    public function initTest()
    {
        $this->_config = Mage::getConfig();
        $this->_config->setOptions($options);
        $this->_initBaseConfig();
        $this->_initCache();

        // Set using cache
        // for things that shouldn't be reloaded each time
        EcomDev_Utils_Reflection::setRestrictedPropertyValue(
            $this->_cache,
            '_allowedCacheOptions',
            array(
                'eav' => 1,
                'layout' => 1,
                'translate' => 1
            )
        );

        // Clean cache before the whole suite is running
        $this->getCache()->clean();

        // Init modules runs install proccess for table structures,
        // It is required for setting up proper setup script
        $this->_initModules();
        $this->loadAreaPart(Mage_Core_Model_App_Area::AREA_GLOBAL, Mage_Core_Model_App_Area::PART_EVENTS);
        if ($this->_config->isLocalConfigLoaded()) {
            $this->_initCurrentStore(self::ADMIN_STORE_CODE, self::RUN_TYPE_STORE);
            $this->_initRequest();
            Mage_Core_Model_Resource_Setup::applyAllDataUpdates();
        }

        return $this;
    }

    /**
     * Overriden to fix issue with stores loading
     * (non-PHPdoc)
     * @see Mage_Core_Model_App::_initStores()
     */
    protected function _initStores()
    {
        $this->_store = null;
        parent::_initStores();
        return $this;
    }

    /**
     * Discard test scope for application, returns all the objects from live version
     *
     */
    public static function discardTestScope()
    {
        // Setting environment variables for unit tests
        EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', self::$_oldApplication);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_config', self::$_oldConfig);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_events', self::$_oldEventCollection);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_registry', self::$_oldRegistry);
    }

    /**
     * Disables events fire
     *
     * @return EcomDev_PHPUnit_Model_App
     */
    public function disableEvents()
    {
        $this->_eventsEnabled = false;
        return $this;
    }

    /**
     * Enable events fire
     *
     * @return EcomDev_PHPUnit_Model_App
     */
    public function enableEvents()
    {
        $this->_eventsEnabled = true;
        return $this;
    }

    /**
     * Overriden for disabling events
     * fire during fixutre loading
     *
     * (non-PHPdoc)
     * @see Mage_Core_Model_App::dispatchEvent()
     */
    public function dispatchEvent($eventName, $args)
    {
        if ($this->_eventsEnabled) {
            parent::dispatchEvent($eventName, $args);
        }

        return $this;
    }
}
