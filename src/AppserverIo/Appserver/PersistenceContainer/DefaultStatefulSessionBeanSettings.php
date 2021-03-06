<?php

/**
 * AppserverIo\Appserver\PersistenceContainer\DefaultStatefulSessionBeanSettings
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/appserver
 * @link      http://www.appserver.io
 */

namespace AppserverIo\Appserver\PersistenceContainer;

use AppserverIo\Storage\GenericStackable;

/**
 * Interface for all session storage implementation.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/appserver
 * @link      http://www.appserver.io
 */
class DefaultStatefulSessionBeanSettings extends GenericStackable implements StatefulSessionBeanSettingsInterface
{

    /**
     * The default lifetime in seconds.
     *
     * @var string
     */
    const DEFAULT_LIFETIME = 1440;

    /**
     * The default probability the garbage collection will be invoked.
     *
     * @var string
     */
    const DEFAULT_GARBAGE_COLLECTION_PROBABILITY = 0.1;

    /**
     * Initialize the default session settings.
     */
    public function __construct()
    {
        // initialize the default values
        $this->setLifetime(DefaultStatefulSessionBeanSettings::DEFAULT_LIFETIME);
        $this->setGarbageCollectionProbability(DefaultStatefulSessionBeanSettings::DEFAULT_GARBAGE_COLLECTION_PROBABILITY);
    }

    /**
     * Sets the number of seconds for a stateful session bean lifetime.
     *
     * @param integer $lifetime The stateful session bean lifetime
     *
     * @return void
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = $lifetime;
    }

    /**
     * Returns the number of seconds for a stateful session bean lifetime.
     *
     * @return integer The stateful session bean lifetime
     */
    public function getLifetime()
    {
        return $this->lifetime;
    }

    /**
     * Sets the probability the garbage collector will be invoked on the session.
     *
     * @param float $garbageCollectionProbability The garbage collector probability
     *
     * @return void
     */
    public function setGarbageCollectionProbability($garbageCollectionProbability)
    {
        $this->garbageCollectionProbability = $garbageCollectionProbability;
    }

    /**
     * Returns the probability the garbage collector will be invoked on the session.
     *
     * @return float The garbage collector probability
     */
    public function getGarbageCollectionProbability()
    {
        return $this->garbageCollectionProbability;
    }

    /**
     * Merge the passed params with the default settings.
     *
     * @param array $params The associative array with the params to merge
     *
     * @return void
     */
    public function mergeWithParams(array $params)
    {
        // merge the passed properties with the default settings for the stateful session beans
        foreach (array_keys(get_object_vars($this)) as $propertyName) {
            if (array_key_exists($propertyName, $params)) {
                $this->$propertyName = $params[$propertyName];
            }
        }
    }
}
