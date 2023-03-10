# Usage in your own extension
## Opt-In
### 1. Generate Opt-In in your controller
For a registration with opt-in simple use the example-code below in your controller.
Please ensure to always load FrontendUserRegistration via ObjectManager
```
/** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
$frontendUser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(FrontendUser::class);
$frontendUser->setEmail($email);

/** @var \Madj2k\FeRegister\Registration\FrontendUserRegistration $registration */
$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
$registration = $objectManager->get(FrontendUserRegistration::class);
$registration->setFrontendUser($frontendUser)
    ->setData($alert)
    ->setCategory('yourExtension')
    ->setRequest($request)
    ->startRegistration();
```

If you want to be able to update the data of the frontendUser after the successful opt-in
you can use the method **setFrontendUserUpdate**. This will update the frontendUser-object as soon as the user
accepts the opt-in. This way you can be sure that changes to the frontendUser-object only
happen if authorized.

### 2. Define MailService for Opt-In-Mail
No you need a MailService class with a defined action for Opt-Ins
```
/**
* Handles opt-in event
*
* @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
* @param \Madj2k\FeRegister\Domain\Model\OptIn $optIn
* @return void
* @throws \Madj2k\Postmaster\Exception
* @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
* @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
* @throws \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
* @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
*/
public function optIn (
    \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser,
    \Madj2k\FeRegister\Domain\Model\OptIn $optIn
): void  {

    // get settings
    $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
    $settingsDefault = $this->getSettings();

    if ($settings['view']['templateRootPaths']) {

        /** @var \Madj2k\Postmaster\Service\MailService $mailService */
        $mailService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MailService::class);

        // send new user an email with token
        $mailService->setTo($frontendUser, array(
            'marker' => array(
                'frontendUser' => $frontendUser,
                'optIn'        => $optIn,
                'pageUid'      => intval($GLOBALS['TSFE']->id),
                'loginPid'     => intval($settingsDefault['loginPid']),
            ),
        ));

        $mailService->getQueueMail()->setSubject(
            FrontendLocalizationUtility::translate(
                'mailService.optInAlertUser.subject',
                'your_extension',
                null,
                $frontendUser->getTxFeregisterLanguageKey()
            )
        );

        $mailService->getQueueMail()->addTemplatePaths($settings['view']['templateRootPaths']);
        $mailService->getQueueMail()->addPartialPaths($settings['view']['partialRootPaths']);

        $mailService->getQueueMail()->setPlaintextTemplate('Email/OptInAlertUser');
        $mailService->getQueueMail()->setHtmlTemplate('Email/OptInAlertUser');
        $mailService->send();
    }
}
```
### 3. Set Signal-Slot
Now we need a signal-slot that refers to the defined method for sending mails (ext_localconf.php)
```
/**
 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
 */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    Madj2k\FeRegister\Registration\AbstractRegistration::class,
    Madj2k\FeRegister\Registration\AbstractRegistration::SIGNAL_AFTER_CREATING_OPTIN  . 'YourExtension',
    Your\Extension\Service\MailService::class,
    'optInAlertUser'
);
```

### 4. Set Template for Opt-In-Mail
The opt-in-email may look like this:
```
<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
	xmlns:postmaster="http://typo3.org/ns/Madj2k/Postmaster/ViewHelpers"
	data-namespace-typo3-fluid="true">

	<f:layout name="Email/{mailType}" />

	<!-- PLAINTEXT -->
	<f:section name="Plaintext"><postmaster:email.plaintextLineBreaks>
	    <postmaster:email.translate key="templates_email_optInAlertUser.textOptInLinkLabel" languageKey="{frontendUser.txFeregisterLanguageKey}" extensionName="yourExtension"/>:\n
	    <postmaster:email.uri.action action="optIn" controller="Alert" extensionName="yourExtension" pluginName="yourExtension" absolute="true" pageUid="{pageUid}" additionalParams="{tx_yourextenion_plugin: {token: optIn.tokenYes, tokenUser: optIn.tokenUser}}" section="your-extension" />\n\n

        <postmaster:email.translate key="templates_email_optInAlertUser.textOptOutLinkLabel" languageKey="{frontendUser.txFeregisterLanguageKey}" extensionName="yourExtension"/>:\n
        <postmaster:email.uri.action action="optIn" controller="Alert" extensionName="yourExtension" pluginName="yourExtension" absolute="true" pageUid="{pageUid}" additionalParams="{tx_yourextenion_plugin: {token: optIn.tokenNo, tokenUser: optIn.tokenUser}}" section="your-extension" />
    </postmaster:email.plaintextLineBreaks></f:section>

	<!-- HTML -->
	<f:section name="Html">
		<a href="<postmaster:email.uri.action action='optIn' controller='Alert' extensionName='yourExtension' pluginName='yourExtension' absolute='true' pageUid='{pageUid}' additionalParams='{tx_yourextenion_plugin: {token: optIn.tokenYes, tokenUser: optIn.tokenUser}}' section='your-extension' />"><postmaster:email.translate key="templates_email_optInAlertUser.textOptInLinkLabel" languageKey="{frontendUser.txFeregisterLanguageKey}" extensionName="yourExtension"/></a>
		<a href="<postmaster:email.uri.action action='optIn' controller='Alert' extensionName='yourExtension' pluginName='yourExtension' absolute='true' pageUid='{pageUid}' additionalParams='{tx_yourextenion_plugin: {token: optIn.tokenNo, tokenUser: optIn.tokenUser}}' section='your-extension' />"><postmaster:email.translate key="templates_email_optInAlertUser.textOptOutLinkLabel" languageKey="{frontendUser.txFeregisterLanguageKey}" extensionName="yourExtension"/></a>
	</f:section>

</html>
```
### 5. Check Opt-In
To check the opt-in you can use the following example-code in your contoller:
```
public function optInAction(string $tokenUser, string $token): void
{
    /** @var \Madj2k\FeRegister\Registration\FrontendUserRegistration $registration */
    $registration = $this->objectManager->get(FrontendUserRegistration::class);
    $result = $registration->setFrontendUserToken($tokenUser)
        ->setCategory('yourExtension')
        ->setRequest($this->request)
        ->validateOptIn($token);

    if ($result >= 200 && $result < 300) {

        // sucessfull

    } elseif ($result >= 300 && $result < 400) {

        // canceled

    } else {
        // error / not found
    }
}
```

### 6. Signal-Slot for extension specific action after opt-in
We need a second signal-slot in order to do whatever we need to do after the opt-in
```
    $signalSlotDispatcher->connect(
        Madj2k\FeRegister\Registration\AbstractRegistration::class,
        Madj2k\FeRegister\Registration\AbstractRegistration::SIGNAL_AFTER_REGISTRATION_COMPLETED . 'YourExtension',
        Your\Extension\Alerts\AlertManager::class',
        'saveAlertByRegistration'
    );
```

### 7. Method for the specific action
Then we need to define the corresponding method:
```
    /**
     * Save alert by registration
     * Used by SignalSlot
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param \Madj2k\FeRegister\Domain\Model\OptIn $optIn
     * @return void
     * @api Used by SignalSlot
     */
    public function saveAlertByRegistration(
        \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser,
        \Madj2k\FeRegister\Domain\Model\OptIn $optIn
    ) {

        if (
            ($alert = $optIn->getData())
            && ($alert instanceof \Your\Extension\Domain\Model\Alert)
        ) {

            try {
                // create alert here
            } catch (\Your\Extension\Exception $exception) {
                // do nothing here
            }
        }
    }
```
## Another use-cases
* You can also:
** Send a confirmation-email after the opt-in was successful (Using SIGNAL_AFTER_ALERT_CREATED-Signal-Slot)
** Delete all extension-specific data if the frontendUser is deleted (Using SIGNAL_AFTER_REGISTRATION_ENDED-Signal-Slot)
** ... do many other fancy stuff ;-)

# Consent (Privacy, Terms, Marketing)
The extension has a ViewHelper and validators to obtain consent to privacy, terms of use and advanced marketing.
In order to obtain the consents, only the corresponding ViewHelper must be used in the own extension. As soon as an opt-in is carried out, the corresponding consents are automatically documented and stored in the database. The consents granted are recorded accordingly with the associated data (IP address, browser, etc.). In addition, the consent to the terms of use and to marketing is stored in the FrontendUser, as these consents are usually page-wide and independent of the respective context of the consent.
## 1. In Fluid
The following code can be used to obtain the appropriate consent. It is important that the ViewHelper is used within a form and that the FormErrors are also returned via Fluid.
```
<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
	xmlns:feRegister="http://typo3.org/ns/Madj2k/FeRegister/ViewHelpers"
	xmlns:ajaxApi="http://typo3.org/ns/Madj2k/AjaxApi/ViewHelpers"
	data-namespace-typo3-fluid="true">

	<f:form action="create" name="alert" object="{alert}">

        [...]

        <feRegister:consent type="terms" />
        <feRegister:consent type="privacy" key="default" />
        <feRegister:consent type="marketing" />

        [...]

	</f:form>
</html>
```
## 2. In the controller
Only the corresponding validators are included here. They always refer to the form object.
```
    /**
     * action create
     *
     * @param \Your\Extension\Domain\Model\Alert $alert
     * @param string $email
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @TYPO3\CMS\Extbase\Annotation\Validate("\Madj2k\FeRegister\Validation\Consent\TermsValidator", param="alert")
     * @TYPO3\CMS\Extbase\Annotation\Validate("\Madj2k\FeRegister\Validation\Consent\PrivacyValidator", param="alert")
     * @TYPO3\CMS\Extbase\Annotation\Validate("\Madj2k\FeRegister\Validation\Consent\MarketingValidator", param="alert")
     */
    public function createAction(
        \Your\Extension\Domain\Model\Alert $alert,
        string $email = ''
    ): void {

        [...]
```
An opt-in procedure is usually not carried out for logged-in frontend users. If you still want to record the time of consent for a registration, you can achieve this with the following code:
```
    \Madj2k\FeRegister\DataProtection\ConsentHandler::add(
        $request,
        $frontendUser,
        $alert,
        'new alert'
    );
```

## When migrating from rkw_registration to fe_register
Execute the following MySQL-queries BEFORE install!
```
RENAME TABLE `tx_rkwregistation_domain_model_privacy` TO `tx_feregister_domain_model_consent`;
RENAME TABLE `tx_rkwregistration_domain_model_encrypteddata` TO `tx_feregister_domain_model_encrypteddata`;
RENAME TABLE `tx_rkwregistration_domain_model_shippingaddress` TO `tx_feregister_domain_model_shippingaddress`;
RENAME TABLE `tx_rkwregistration_domain_model_title` TO `tx_feregister_domain_model_title`;
```

Execute the following MySQL-queries AFTER install!
```
UPDATE fe_users SET tx_feregister_title = tx_rkwregistration_title;
UPDATE fe_users SET tx_feregister_gender = tx_rkwregistration_gender;
UPDATE fe_users SET tx_feregister_mobile = tx_rkwregistration_mobile;
UPDATE fe_users SET tx_feregister_twitter_url = tx_rkwregistration_twitter_url;
UPDATE fe_users SET tx_feregister_facebook_url = tx_rkwregistration_facebook_url;
UPDATE fe_users SET tx_feregister_xing_url = tx_rkwregistration_xing_url;
UPDATE fe_users SET tx_feregister_register_remote_ip = tx_rkwregistration_register_remote_ip;
UPDATE fe_users SET tx_feregister_language_key  = tx_rkwregistration_language_key;
UPDATE fe_users SET tx_feregister_login_error_count = tx_rkwregistration_login_error_count;
UPDATE fe_users SET tx_feregister_data_protection_status = tx_rkwregistration_data_protection_status;

UPDATE `tx_feregister_domain_model_consent` SET `consent_privacy` = 1;
```
