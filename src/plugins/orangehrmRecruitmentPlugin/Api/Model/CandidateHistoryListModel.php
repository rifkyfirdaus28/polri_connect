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

namespace OrangeHRM\Recruitment\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Entity\CandidateHistory;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Recruitment\Traits\Service\CandidateServiceTrait;

class CandidateHistoryListModel implements Normalizable
{
    use ModelTrait {
        ModelTrait::toArray as entityToArray;
    }

    use CandidateServiceTrait;
    use DateTimeHelperTrait;

    public function __construct(CandidateHistory $candidateHistory)
    {
        $this->setEntity($candidateHistory);
        $this->setFilters([
            'id',
            'action',
            ['getDecorator', 'getCandidateHistoryAction'],
            'candidateVacancyName',
            ['getPerformedBy', 'getEmpNumber'],
            ['getPerformedBy', 'getLastName'],
            ['getPerformedBy', 'getFirstName'],
            ['getPerformedBy', 'getMiddleName'],
            ['getPerformedBy', 'getEmployeeTerminationRecord', 'getId'],
            ['getDecorator', 'getPerformedDate'],
            'note',
        ]);

        $this->setAttributeNames([
            'id',
            ['action', 'id'],
            ['action', 'label'],
            'vacancyName',
            ['performedBy', 'empNumber'],
            ['performedBy', 'lastName'],
            ['performedBy', 'firstName'],
            ['performedBy', 'middleName'],
            ['performedBy', 'terminationId'],
            'performedDate',
            'note'
        ]);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $result = $this->entityToArray();
        $interview = $this->getEntity()->getInterview();
        $candidateId = $this->getEntity()->getCandidate()->getId();
        $currentVacancyId = $this->getCandidateService()
            ->getCandidateDao()
            ->getCurrentVacancyIdByCandidateId($candidateId);
        $vacancy = $this->getEntity()->getVacancy();
        $result['editable'] = !is_null($vacancy) && $currentVacancyId == $vacancy->getId();

        if (!is_null($interview)) {
            $result['interview']['id'] = $interview->getId();
            $result['interview']['name'] = $interview->getInterviewName();
            $result['interview']['date'] = $this->getDateTimeHelper()
                ->formatDate(
                    $interview->getInterviewDate()
                );
            $result['interview']['time'] = $this->getDateTimeHelper()
                ->formatDateTimeToTimeString(
                    $interview->getInterviewTime()
                );
            $result['interview']['interviewers'] = array_map(
                function (Employee $employee) {
                    return [
                        'empNumber' => $employee->getEmpNumber(),
                        'firstName' => $employee->getFirstName(),
                        'middleName' => $employee->getMiddleName(),
                        'lastName' => $employee->getLastName(),
                        'terminationId' => is_null($employee->getEmployeeTerminationRecord()) ?
                            null :
                            $employee->getEmployeeTerminationRecord()->getId(),
                    ];
                },
                [...$interview->getInterviewers()]
            );
        } else {
            $result['interview'] = null;
        }
        return $result;
    }
}
