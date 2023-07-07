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

namespace OrangeHRM\Core\Registration\Subscriber;

use OrangeHRM\Core\Registration\Processor\RegistrationEventProcessorFactory;
use OrangeHRM\Entity\RegistrationEventQueue;
use OrangeHRM\Framework\Event\AbstractEventSubscriber;
use OrangeHRM\Pim\Event\EmployeeEvents;
use OrangeHRM\Pim\Event\EmployeeSavedEvent;

class RegistrationEventPersistSubscriber extends AbstractEventSubscriber
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EmployeeEvents::EMPLOYEE_SAVED => 'onEmployeeSaved',
        ];
    }

    public function onEmployeeSaved(EmployeeSavedEvent $event): void
    {
        $registrationEventProcessorFactory = new RegistrationEventProcessorFactory();
        $registrationEventProcessor = $registrationEventProcessorFactory->getRegistrationEventProcessor(
            RegistrationEventQueue::ACTIVE_EMPLOYEE_COUNT
        );
        $registrationEventProcessor->saveRegistrationEvent();
        $registrationEventProcessor = $registrationEventProcessorFactory->getRegistrationEventProcessor(
            RegistrationEventQueue::INACTIVE_EMPLOYEE_COUNT
        );
        $registrationEventProcessor->saveRegistrationEvent();
    }
}
