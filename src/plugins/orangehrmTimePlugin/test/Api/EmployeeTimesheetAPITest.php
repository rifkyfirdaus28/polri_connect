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

namespace OrangeHRM\Tests\Time\Api;

use OrangeHRM\Config\Config;
use OrangeHRM\Framework\Services;
use OrangeHRM\Tests\Util\EndpointIntegrationTestCase;
use OrangeHRM\Tests\Util\Integration\TestCaseParams;
use OrangeHRM\Tests\Util\TestDataService;
use OrangeHRM\Time\Api\EmployeeTimesheetAPI;

/**
 * @group Time
 * @group APIv2
 */
class EmployeeTimesheetAPITest extends EndpointIntegrationTestCase
{
    /**
     * @dataProvider dataProviderForTestCreate
     */
    public function testCreate(TestCaseParams $testCaseParams): void
    {
        TestDataService::populate(Config::get(Config::TEST_DIR).'/phpunit/fixtures/WorkflowStateMachine.yaml', true);
        $this->populateFixtures('EmployeeTimesheetAPITest.yml', null, true);
        $this->createKernelWithMockServices([Services::AUTH_USER => $this->getMockAuthUser($testCaseParams)]);
        $this->registerServices($testCaseParams);
        $this->registerMockDateTimeHelper($testCaseParams);
        $api = $this->getApiEndpointMock(EmployeeTimesheetAPI::class, $testCaseParams);
        $this->assertValidTestCase($api, 'create', $testCaseParams);
    }

    public function dataProviderForTestCreate(): array
    {
        return $this->getTestCases('EmployeeTimesheetAPITestCases.yaml', 'Create');
    }

    /**
     * @dataProvider dataProviderForTestGetAll
     */
    public function testGetAll(TestCaseParams $testCaseParams): void
    {
        $this->populateFixtures('EmployeeTimesheetAPITest.yml', null, true);
        $this->createKernelWithMockServices([Services::AUTH_USER => $this->getMockAuthUser($testCaseParams)]);
        $this->registerServices($testCaseParams);
        $this->registerMockDateTimeHelper($testCaseParams);
        $api = $this->getApiEndpointMock(EmployeeTimesheetAPI::class, $testCaseParams);
        $this->assertValidTestCase($api, 'getAll', $testCaseParams);
    }

    public function dataProviderForTestGetAll(): array
    {
        return $this->getTestCases('EmployeeTimesheetAPITestCases.yaml', 'GetAll');
    }

    /**
     * @dataProvider dataProviderForTestUpdate
     */
    public function testUpdate(TestCaseParams $testCaseParams): void
    {
        TestDataService::populate(Config::get(Config::TEST_DIR) . '/phpunit/fixtures/WorkflowStateMachine.yaml', true);
        $this->populateFixtures('EmployeeTimesheetAPITest.yml', null, true);
        $this->createKernelWithMockServices([Services::AUTH_USER => $this->getMockAuthUser($testCaseParams)]);
        $this->registerServices($testCaseParams);
        $this->registerMockDateTimeHelper($testCaseParams);
        $api = $this->getApiEndpointMock(EmployeeTimesheetAPI::class, $testCaseParams);
        $this->assertValidTestCase($api, 'update', $testCaseParams);
    }

    public function dataProviderForTestUpdate(): array
    {
        return $this->getTestCases('EmployeeTimesheetAPITestCases.yaml', 'Update');
    }

    public function testGetOne(): void
    {
        $api = new EmployeeTimesheetAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->getOne();
    }

    public function testGetValidationRuleForGetOne(): void
    {
        $api = new EmployeeTimesheetAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->getValidationRuleForGetOne();
    }

    public function testDelete(): void
    {
        $api = new EmployeeTimesheetAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->delete();
    }

    public function testGetValidationRuleForDelete(): void
    {
        $api = new EmployeeTimesheetAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->getValidationRuleForDelete();
    }
}
