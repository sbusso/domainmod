<?php
/**
 * /assets/edit/ssl-provider-account.php
 *
 * This file is part of DomainMOD, an open source domain and internet asset manager.
 * Copyright (c) 2010-2017 Greg Chetcuti <greg@chetcuti.com>
 *
 * Project: http://domainmod.org   Author: http://chetcuti.com
 *
 * DomainMOD is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * DomainMOD is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with DomainMOD. If not, see
 * http://www.gnu.org/licenses/.
 *
 */
?>
<?php
require_once __DIR__ . '/../../_includes/start-session.inc.php';
require_once __DIR__ . '/../../_includes/init.inc.php';

require_once DIR_ROOT . '/vendor/autoload.php';

$system = new DomainMOD\System();
$error = new DomainMOD\Error();
$time = new DomainMOD\Time();
$form = new DomainMOD\Form();
$assets = new DomainMOD\Assets();

require_once DIR_INC . '/head.inc.php';
require_once DIR_INC . '/config.inc.php';
require_once DIR_INC . '/software.inc.php';
require_once DIR_INC . '/debug.inc.php';
require_once DIR_INC . '/settings/assets-edit-ssl-account.inc.php';
require_once DIR_INC . '/database.inc.php';

$pdo = $system->db();
$system->authCheck();

$del = $_GET['del'];
$really_del = $_GET['really_del'];

$sslpaid = $_GET['sslpaid'];
$new_owner_id = $_POST['new_owner_id'];
$new_ssl_provider_id = $_POST['new_ssl_provider_id'];
$new_email_address = $_POST['new_email_address'];
$new_username = $_POST['new_username'];
$new_password = $_POST['new_password'];
$new_reseller = $_POST['new_reseller'];
$new_reseller_id = $_POST['new_reseller_id'];
$new_notes = $_POST['new_notes'];
$new_sslpaid = $_POST['new_sslpaid'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $system->readOnlyCheck($_SERVER['HTTP_REFERER']);

    if ($new_username != "" && $new_owner_id != "" && $new_ssl_provider_id != "" && $new_owner_id != "0" && $new_ssl_provider_id != "0") {

        $stmt = $pdo->prepare("
            UPDATE ssl_accounts
            SET owner_id = :new_owner_id,
                ssl_provider_id = :new_ssl_provider_id,
                email_address = :new_email_address,
                username = :new_username,
                `password` =:new_password,
                reseller = :new_reseller,
                reseller_id = :new_reseller_id,
                notes = :new_notes,
                update_time = :timestamp
            WHERE id = :new_sslpaid");
        $stmt->bindValue('new_owner_id', $new_owner_id, PDO::PARAM_INT);
        $stmt->bindValue('new_ssl_provider_id', $new_ssl_provider_id, PDO::PARAM_INT);
        $stmt->bindValue('new_email_address', $new_email_address, PDO::PARAM_STR);
        $stmt->bindValue('new_username', $new_username, PDO::PARAM_STR);
        $stmt->bindValue('new_password', $new_password, PDO::PARAM_STR);
        $stmt->bindValue('new_reseller', $new_reseller, PDO::PARAM_INT);
        $stmt->bindValue('new_reseller_id', $new_reseller_id, PDO::PARAM_STR);
        $stmt->bindValue('new_notes', $new_notes, PDO::PARAM_LOB);
        $timestamp = $time->stamp();
        $stmt->bindValue('timestamp', $timestamp, PDO::PARAM_STR);
        $stmt->bindValue('new_sslpaid', $new_sslpaid, PDO::PARAM_INT);
        $stmt->execute();

        $sslpaid = $new_sslpaid;

        $temp_ssl_provider = $assets->getSslProvider($new_ssl_provider_id);

        $temp_owner = $assets->getOwner($new_owner_id);

        $_SESSION['s_message_success'] .= "SSL Account " . $new_username . " (" . $temp_ssl_provider . ", " . $temp_owner . ") Updated<BR>";

        header("Location: ../ssl-accounts.php");
        exit;

    } else {

        if ($new_owner_id == '' || $new_owner_id == '0') {

            $_SESSION['s_message_danger'] .= "Choose the Owner<BR>";

        }

        if ($new_ssl_provider_id == '' || $new_ssl_provider_id == '0') {

            $_SESSION['s_message_danger'] .= "Choose the SSL Provider<BR>";

        }

        if ($new_username == "") { $_SESSION['s_message_danger'] .= "Enter a username<BR>"; }

    }

} else {

    $stmt = $pdo->prepare("
        SELECT owner_id, ssl_provider_id, email_address, username, `password`, reseller, reseller_id, notes
        FROM ssl_accounts
        WHERE id = :sslpaid");
    $stmt->bindValue('sslpaid', $sslpaid, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();

    if ($result) {

        $new_owner_id = $result->owner_id;
        $new_ssl_provider_id = $result->ssl_provider_id;
        $new_email_address = $result->email_address;
        $new_username = $result->username;
        $new_password = $result->password;
        $new_reseller = $result->reseller;
        $new_reseller_id = $result->reseller_id;
        $new_notes = $result->notes;

    }

}

if ($del == "1") {

    $stmt = $pdo->prepare("
        SELECT account_id
        FROM ssl_certs
        WHERE account_id = :sslpaid
        LIMIT 1");
    $stmt->bindValue('sslpaid', $sslpaid, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchColumn();

    if ($result) {

        $existing_ssl_certs = 1;

    }

    if ($existing_ssl_certs > 0) {

        $_SESSION['s_message_danger'] .= "This SSL Account has SSL certificates associated with it and cannot be
        deleted<BR>";

    } else {

        $_SESSION['s_message_danger'] .= "Are you sure you want to delete this SSL Account?<BR><BR><a
            href=\"ssl-provider-account.php?sslpaid=" . $sslpaid . "&really_del=1\">YES, REALLY DELETE THIS SSL PROVIDER ACCOUNT</a><BR>";

    }

}

if ($really_del == "1") {

    $stmt = $pdo->prepare("
        SELECT a.username AS username, o.name AS owner_name, p.name AS ssl_provider_name
        FROM ssl_accounts AS a, owners AS o, ssl_providers AS p
        WHERE a.owner_id = o.id
          AND a.ssl_provider_id = p.id
          AND a.id = :sslpaid");
    $stmt->bindValue('sslpaid', $sslpaid, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();

    if ($result) {

        $temp_username = $result->username;
        $temp_owner_name = $result->owner_name;
        $temp_ssl_provider_name = $result->ssl_provider_name;

    }

    $stmt = $pdo->prepare("
        DELETE FROM ssl_accounts
        WHERE id = :sslpaid");
    $stmt->bindValue('sslpaid', $sslpaid, PDO::PARAM_INT);
    $stmt->execute();

    $_SESSION['s_message_success'] .= "SSL Account " . $temp_username . " (" . $temp_ssl_provider_name . ", " . $temp_owner_name . ") Deleted<BR>";

    $system->checkExistingAssets();

    header("Location: ../ssl-accounts.php");
    exit;

}
?>
<?php require_once DIR_INC . '/doctype.inc.php'; ?>
<html>
<head>
    <title><?php echo $system->pageTitle($page_title); ?></title>
    <?php require_once DIR_INC . '/layout/head-tags.inc.php'; ?>
</head>
<body class="hold-transition skin-red sidebar-mini">
<?php require_once DIR_INC . '/layout/header.inc.php'; ?>
<?php
echo $form->showFormTop('');

$result = $pdo->query("
    SELECT id, `name`
    FROM ssl_providers
    ORDER BY `name` ASC")->fetchAll();

if ($result) {

    echo $form->showDropdownTop('new_ssl_provider_id', 'SSL Provider', '', '1', '');

    foreach ($result as $row) {

        echo $form->showDropdownOption($row->id, $row->name, $new_ssl_provider_id);

    }

    echo $form->showDropdownBottom('');

}

$result = $pdo->query("
    SELECT id, `name`
    FROM owners
    ORDER BY `name` ASC")->fetchAll();

if ($result) {

    echo $form->showDropdownTop('new_owner_id', 'Account Owner', '', '1', '');

    foreach ($result as $row) {

        echo $form->showDropdownOption($row->id, $row->name, $new_owner_id);

    }

    echo $form->showDropdownBottom('');

}

echo $form->showInputText('new_email_address', 'Email Address (100)', '', $new_email_address, '100', '', '', '', '');
echo $form->showInputText('new_username', 'Username (100)', '', $new_username, '100', '', '1', '', '');
echo $form->showInputText('new_password', 'Password (255)', '', $new_password, '255', '', '', '', '');
echo $form->showRadioTop('Reseller Account?', '', '');
echo $form->showRadioOption('new_reseller', '1', 'Yes', $new_reseller, '<BR>', '&nbsp;&nbsp;&nbsp;&nbsp;');
echo $form->showRadioOption('new_reseller', '0', 'No', $new_reseller, '', '');
echo $form->showRadioBottom('');
echo $form->showInputText('new_reseller_id', 'Reseller ID (100)', '', $new_reseller_id, '100', '', '', '', '');
echo $form->showInputTextarea('new_notes', 'Notes', '', $new_notes, '', '', '');
echo $form->showInputHidden('new_sslpaid', $sslpaid);
echo $form->showSubmitButton('Save', '', '');
echo $form->showFormBottom('');
?>
<BR><a href="ssl-provider-account.php?sslpaid=<?php echo urlencode($sslpaid); ?>&del=1">DELETE THIS SSL PROVIDER ACCOUNT</a>
<?php require_once DIR_INC . '/layout/footer.inc.php'; ?>
</body>
</html>
