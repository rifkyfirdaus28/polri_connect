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

namespace OrangeHRM\Pim\Service;

use OrangeHRM\Core\Exception\DaoException;
use OrangeHRM\Entity\EmployeeMembership;
use OrangeHRM\Pim\Dao\EmployeeMembershipDao;
use OrangeHRM\Pim\Dto\EmployeeMembershipSearchFilterParams;

class EmployeeMembershipService
{
    /**
     * @var EmployeeMembershipDao|null
     */
    private ?EmployeeMembershipDao $employeeMembershipDao = null;

    /**
     * @ignore
     */
    public function getEmployeeMembershipDao()
    {
        if (!($this->employeeMembershipDao instanceof EmployeeMembershipDao)) {
            $this->employeeMembershipDao = new EmployeeMembershipDao();
        }
        return $this->employeeMembershipDao;
    }

    /**
     * @param EmployeeMembershipDao|null $employeeMembershipDao
     */
    public function setEmployeeMembershipDao(?EmployeeMembershipDao $employeeMembershipDao): void
    {
        $this->employeeMembershipDao = $employeeMembershipDao;
    }

    /**
     * @param EmployeeMembership $employeeMembership
     * @return EmployeeMembership
     * @throws DaoException
     */
    public function saveEmployeeMembership(EmployeeMembership $employeeMembership): EmployeeMembership
    {
        return $this->getEmployeeMembershipDao()->saveEmployeeMembership($employeeMembership);
    }

    /**
     * @param int $empNumber
     * @param int $id
     * @return EmployeeMembership|null
     * @throws DaoException
     */
    public function getEmployeeMembershipById(int $empNumber, int $id): ?EmployeeMembership
    {
        return $this->getEmployeeMembershipDao()->getEmployeeMembershipById($empNumber, $id);
    }

    /**
     * @param EmployeeMembershipSearchFilterParams $employeeMembershipSearchFilterParams
     * @return array
     * @throws DaoException
     */
    public function searchEmployeeMembership(
        EmployeeMembershipSearchFilterParams $employeeMembershipSearchFilterParams
    ): array {
        return $this->getEmployeeMembershipDao()->searchEmployeeMembership($employeeMembershipSearchFilterParams);
    }

    /**
     * @param EmployeeMembershipSearchFilterParams $employeeMembershipSearchFilterParams
     * @return int
     * @throws DaoException
     */
    public function getSearchEmployeeMembershipsCount(
        EmployeeMembershipSearchFilterParams $employeeMembershipSearchFilterParams
    ): int {
        return $this->getEmployeeMembershipDao()->getSearchEmployeeMembershipsCount($employeeMembershipSearchFilterParams);
    }

    /**
     * @param int $empNumber
     * @param array $toDeleteIds
     * @return int
     * @throws DaoException
     */
    public function deleteEmployeeMemberships(int $empNumber, array $toDeleteIds): int
    {
        return $this->getEmployeeMembershipDao()->deleteEmployeeMemberships($empNumber, $toDeleteIds);
    }
}
