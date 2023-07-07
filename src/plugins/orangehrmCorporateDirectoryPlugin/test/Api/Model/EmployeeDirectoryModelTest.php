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

namespace OrangeHRM\Tests\CorporateDirectory\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\NormalizeException;
use OrangeHRM\Entity\Employee;
use OrangeHRM\CorporateDirectory\Api\Model\EmployeeDirectoryModel;
use OrangeHRM\Entity\JobTitle;
use OrangeHRM\Tests\Util\TestCase;

/**
 * @group Directory
 * @group Model
 */
class EmployeeDirectoryModelTest extends TestCase
{
    /**
     * @return void
     * @throws NormalizeException
     */
    public function testToArray()
    {
        $resultArray = [
            'empNumber' => 1,
            'lastName' => 'Last',
            'firstName' => 'First',
            'middleName' => 'Middle',
            'terminationId' => null,
            'jobTitle' => [
                'id' => 1,
                'title' => 'Software Engineer',
                'isDeleted' => null,
            ],
            'subunit' => [
                'id' => null,
                'name' => null,
            ],
            'location' => [
                'id' => null,
                'name' => null,
            ],
        ];

        $jobTitle = new JobTitle();
        $jobTitle->setId(1);
        $jobTitle->setJobTitleName('Software Engineer');
        $employee = new Employee();
        $employee->setEmpNumber(1);
        $employee->setFirstName('First');
        $employee->setMiddleName('Middle');
        $employee->setLastName('Last');
        $employee->setEmployeeTerminationRecord(null);
        $employee->setJobTitle($jobTitle);

        $employeeModel = new EmployeeDirectoryModel($employee);

        $this->assertEquals($resultArray, $employeeModel->toArray());
    }
}
