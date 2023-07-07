<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

use Composer\Autoload\ClassLoader;
use OrangeHRM\Config\Config;
use OrangeHRM\Core\Traits\EventDispatcherTrait;
use OrangeHRM\Framework\Console\Console;
use OrangeHRM\Framework\Console\ConsoleConfigurationInterface;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\PluginConfigurationInterface;
use OrangeHRM\FunctionalTesting\Command\CreateDatabaseSavepointCommand;
use OrangeHRM\FunctionalTesting\Command\DeleteDatabaseSavepointCommand;
use OrangeHRM\FunctionalTesting\Command\ResetDatabaseCommand;
use OrangeHRM\FunctionalTesting\Command\RestoreDatabaseToSavepointCommand;
use OrangeHRM\FunctionalTesting\Subscriber\FunctionalTestingPluginSubscriber;

class FunctionalTestingPluginConfiguration implements PluginConfigurationInterface, ConsoleConfigurationInterface
{
    use EventDispatcherTrait;

    /**
     * @inheritDoc
     */
    public function initialize(Request $request): void
    {
        $loader = new ClassLoader();
        $loader->addPsr4('OrangeHRM\\FunctionalTesting\\', [realpath(__DIR__ . '/..')]);
        $loader->register();

        $this->getEventDispatcher()->addSubscriber(new FunctionalTestingPluginSubscriber());
    }

    /**
     * @inheritDoc
     */
    public function registerCommands(Console $console): void
    {
        if (Config::PRODUCT_MODE !== Config::MODE_PROD) {
            $console->add(new CreateDatabaseSavepointCommand());
            $console->add(new RestoreDatabaseToSavepointCommand());
            $console->add(new DeleteDatabaseSavepointCommand());
            $console->add(new ResetDatabaseCommand());
        }
    }
}
