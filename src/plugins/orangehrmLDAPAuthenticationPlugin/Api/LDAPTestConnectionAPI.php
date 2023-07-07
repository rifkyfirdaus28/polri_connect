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

namespace OrangeHRM\LDAP\Api;

use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;
use OrangeHRM\Core\Traits\ValidatorTrait;
use OrangeHRM\I18N\Traits\Service\I18NHelperTrait;
use OrangeHRM\LDAP\Api\Traits\LDAPCommonParamRuleCollection;
use OrangeHRM\LDAP\Dto\LDAPSetting;
use OrangeHRM\LDAP\Dto\LDAPUserLookupSetting;
use OrangeHRM\LDAP\Service\LDAPTestService;
use OrangeHRM\LDAP\Service\LDAPTestSyncService;
use OrangeHRM\LDAP\Traits\LDAPLoggerTrait;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Exception\ConnectionTimeoutException;
use Symfony\Component\Ldap\Exception\InvalidCredentialsException;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Ldap\Exception\NotBoundException;

use function count;

class LDAPTestConnectionAPI extends Endpoint implements CollectionEndpoint
{
    use ConfigServiceTrait;
    use ValidatorTrait;
    use LDAPCommonParamRuleCollection;
    use LDAPLoggerTrait;
    use I18NHelperTrait;

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $dataMapping = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            LDAPConfigAPI::PARAMETER_DATA_MAPPING
        );
        $this->validate($dataMapping, $this->getParamRuleCollectionForDataMapping());

        $userLookupSettings = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            LDAPConfigAPI::PARAMETER_USER_LOOKUP_SETTINGS
        );
        foreach ($userLookupSettings as $userLookupSetting) {
            $this->validate($userLookupSetting, $this->getParamRuleCollectionForUserLookupSettings());
        }

        $ldapSettings = new LDAPSetting(
            $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                LDAPConfigAPI::PARAMETER_HOSTNAME
            ),
            $this->getRequestParams()->getInt(
                RequestParams::PARAM_TYPE_BODY,
                LDAPConfigAPI::PARAMETER_PORT
            ),
            $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                LDAPConfigAPI::PARAMETER_LDAP_IMPLEMENTATION
            ),
            $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                LDAPConfigAPI::PARAMETER_ENCRYPTION
            ),
        );

        $this->setConfigAttributes($ldapSettings);
        $this->setDataMappingAttributes($ldapSettings, $dataMapping);
        $this->setUserLookupSettings($ldapSettings, $userLookupSettings);
        return new EndpointResourceResult(ArrayModel::class, $this->getNormalizeLDAPSettings($ldapSettings));
    }

    /**
     * @param LDAPSetting $ldapSetting
     * @return LDAPTestService
     */
    protected function getLDAPTestService(LDAPSetting $ldapSetting): LDAPTestService
    {
        return new LDAPTestService($ldapSetting);
    }

    /**
     * @param LDAPSetting $ldapSetting
     * @return LDAPTestSyncService
     */
    protected function getLDAPTestSyncService(LDAPSetting $ldapSetting): LDAPTestSyncService
    {
        return new LDAPTestSyncService($ldapSetting);
    }

    /**
     * @param LDAPSetting $ldapSetting
     * @return array[]
     */
    private function getNormalizeLDAPSettings(LDAPSetting $ldapSetting): array
    {
        $ldapTestService = $this->getLDAPTestService($ldapSetting);
        $authState = $ldapTestService->testAuthentication();
        $userLookupStatus = $authState['status'];

        $searchResults = $this->getI18NHelper()->transBySource('Failed');
        $users = $this->getI18NHelper()->transBySource('Failed');
        try {
            $ldapTestSyncService = $this->getLDAPTestSyncService($ldapSetting);
            $entryCollection = $ldapTestSyncService->fetchEntryCollections();
            $searchResults = $this->getI18NHelper()
                ->transBySource('{count} user(s) found', ['count' => $entryCollection->count()]);
            $users = $this->getI18NHelper()
                ->transBySource(
                    '{count} user(s) will be imported',
                    ['count' => count($ldapTestSyncService->fetchAllLDAPUsers()->getLDAPUsers())]
                );
        } catch (LdapException | NotBoundException | InvalidCredentialsException | ConnectionTimeoutException | ConnectionException $e) {
            $userLookupStatus = LDAPTestService::STATUS_FAIL;
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->error($e->getTraceAsString());
        }


        return [
            [
                'category' => $this->getI18NHelper()->transBySource('Login'),
                'checks' => [
                    [
                        'label' => $this->getI18NHelper()->transBySource('Authentication'),
                        'value' => $authState,
                    ],
                ]
            ],
            [
                'category' => $this->getI18NHelper()->transBySource('Lookup'),
                'checks' => [
                    [
                        'label' => $this->getI18NHelper()->transBySource('User lookup'),
                        'value' => [
                            'message' => $userLookupStatus == LDAPTestService::STATUS_FAIL
                                ? $this->getI18NHelper()->transBySource('Failed')
                                : $this->getI18NHelper()->transBySource('Ok'),
                            'status' => $userLookupStatus
                        ]
                    ],
                    [
                        'label' => $this->getI18NHelper()->transBySource('Search results'),
                        'value' => [
                            'message' => $searchResults,
                            'status' => $userLookupStatus
                        ]
                    ],
                    [
                        'label' => $this->getI18NHelper()->transBySource('Users'),
                        'value' => [
                            'message' => $users,
                            'status' => $userLookupStatus
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @param LDAPSetting $ldapSetting
     */
    private function setConfigAttributes(LDAPSetting $ldapSetting): void
    {
        $bindAnonymously = $this->getRequestParams()->getBoolean(
            RequestParams::PARAM_TYPE_BODY,
            LDAPConfigAPI::PARAMETER_BIND_ANONYMOUSLY
        );
        $ldapSetting->setBindAnonymously($bindAnonymously);
        if (!$bindAnonymously) {
            $ldapSetting->setBindUserDN(
                $this->getRequestParams()->getString(
                    RequestParams::PARAM_TYPE_BODY,
                    LDAPConfigAPI::PARAMETER_BIND_USER_DISTINGUISHED_NAME
                )
            );

            $password = $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                LDAPConfigAPI::PARAMETER_BIND_USER_PASSWORD
            );
            $ldapSettings = $this->getConfigService()->getLDAPSetting();
            if ($ldapSettings instanceof LDAPSetting && $password === null) {
                $ldapSetting->setBindUserPassword($ldapSettings->getBindUserPassword());
            } else {
                $ldapSetting->setBindUserPassword($password);
            }
        } else {
            $ldapSetting->setBindUserDN(null);
            $ldapSetting->setBindUserPassword(null);
        }
    }

    /**
     * @param LDAPSetting $ldapSetting
     * @param array $dataMapping
     */
    private function setDataMappingAttributes(LDAPSetting $ldapSetting, array $dataMapping): void
    {
        $ldapSetting->getDataMapping()->setAttributeNames($dataMapping);
    }

    /**
     * @param LDAPSetting $ldapSetting
     * @param array $userLookupSettings
     */
    private function setUserLookupSettings(LDAPSetting $ldapSetting, array $userLookupSettings): void
    {
        foreach ($userLookupSettings as $userLookupSetting) {
            $ldapSetting->addUserLookupSetting(LDAPUserLookupSetting::createFromArray($userLookupSetting));
        }
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        $paramRules = $this->getParamRuleCollection();
        $paramRules->removeParamValidation(LDAPConfigAPI::PARAMETER_ENABLED);
        $paramRules->removeParamValidation(LDAPConfigAPI::PARAMETER_SYNC_INTERVAL);
        return $paramRules;
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
