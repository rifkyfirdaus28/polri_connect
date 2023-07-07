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

namespace OrangeHRM\Pim\Dao;

use Exception;
use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Core\Exception\DaoException;
use OrangeHRM\Entity\EmployeeEvent;
use OrangeHRM\ORM\Paginator;
use OrangeHRM\Pim\Dto\EmployeeEventSearchFilterParams;

class EmployeeEventDao extends BaseDao
{
    /**
     * Save employee event
     *
     * @param EmployeeEvent $employeeEvent
     * @return EmployeeEvent
     * @throws DaoException
     */
    public function saveEmployeeEvent(EmployeeEvent $employeeEvent): EmployeeEvent
    {
        try {
            $this->persist($employeeEvent);
            return $employeeEvent;
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get employee event
     *
     * @param EmployeeEventSearchFilterParams $employeeEventSearchFilterParams
     * @return EmployeeEvent[]
     * @throws DaoException
     */
    public function getEmployeeEvents(EmployeeEventSearchFilterParams $employeeEventSearchFilterParams): array
    {
        try {
            $paginator = $this->getEmployeeEventPaginator($employeeEventSearchFilterParams);
            return $paginator->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param EmployeeEventSearchFilterParams $employeeEventSearchFilterParams
     * @return Paginator
     */
    private function getEmployeeEventPaginator(
        EmployeeEventSearchFilterParams $employeeEventSearchFilterParams
    ): Paginator {
        $q = $this->createQueryBuilder(EmployeeEvent::class, 'event');
        $this->setSortingAndPaginationParams($q, $employeeEventSearchFilterParams);

        if ($employeeEventSearchFilterParams->getDateRange()) {
            if ($employeeEventSearchFilterParams->getDateRange()->getFromDate()) {
                $q->andWhere('event.createdDate >= :fromDate')
                    ->setParameter('fromDate', $employeeEventSearchFilterParams->getDateRange()->getFromDate());
            }
            if ($employeeEventSearchFilterParams->getDateRange()->getToDate()) {
                $q->andWhere('event.createdDate <= :toDate')
                    ->setParameter('toDate', $employeeEventSearchFilterParams->getDateRange()->getToDate());
            }
        }
        if ($employeeEventSearchFilterParams->getEmpNumber()) {
            $q->andWhere("event.empNumber = :empNumber")
                ->setParameter('empNumber', $employeeEventSearchFilterParams->getEmpNumber());
        }
        if ($employeeEventSearchFilterParams->getEvent()) {
            $q->andWhere("event.event = :event")
                ->setParameter('event', $employeeEventSearchFilterParams->getEvent());
        }
        if ($employeeEventSearchFilterParams->getType()) {
            $q->andWhere("event.type = :type")
                ->setParameter('type', $employeeEventSearchFilterParams->getType());
        }

        return $this->getPaginator($q);
    }
}
