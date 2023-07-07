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
use OrangeHRM\Entity\TerminationReason;
use OrangeHRM\Pim\Dao\TerminationReasonConfigurationDao;
use OrangeHRM\Pim\Dto\TerminationReasonConfigurationSearchFilterParams;

class TerminationReasonConfigurationService
{
    /**
     * @var TerminationReasonConfigurationDao|null
     */
    private ?TerminationReasonConfigurationDao $terminationReasonConfigurationDao = null;

    /**
     * @ignore
     */
    public function getTerminationReasonDao()
    {
        if (!($this->terminationReasonConfigurationDao instanceof TerminationReasonConfigurationDao)) {
            $this->terminationReasonConfigurationDao = new TerminationReasonConfigurationDao();
        }
        return $this->terminationReasonConfigurationDao;
    }

    /**
     * @ignore
     */
    public function setTerminationReasonDao($terminationReasonConfigurationDao)
    {
        $this->terminationReasonConfigurationDao = $terminationReasonConfigurationDao;
    }

    /**
     * @param TerminationReason $terminationReason
     * @return TerminationReason
     * @throws DaoException
     */
    public function saveTerminationReason(TerminationReason $terminationReason): TerminationReason
    {
        return $this->getTerminationReasonDao()->saveTerminationReason($terminationReason);
    }

    /**
     * @param int $id
     * @return TerminationReason|null
     * @throws DaoException
     */
    public function getTerminationReasonById(int $id): ?TerminationReason
    {
        return $this->getTerminationReasonDao()->getTerminationReasonById($id);
    }

    /**
     * @param string $name
     * @return TerminationReason|null
     * @throws DaoException
     */
    public function getTerminationReasonByName(string $name): ?TerminationReason
    {
        return $this->getTerminationReasonDao()->getTerminationReasonByName($name);
    }

    /**
     * @param TerminationReasonConfigurationSearchFilterParams $terminationReasonConfigurationSearchFilterParams
     * @return array
     * @throws DaoException
     */
    public function getTerminationReasonList(
        TerminationReasonConfigurationSearchFilterParams $terminationReasonConfigurationSearchFilterParams
    ): array {
        return $this->getTerminationReasonDao()->getTerminationReasonList($terminationReasonConfigurationSearchFilterParams);
    }

    /**
     * @param TerminationReasonConfigurationSearchFilterParams $terminationReasonConfigurationSearchFilterParams
     * @return int
     * @throws DaoException
     */
    public function getTerminationReasonCount(TerminationReasonConfigurationSearchFilterParams $terminationReasonConfigurationSearchFilterParams): int
    {
        return $this->getTerminationReasonDao()->getTerminationReasonCount($terminationReasonConfigurationSearchFilterParams);
    }

    /**
     * @param array $toDeleteIds
     * @return int
     * @throws DaoException
     */
    public function deleteTerminationReasons(array $toDeleteIds): int
    {
        return $this->getTerminationReasonDao()->deleteTerminationReasons($toDeleteIds);
    }

    /**
     * @param string $terminationReasonName
     * @return bool
     * @throws DaoException
     */
    public function isExistingTerminationReasonName(string $terminationReasonName): bool
    {
        return $this->getTerminationReasonDao()->isExistingTerminationReasonName($terminationReasonName);
    }

    /**
     * @return array
     * @throws DaoException
     */
    public function getReasonIdsInUse(): array
    {
        return $this->getTerminationReasonDao()->getReasonIdsInUse();
    }
}
