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
use InvalidArgumentException;
use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Core\Exception\DaoException;
use OrangeHRM\Entity\EmpEmergencyContact;
use OrangeHRM\ORM\ListSorter;
use OrangeHRM\ORM\Paginator;
use OrangeHRM\Pim\Dto\EmpEmergencyContactSearchFilterParams;

class EmpEmergencyContactDao extends BaseDao
{
    /**
     * @param EmpEmergencyContact $empEmergencyContact
     * @return EmpEmergencyContact
     */
    public function saveEmployeeEmergencyContact(EmpEmergencyContact $empEmergencyContact): EmpEmergencyContact
    {
        if ($empEmergencyContact->getSeqNo() === '0') {
            $q = $this->createQueryBuilder(EmpEmergencyContact::class, 'eec');
            $empNumber = $empEmergencyContact->getEmployee()->getEmpNumber();
            $q->andWhere('eec.employee = :empNumber')
                ->setParameter('empNumber', $empNumber);
            $q->select($q->expr()->max('eec.seqNo'));
            $maxSeqNo = $q->getQuery()->getSingleScalarResult();
            $seqNo = 1;
            if (!is_null($maxSeqNo)) {
                $seqNo += intval($maxSeqNo);
            }
            $empEmergencyContact->setSeqNo($seqNo);
        }
        $seqNo = intval($empEmergencyContact->getSeqNo());
        if (!($seqNo < 100 && $seqNo > 0)) {
            throw new InvalidArgumentException('Invalid `seqNo`');
        }

        $this->persist($empEmergencyContact);
        return $empEmergencyContact;
    }

    /**
     * Get Emergency contacts for given employee
     * @param int $seqNo
     * @param int $empNumber Employee Number
     * @return EmpEmergencyContact|null EmpEmergencyContact objects as array
     * @throws DaoException
     */
    public function getEmployeeEmergencyContact(int $empNumber, int $seqNo): ?EmpEmergencyContact
    {
        try {
            $empEmergencyContact = $this->getRepository(EmpEmergencyContact::class)->findOneBy([
                'employee' => $empNumber,
                'seqNo' => $seqNo,
            ]);
            if ($empEmergencyContact instanceof EmpEmergencyContact) {
                return $empEmergencyContact;
            }
            return null;
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Delete Emergency contacts
     * @param int $empNumber
     * @param array|null $entriesToDelete
     * @return int
     * @throws DaoException
     */
    public function deleteEmployeeEmergencyContacts(int $empNumber, array $entriesToDelete): int
    {
        try {
            $q = $this->createQueryBuilder(EmpEmergencyContact::class, 'eec');
            $q->delete()
                ->where('eec.employee = :empNumber')
                ->setParameter('empNumber', $empNumber);
            $q->andWhere($q->expr()->in('eec.seqNo', ':ids'))
                ->setParameter('ids', $entriesToDelete);
            return $q->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param int $empNumber
     * @return array
     * @throws DaoException
     */
    public function getEmployeeEmergencyContactList(int $empNumber): array
    {
        try {
            $q = $this->createQueryBuilder(EmpEmergencyContact::class, 'eec');
            $q->andWhere('eec.employee = :empNumber')
                ->setParameter('empNumber', $empNumber);
            $q->addOrderBy('eec.name', ListSorter::ASCENDING);

            return $q->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param EmpEmergencyContactSearchFilterParams $emergencyContactSearchFilterParams
     * @return array
     * @throws DaoException
     */
    public function searchEmployeeEmergencyContacts(
        EmpEmergencyContactSearchFilterParams $emergencyContactSearchFilterParams
    ): array {
        try {
            $paginator = $this->getSearchEmployeeEmergencyContactsPaginator($emergencyContactSearchFilterParams);
            return $paginator->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param EmpEmergencyContactSearchFilterParams $emergencyContactSearchFilterParams
     * @return Paginator
     */
    private function getSearchEmployeeEmergencyContactsPaginator(
        EmpEmergencyContactSearchFilterParams $emergencyContactSearchFilterParams
    ): Paginator {
        $q = $this->createQueryBuilder(EmpEmergencyContact::class, 'eec');
        $this->setSortingAndPaginationParams($q, $emergencyContactSearchFilterParams);

        if (!empty($emergencyContactSearchFilterParams->getEmpNumber())) {
            $q->andWhere('eec.employee = :empNumber');
            $q->setParameter('empNumber', $emergencyContactSearchFilterParams->getEmpNumber());
        }
        if (!empty($emergencyContactSearchFilterParams->getName())) {
            $q->andWhere('eec.name = :name');
            $q->setParameter('name', $emergencyContactSearchFilterParams->getName());
        }

        return $this->getPaginator($q);
    }

    /**
     * @param EmpEmergencyContactSearchFilterParams $emergencyContactSearchFilterParams
     * @return int
     * @throws DaoException
     */
    public function getSearchEmployeeEmergencyContactsCount(
        EmpEmergencyContactSearchFilterParams $emergencyContactSearchFilterParams
    ): int {
        try {
            $paginator = $this->getSearchEmployeeEmergencyContactsPaginator($emergencyContactSearchFilterParams);
            return $paginator->count();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
