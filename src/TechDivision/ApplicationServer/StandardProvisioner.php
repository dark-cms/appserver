<?php

/**
 * TechDivision\ApplicationServer\StandardProvisioner
 *
 * PHP version 5
 *
 * @category  Appserver
 * @package   TechDivision_ApplicationServer
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */

namespace TechDivision\ApplicationServer;

use TechDivision\ApplicationServer\Api\Node\ProvisionNode;

/**
 * Standard provisioning functionality.
 *
 * @category  Appserver
 * @package   TechDivision_ApplicationServer
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class StandardProvisioner extends AbstractProvisioner
{

    /**
     * Path the to appservers PHP executable on UNIX systems.
     *
     * @var string
     */
    const PHP_EXECUTABLE_UNIX = '/bin/php';

    /**
     * Path the to appservers PHP executable on Windows systems.
     *
     * @var string
     */
    const PHP_EXECUTABLE_WIN = '/php/php.exe';

    /**
     * Provisions all web applications.
     *
     * @return void
     */
    public function provision()
    {
        // check if deploy dir exists
        if (is_dir($this->getWebappsDir())) {

            // load the service instance
            $service = $this->getService();

            // Iterate through all provisioning files (provision.xml) and attach them to the configuration
            foreach (new \DirectoryIterator($this->getWebappsDir()) as $webappPath) {

                // check if we found an application directory
                if ($webappPath->isDir() === false || $webappPath->isDot()) {
                    continue;
                }

                // prepare the iterator for parsing META-INF/WEB-INF directories
                $directory = new \RecursiveDirectoryIterator($webappPath->getPathname());
                $iterator = new \RecursiveIteratorIterator($directory);

                // we need to use another regex on Windows here
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $regex = sprintf('/^.*\\\(META-INF|WEB-INF)\\\provision.xml$/');
                } else {
                    $regex = sprintf('/^.*\/(META-INF|WEB-INF)\/provision.xml$/');
                }

                // Iterate through all provisioning files (provision.xml) and attach them to the configuration
                foreach (new \RegexIterator($iterator, $regex) as $provisionFile) {

                    // load the provisioning configuration
                    $provisionNode = new ProvisionNode();
                    $provisionNode->initFromFile($provisionFile->getPathname());

                    // try to load the datasource from the system configuration
                    $datasourceNode = $service->findByName(
                        $provisionNode->getDatasource()->getName()
                    );

                    // try to inject the datasource node if available
                    if ($datasourceNode != null) {
                        $provisionNode->injectDatasource($datasourceNode);
                    }

                    /* Reprovision the provision.xml (reinitialize).
                     *
                     * ATTENTION: The reprovisioning is extremely important, because
                     * this allows dynamic replacment of placeholders by using the
                     * XML file as a template that will reinterpreted with the PHP
                     * interpreter!
                     */
                    $provisionNode->reprovision($provisionFile->getPathname());

                    // execute the provisioning workflow
                    $this->executeProvision($provisionNode, $webappPath);
                }
            }
        }
    }

    /**
     * Executes the passed applications provisioning workflow.
     *
     * @param \TechDivision\ApplicationServer\Api\Node\ProvisionNode $provisionNode The file with the provisioning information
     * @param \SplFileInfo                                           $webappPath    The path to the webapp folder
     *
     * @return void
     */
    protected function executeProvision(ProvisionNode $provisionNode, \SplFileInfo $webappPath)
    {

        // load the steps from the configuration
        $stepNodes = $provisionNode->getInstallation()->getSteps();

        // execute all steps found in the configuration
        foreach ($stepNodes as $stepNode) {

            try {

                // create a new reflection class of the step
                $reflectionClass = new \ReflectionClass($stepNode->getType());
                $step = $reflectionClass->newInstance();

                // try to inject the datasource node if available
                if ($datasourceNode = $provisionNode->getDatasource()) {
                    $step->injectDataSourceNode($datasourceNode);
                }

                // inject all other information
                $step->injectStepNode($stepNode);
                $step->injectService($this->getService());
                $step->injectWebappPath($webappPath->getPathname());
                $step->injectInitialContext($this->getInitialContext());
                $step->injectPhpExecutable($this->getAbsolutPathToPhpExecutable());

                // execute the step finally
                $step->start();
                $step->join();

            } catch (\Exception $e) {
                $this->getInitialContext()->getSystemLogger()->error($e->__toString());
            }
        }
    }

    /**
     * Returns the absolute path to the appservers PHP executable.
     *
     * @return string The absolute path to the appserver PHP executable
     */
    public function getAbsolutPathToPhpExecutable()
    {
        $executable = StandardProvisioner::PHP_EXECUTABLE_UNIX;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') { // we have a different executable on Windows systems
            $executable = StandardProvisioner::PHP_EXECUTABLE_WIN;
        }
        return $this->getService()->realpath($executable);
    }
}
