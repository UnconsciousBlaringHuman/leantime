@extends($layout)

@section('content')

    <?php
$sprints        = $tpl->get("sprints");
$searchCriteria = $tpl->get("searchCriteria");
$currentSprint  = $tpl->get("currentSprint");
$allTickets     = $tpl->get('allTickets');

$searchCriteria = $tpl->get("searchCriteria");
$currentSprint  = $tpl->get("currentSprint");
$allTicketGroups     = $tpl->get('allTickets');

$todoTypeIcons  = $tpl->get("ticketTypeIcons");

$efforts        = $tpl->get('efforts');
$priorities     = $tpl->get('priorities');
$statusLabels   = $tpl->get('allTicketStates');

//All states >0 (<1 is archive)
$numberofColumns = count($tpl->get('allTicketStates')) - 1;
$size = floor(100 / $numberofColumns);

?>
    @include("tickets::includes.timelineHeader")

    <div class="maincontent">

    @include("tickets::includes.timelineTabs")

        <div class="maincontentinner">

        @displayNotification()

        <div class="row">
            <div class="col-md-6">
                <?php
                $tpl->dispatchTplEvent('filters.afterLefthandSectionOpen');
                ?>

                @include("tickets::includes.ticketNewBtn")
                @include("tickets::includes.ticketFilter")

                <?php
                $tpl->dispatchTplEvent('filters.beforeLefthandSectionClose');
                ?>

                </div>


                <div class="col-md-6">
                    <div class="pull-right">

                        <?php $tpl->dispatchTplEvent('filters.afterRighthandSectionOpen'); ?>

                        <div id="tableButtons" style="display:inline-block"></div>

                        <?php $tpl->dispatchTplEvent('filters.beforeRighthandSectionClose'); ?>

                    </div>
                </div>

            </div>

            <div class="clearfix" style="margin-bottom: 20px;"></div>

            <?php if (isset($allTicketGroups['all'])) {
                $allTickets = $allTicketGroups['all']['items'];
            }
            ?>

            <?php foreach ($allTicketGroups as $group) {?>
            <?php if ($group['label'] != 'all') { ?>
            <h5 class="accordionTitle <?= $group['class'] ?>" id="accordion_link_<?= $group['id'] ?>">
                <a href="javascript:void(0)" class="accordion-toggle" id="accordion_toggle_<?= $group['id'] ?>"
                    onclick="leantime.snippets.accordionToggle('<?= $group['id'] ?>');">
                    <i class="fa fa-angle-down"></i><?= $group['label'] ?> (<?= count($group['items']) ?>)
                </a>
            </h5>
            <div class="simpleAccordionContainer" id="accordion_content-<?= $group['id'] ?>">
                <?php } ?>

                <?php $allTickets = $group['items']; ?>

                <?php $tpl->dispatchTplEvent('allTicketsTable.before', ['tickets' => $allTickets]); ?>
                <table class="table table-bordered display ticketTable " style="width:100%">
                    <colgroup>
                        <col class="con1">
                        <col class="con0">
                        <col class="con1">
                        <col class="con0">
                        <col class="con1">
                        <col class="con0">
                        <col class="con1">
                        <col class="con0">
                        <col class="con1">
                        <col class="con0">
                        <col class="con1">

                    </colgroup>
                    <?php $tpl->dispatchTplEvent('allTicketsTable.beforeHead', ['tickets' => $allTickets]); ?>
                    <thead>
                        <?php $tpl->dispatchTplEvent('allTicketsTable.beforeHeadRow', ['tickets' => $allTickets]); ?>
                        <tr>
                            <th><?= $tpl->__('label.title') ?></th>
                            <th><?= $tpl->__('label.todo_type') ?></th>
                            <th><?= $tpl->__('label.progress') ?></th>
                            <th class="milestone-col"><?= $tpl->__('label.dependent_on') ?></th>

                            <th><?= $tpl->__('label.todo_status') ?></th>

                            <th class="user-col"><?= $tpl->__('label.owner') ?></th>
                            <th><?= $tpl->__('label.planned_start_date') ?></th>
                            <th><?= $tpl->__('label.planned_end_date') ?></th>
                            <th><?= $tpl->__('label.planned_hours') ?></th>
                            <th><?= $tpl->__('label.estimated_hours_remaining') ?></th>
                            <th><?= $tpl->__('label.booked_hours') ?></th>

                            <th class="no-sort"></th>

                        </tr>
                        <?php $tpl->dispatchTplEvent('allTicketsTable.afterHeadRow', ['tickets' => $allTickets]); ?>
                    </thead>
                    <?php $tpl->dispatchTplEvent('allTicketsTable.afterHead', ['tickets' => $allTickets]); ?>
                    <tbody>
                        <?php $tpl->dispatchTplEvent('allTicketsTable.beforeFirstRow', ['tickets' => $allTickets]); ?>
                        <?php foreach ($allTickets as $rowNum => $row) {?>
                        <tr>
                            <?php $tpl->dispatchTplEvent('allTicketsTable.afterRowStart', ['rowNum' => $rowNum, 'tickets' => $allTickets]); ?>
                            <td data-order="<?= $tpl->e($row['headline']) ?>">
                                <?php if($row['type'] == 'milestone'){ ?>
                                <a
                                    href="#/tickets/editMilestone/<?= $tpl->e($row['id']) ?>"><?= $tpl->e($row['headline']) ?></a>
                                <?php }else{ ?>
                                <a
                                    href="#/tickets/showTicket/<?= $tpl->e($row['id']) ?>"><?= $tpl->e($row['headline']) ?></a>
                                <?php } ?>
                            </td>
                            <td><?php echo $tpl->__('label.' . strtolower($row['type'])); ?></td>


                            <td>
                                <?php if($row["type"] == "milestone"){ ?>
                                    <div hx-trigger="load"
                                         hx-get="{{ BASE_URL }}/hx/tickets/milestones/progress?milestoneId=<?=$row["id"] ?>&view=Progress">
                                        <div class="htmx-indicator">
                                            <?=$tpl->__("label.calculating_progress") ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            </td>

                            <?php
                            if ($row['milestoneid'] != '' && $row['milestoneid'] != 0) {
                                $milestoneHeadline = $tpl->escape($row['milestoneHeadline']);
                            } else {
                                $milestoneHeadline = $tpl->__('label.no_milestone');
                            } ?>

                            <td data-order="<?= $milestoneHeadline ?>">
                                @php
                                    // Determine the label text for the dropdown
                                    $labelText =
                                        "<span class='text'>" .
                                        $milestoneHeadline .
                                        "</span> <i class='fa fa-caret-down' aria-hidden='true'></i>";
                                @endphp

                                <!-- Use the determined label text in the dropdown component -->
                                <x-global::content.context-menu :label-text="$labelText" contentRole="link" position="bottom"
                                    align="start" style="background-color:{{ $tpl->escape($row['milestoneColor']) }};">

                                    <!-- Menu Header -->
                                    <li class="nav-header border">{{ __('dropdown.choose_milestone') }}</li>

                                    <!-- No Milestone Option -->
                                    <x-global::actions.dropdown.item href="javascript:void(0);"
                                        style="background-color:#b0b0b0" :data-label="__('label.no_milestone')" :data-value="$row['id'] . '_0_#b0b0b0'">
                                        {{ __('label.no_milestone') }}
                                    </x-global::actions.dropdown.item>

                                    <!-- Dynamic Milestone Options -->
                                    @foreach ($tpl->get('milestones') as $milestone)
                                        @if ($milestone->id != $row['id'])
                                            <x-global::actions.dropdown.item href="javascript:void(0);" :data-label="$tpl->escape($milestone->headline)"
                                                :data-value="$row['id'] .
                                                    '_' .
                                                    $milestone->id .
                                                    '_' .
                                                    $tpl->escape($milestone->tags)"
                                                id="ticketMilestoneChange{{ $row['id'] . $milestone->id }}"
                                                style="background-color:{{ $tpl->escape($milestone->tags) }};">
                                                {{ $tpl->escape($milestone->headline) }}
                                            </x-global::actions.dropdown.item>
                                        @endif
                                    @endforeach

                                </x-global::content.context-menu>

                            </td>
                            <?php

                            if (isset($statusLabels[$row['status']])) {
                                $class = $statusLabels[$row['status']]['class'];
                                $name = $statusLabels[$row['status']]['name'];
                                $sortKey = $statusLabels[$row['status']]['sortKey'];
                            } else {
                                $class = 'label-important';
                                $name = 'new';
                                $sortKey = 0;
                            }

                            ?>
                            <td data-order="<?= $sortKey ?>">
                                @php
                                    // Determine the label text for the dropdown
                                    $labelText =
                                        "<span class='text'>" .
                                        $name .
                                        "</span> <i class='fa fa-caret-down' aria-hidden='true'></i>";
                                @endphp

                                <!-- Use the determined label text in the dropdown component -->
                                <x-global::content.context-menu :label-text="$labelText" contentRole="link" position="bottom"
                                    align="start" class="status {{ $class }}">

                                    <!-- Menu Header -->
                                    <li class="nav-header border">{{ __('dropdown.choose_status') }}</li>

                                    <!-- Dynamic Status Options -->
                                    @foreach ($statusLabels as $key => $label)
                                        <x-global::actions.dropdown.item href="javascript:void(0);" :class="$label['class']"
                                            :data-label="$tpl->escape($label['name'])" :data-value="$row['id'] . '_' . $key . '_' . $label['class']"
                                            id="ticketStatusChange{{ $row['id'] . $key }}">
                                            {{ $tpl->escape($label['name']) }}
                                        </x-global::actions.dropdown.item>
                                    @endforeach

                                </x-global::content.context-menu>

                            </td>

                            <td
                                data-order="<?= $row['editorFirstname'] != '' ? $tpl->escape($row['editorFirstname']) : $tpl->__('dropdown.not_assigned') ?>">
                                <div class="dropdown ticketDropdown userDropdown noBg show ">
                                    @php
                                        // Determine the label text with user's image and name
if ($row['editorFirstname'] != '') {
    $labelText =
        "<span id='userImage" .
        $row['id'] .
        "'>
                                                        <img src='" .
        BASE_URL .
        '/api/users?profileImage=' .
        $row['editorId'] .
        "'
                                                             width='25' style='vertical-align: middle; margin-right:5px;' />
                                                      </span>
                                                      <span id='user" .
        $row['id'] .
        "'>" .
        $tpl->escape($row['editorFirstname']) .
        '</span>';
} else {
    $labelText =
        "<span id='userImage" .
        $row['id'] .
        "'>
                                                        <img src='" .
        BASE_URL .
        "/api/users?profileImage=false'
                                                             width='25' style='vertical-align: middle; margin-right:5px;' />
                                                      </span>
                                                      <span id='user" .
        $row['id'] .
        "'>" .
        $tpl->__('dropdown.not_assigned') .
        '</span>';
}

// Append the dropdown caret icon to the label text
$labelText .= "&nbsp;<i class='fa fa-caret-down' aria-hidden='true'></i>";
                                    @endphp

                                    <!-- Use the context-menu Blade component -->
                                    <x-global::content.context-menu :label-text="$labelText" contentRole="link" position="bottom"
                                        align="start" class="user-dropdown">

                                        <!-- Menu Header -->
                                        <li class="nav-header border">{{ __('dropdown.choose_user') }}</li>

                                        <!-- Dynamic User Options -->
                                        @foreach ($tpl->get('users') as $user)
                                            <x-global::actions.dropdown.item href="javascript:void(0);" :data-label="sprintf(
                                                __('text.full_name'),
                                                $tpl->escape($user['firstname']),
                                                $tpl->escape($user['lastname']),
                                            )"
                                                :data-value="$row['id'] . '_' . $user['id'] . '_' . $user['profileId']" id="userStatusChange{{ $row['id'] . $user['id'] }}">
                                                <img src="{{ BASE_URL }}/api/users?profileImage={{ $user['id'] }}"
                                                    width="25" style="vertical-align: middle; margin-right:5px;" />
                                                {{ sprintf(__('text.full_name'), $tpl->escape($user['firstname']), $tpl->escape($user['lastname'])) }}
                                            </x-global::actions.dropdown.item>
                                        @endforeach

                                    </x-global::content.context-menu>

                                </div>
                            </td>

                            <td data-order="<?php echo $row["editFrom"] ?>" >
                                {{ __("label.due_icon") }}<input type="text" title="{{ __("label.planned_start_date") }}" value="<?php echo format($row["editFrom"])->date() ?>" class="editFromDate secretInput milestoneEditFromAsync fromDateTicket-<?php echo $row["id"];?>" data-id="<?php echo $row["id"];?>" name="editFrom" class=""/>
                            </td>

                            <td data-order="<?php echo $row["editTo"] ?>" >
                                {{ __("label.due_icon") }}<input type="text" title="{{ __("label.planned_end_date") }}" value="<?php echo format($row["editTo"])->date() ?>" class="editToDate secretInput milestoneEditToAsync toDateTicket-<?php echo $row["id"];?>" data-id="<?php echo $row["id"];?>" name="editTo" class="" />

                            </td>

                            <td data-order="<?= $row['planHours'] ?>">
                                <?php echo $row['planHours']; ?>
                            </td>
                            <td data-order="<?= $row['hourRemaining'] ?>">
                                <?php echo $row['hourRemaining']; ?>
                            </td>
                            <td data-order="<?= $row['bookedHours'] ?>">
                                <?php echo $row['bookedHours']; ?>
                            </td>


                            <td>
                                <?php if ($login::userIsAtLeast($roles::$editor)) { ?>
                                @php
                                    // Define the label text for the dropdown toggle
                                    $labelText = '<i class="fa fa-ellipsis-v" aria-hidden="true"></i>';
                                @endphp

                                <!-- Use the context-menu Blade component -->
                                <x-global::content.context-menu :label-text="$labelText" contentRole="link" position="bottom"
                                    align="start">

                                    <!-- Menu Header -->
                                    <li class="nav-header">{{ $tpl->__('subtitles.todo') }}</li>

                                    <!-- Menu Items -->
                                    <x-global::actions.dropdown.item
                                        href="{{ BASE_URL }}/tickets/editMilestone/{{ $row['id'] }}"
                                        class="ticketModal">
                                        <i class="fa fa-edit"></i> {{ $tpl->__('links.edit_milestone') }}
                                    </x-global::actions.dropdown.item>

                                    <x-global::actions.dropdown.item
                                        href="{{ BASE_URL }}/tickets/moveTicket/{{ $row['id'] }}"
                                        class="moveTicketModal sprintModal">
                                        <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                        {{ $tpl->__('links.move_milestone') }}
                                    </x-global::actions.dropdown.item>

                                    <x-global::actions.dropdown.item
                                        href="{{ BASE_URL }}/tickets/delMilestone/{{ $row['id'] }}"
                                        class="delete">
                                        <i class="fa fa-trash"></i> {{ $tpl->__('links.delete') }}
                                    </x-global::actions.dropdown.item>

                                    <!-- Separator -->
                                    <li class="nav-header border"></li>

                                    <x-global::actions.dropdown.item
                                        href="{{ BASE_URL }}/tickets/showAll?search=true&milestone={{ $row['id'] }}">
                                        {{ $tpl->__('links.view_todos') }}
                                    </x-global::actions.dropdown.item>

                                </x-global::content.context-menu>

                                <?php } ?>


                            </td>
                            <?php $tpl->dispatchTplEvent('allTicketsTable.beforeRowEnd', ['tickets' => $allTickets, 'rowNum' => $rowNum]); ?>
                        </tr>
                        <?php } ?>
                        <?php $tpl->dispatchTplEvent('allTicketsTable.afterLastRow', ['tickets' => $allTickets]); ?>
                    </tbody>
                    <?php $tpl->dispatchTplEvent('allTicketsTable.afterBody', ['tickets' => $allTickets]); ?>
                </table>
                <?php $tpl->dispatchTplEvent('allTicketsTable.afterClose', ['tickets' => $allTickets]); ?>

                <?php if ($group['label'] != 'all') { ?>
            </div>
            <?php } ?>
            <?php } ?>

        </div>
    </div>

    <script type="text/javascript">
        <?php $tpl->dispatchTplEvent('scripts.afterOpen'); ?>

        jQuery(document).ready(function() {



            <?php if ($login::userIsAtLeast($roles::$editor)) { ?>
            leantime.ticketsController.initUserDropdown();
            leantime.ticketsController.initMilestoneDropdown();
            leantime.ticketsController.initEffortDropdown();
            leantime.ticketsController.initStatusDropdown();
            leantime.ticketsController.initSprintDropdown();
            leantime.ticketsController.initMilestoneDatesAsyncUpdate();

            <?php } else { ?>
            leantime.authController.makeInputReadonly(".maincontentinner");
            <?php } ?>

            leantime.ticketsController.initMilestoneTable("<?= $searchCriteria['groupBy'] ?>");

        <?php $tpl->dispatchTplEvent('scripts.beforeClose'); ?>
    });
</script>
@endsection
