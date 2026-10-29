<?php

/**
 * AdminZone - Mailer class
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2018 Spoje.Net
 */

namespace AdminZone;

/**
 * Description of Mailer
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Mailer extends \Ease\HtmlMailer
{
    /**
     * Send Mail Message
     *
     * @param string $emailAddress
     * @param string $mailSubject
     * @param string $emailContents
     */
    public function __construct(
        $emailAddress,
        $mailSubject,
        $emailContents = ''
    ) {
        if (\Ease\Shared::cfg('SUPPRESS_EMAILS') == 'true') {
            $emailContents = "OriginalTO: " . $emailAddress . "\n\n " . $emailContents;
            $emailAddress = \Ease\Shared::cfg('EASE_EMAILTO');
        }
        if (\Ease\Shared::cfg('SEND_INFO_TO')) {
            $emailAddress .= ',' . \Ease\Shared::cfg('SEND_INFO_TO');
        }
        parent::__construct($emailAddress, $mailSubject, $emailContents);
        $this->setMailHeaders([
            'Reply-To' => 'fakturace@spoje.net',
            'From' => \Ease\Shared::cfg('SEND_MAILS_FROM')
        ]);
    }

    /**
     * Obtain Address for Customer
     *
     * @param \AbraFlexi\Adresar $customerAddress
     *
     * @deprecated since version 1.0-163 moved to AbraFlexi\Adresar
     *
     * @return string email
     */
    public static function getNotificationEmailAddres($customerAddress)
    {
        $email = null;
        $emailsRaw = $customerAddress->getFlexiData(
            $customerAddress->getApiURL(),
            ['detail' => 'custom:id,email,kontakty(primarni,email)', 'relations' => 'kontakty']
        );
        if (is_array($emailsRaw)) {
            $emails = $emailsRaw[0];
            if (array_key_exists('email', $emails) && strlen(trim($emails['email']))) {
                $email = $emails['email'];
            }
            if (array_key_exists('kontakty', $emails) && count($emails['kontakty'])) {
                foreach ($emails['kontakty'] as $kontakt) {
                    if (($kontakt['primarni'] == 'true') && strlen(trim($kontakt['email']))) {
                        $email = $kontakt['email'];
                        break;
                    }
                }
            }
        }
        return $email;
    }
}
