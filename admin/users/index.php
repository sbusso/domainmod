<?php
/**
 * /admin/users/index.php
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
$layout = new DomainMOD\Layout();
$time = new DomainMOD\Time();

require_once DIR_INC . '/head.inc.php';
require_once DIR_INC . '/config.inc.php';
require_once DIR_INC . '/software.inc.php';
require_once DIR_INC . '/debug.inc.php';
require_once DIR_INC . '/settings/admin-users-main.inc.php';
require_once DIR_INC . '/database.inc.php';

$system->authCheck();
$system->checkAdminUser($_SESSION['s_is_admin']);

$export_data = $_GET['export_data'];

$sql = "SELECT u.id, u.first_name, u.last_name, u.username, u.email_address, u.admin, u.read_only, u.active, u.number_of_logins, u.last_login, u.creation_type_id, u.created_by, u.insert_time, u.update_time, us.default_timezone, us.default_currency
        FROM users AS u, user_settings AS us
        WHERE u.id = us.user_id
        ORDER BY u.first_name, u.last_name, u.username, u.email_address";

if ($export_data == '1') {

    $result = mysqli_query($dbcon, $sql) or $error->outputSqlError($dbcon, '1', 'ERROR');

    $export = new DomainMOD\Export();
    $export_file = $export->openFile('user_list', strtotime($time->stamp()));

    $row_contents = array($page_title);
    $export->writeRow($export_file, $row_contents);

    $export->writeBlankRow($export_file);

    $row_contents = array(
        'Status',
        'First Name',
        'Last Name',
        'Username',
        'Email Address',
        'Admin?',
        'Read-only?',
        'Default Currency',
        'Default Timezone',
        'Number of Logins',
        'Last Login',
        'Creation Type',
        'Created By',
        'Inserted',
        'Updated'
    );
    $export->writeRow($export_file, $row_contents);

    if (mysqli_num_rows($result) > 0) {

        while ($row = mysqli_fetch_object($result)) {

            if ($row->admin == '1') {

                $is_admin = '1';

            } else {

                $is_admin = '0';

            }

            if ($row->read_only == '1') {

                $is_read_only = '1';

            } else {

                $is_read_only = '0';

            }

            if ($row->active == '1') {

                $status = 'Active';

            } else {

                $status = 'Inactive';

            }

            $creation_type = $system->getCreationType($row->creation_type_id);

            if ($row->created_by == '0') {
                $created_by = 'Unknown';
            } else {
                $user = new DomainMOD\User();
                $created_by = $user->getFullName($row->created_by);
            }

            $row_contents = array(
                $status,
                $row->first_name,
                $row->last_name,
                $row->username,
                $row->email_address,
                $is_admin,
                $is_read_only,
                $row->default_currency,
                $row->default_timezone,
                $row->number_of_logins,
                $time->toUserTimezone($row->last_login),
                $creation_type,
                $created_by,
                $time->toUserTimezone($row->insert_time),
                $time->toUserTimezone($row->update_time)
            );
            $export->writeRow($export_file, $row_contents);

        }

    }

    $export->closeFile($export_file);

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
Below is a list of all users that have access to <?php echo SOFTWARE_TITLE; ?>.<BR><BR>
<a href="add.php"><?php echo $layout->showButton('button', 'Add User'); ?></a>
<a href="index.php?export_data=1"><?php echo $layout->showButton('button', 'Export'); ?></a><BR><BR><?php

$result = mysqli_query($dbcon, $sql) or $error->outputSqlError($dbcon, '1', 'ERROR');

if (mysqli_num_rows($result) > 0) { ?>

<table id="<?php echo $slug; ?>" class="<?php echo $datatable_class; ?>">
    <thead>
    <tr>
        <th width="20px"></th>
        <th>User</th>
        <th>Username</th>
        <th>Email</th>
    </tr>
    </thead>
    <tbody><?php

    while ($row = mysqli_fetch_object($result)) { ?>

        <tr>
        <td></td>
        <td>
            <a <?php if ($row->active != '1') { ?>style="text-decoration: line-through;" <?php } ?>href="edit.php?uid=<?php echo $row->id; ?>"><?php echo $row->first_name; ?>&nbsp;<?php echo $row->last_name; ?></a><?php if ($row->admin == '1') echo "&nbsp;&nbsp;<strong>A</strong>"; ?><?php if ($row->read_only == '1') echo "&nbsp;&nbsp;<strong>R</strong>"; ?>
        </td>
        <td>
            <a href="edit.php?uid=<?php echo $row->id; ?>"><?php echo $row->username; ?></a>
        </td>
        <td>
            <a href="edit.php?uid=<?php echo $row->id; ?>"><?php echo $row->email_address; ?></a>
        </td>
        </tr><?php

    } ?>

    </tbody>
</table>

    <strong>A</strong> = Admin&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>R</strong> = Read-Only&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="text-decoration: line-through;">STRIKE</span> = Inactive<?php

} ?>
<?php require_once DIR_INC . '/layout/footer.inc.php'; ?>
</body>
</html>
