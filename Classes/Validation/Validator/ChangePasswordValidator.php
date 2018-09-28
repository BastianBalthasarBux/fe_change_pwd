<?php
namespace Derhansen\FeChangePwd\Validation\Validator;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Derhansen\FeChangePwd\Domain\Model\Dto\ChangePassword;
use Derhansen\FeChangePwd\Service\LocalizationService;
use Derhansen\FeChangePwd\Service\SettingsService;

/**
 * Class RegistrationValidator
 */
class ChangePasswordValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator
{
    /**
     * Available password checks
     *
     * @var array
     */
    protected $checks = [
        'capitalCharCheck',
        'lowerCaseCharCheck',
        'digitCheck',
        'specialCharCheck',
    ];

    /**
     * @var SettingsService
     */
    protected $settingsService = null;

    /**
     * @var LocalizationService
     */
    protected $localizationService = null;

    /**
     * @param SettingsService $settingsService
     */
    public function injectSettingsService(\Derhansen\FeChangePwd\Service\SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * @param LocalizationService $localizationService
     */
    public function injectLocalizationService(
        \Derhansen\FeChangePwd\Service\LocalizationService $localizationService
    ) {
        $this->localizationService = $localizationService;
    }

    /**
     * Validates the password of the given ChangePassword object against the configured password complexity
     *
     * @param ChangePassword $value
     *
     * @return bool
     */
    protected function isValid($value)
    {
        $result = true;
        $settings = $this->settingsService->getSettings();

        // Early return if no passwords are given
        if ($value->getPassword1() === '' || $value->getPassword2() === '') {
            $this->addError(
                $this->localizationService->translate('passwordFieldsEmptyOrNotBothFilledOut'),
                1537701950
            );

            return false;
        }

        if ($value->getPassword1() !== $value->getPassword2()) {
            $this->addError(
                $this->localizationService->translate('passwordsDoNotMatch'),
                1537701950
            );
            // Early return, no other checks need to be done if passwords do not match
            return false;
        }

        if (isset($settings['passwordComplexity']['minLength'])) {
            $this->evaluateMinLengthCheck($value, (int)$settings['passwordComplexity']['minLength']);
        }

        foreach ($this->checks as $check) {
            if (isset($settings['passwordComplexity'][$check]) &&
                (bool)$settings['passwordComplexity'][$check]
            ) {
                $this->evaluatePasswordCheck($value, $check);
            }
        }

        if ($this->result->hasErrors()) {
            $result = false;
        }

        return $result;
    }

    /**
     * Checks if the password complexity in regards to minimum password length in met
     *
     * @param ChangePassword $changePassword
     * @param int $minLength
     * @return void
     */
    protected function evaluateMinLengthCheck(ChangePassword $changePassword, int $minLength)
    {
        if (strlen($changePassword->getPassword1()) < $minLength) {
            $this->addError(
                $this->localizationService->translate('passwordComplexity.failure.minLength', [$minLength]),
                1537898028
            );
        };
    }

    /**
     * Evaluates the password complexity in regards to the given check
     *
     * @param ChangePassword $changePassword
     * @param string $check
     * @return void
     */
    protected function evaluatePasswordCheck(ChangePassword $changePassword, $check)
    {
        $patterns = [
            'capitalCharCheck' => '/[A-Z]/',
            'lowerCaseCharCheck' => '/[a-z]/',
            'digitCheck' => '/[0-9]/',
            'specialCharCheck' => '/[^0-9a-z]/i'
        ];

        if (isset($patterns[$check])) {
            if (!preg_match($patterns[$check], $changePassword->getPassword1()) > 0) {
                $this->addError(
                    $this->localizationService->translate('passwordComplexity.failure.' . $check),
                    1537898029
                );
            }
        }
    }
}
