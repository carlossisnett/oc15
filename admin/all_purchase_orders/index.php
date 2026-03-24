<?php
require_once "./views/view_functions.php";

$user_type = (int)$_SESSION['userdata']['type'];
$user_id   = (int)$_settings->userdata('id');
$username  = $_SESSION['userdata']['username'];

// Check if current user has super_firma
$super_firma_check = $conn->query("SELECT super_firma FROM users WHERE id = '{$user_id}'");
$is_super_firma = ($super_firma_check && ($sf_row = $super_firma_check->fetch_assoc()) && $sf_row['super_firma'] == 1);

// --- POs pending THIS user's approval (status = 3) ---
$result = $conn->query("SELECT * FROM `po_list` WHERE status = 3");
$rows_to_display = array();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $can_approve = puede_aprobar_compras($user_id, $row['id'], $conn);

        // Super firma users also see POs flagged with super_firma = 1
        if (!$can_approve && $is_super_firma && !empty($row['super_firma'])) {
            $can_approve = true;
        }

        if ($can_approve) {
            $rows_to_display[] = $row;
        }
    }
}

// --- Pendientes query (compras only) ---
$pendientes_rows = array();
if ($user_type == 2) {
    $qry_pend = $conn->query("SELECT po.*, CONCAT_WS(' ', u.firstname, u.lastname) as sname
        FROM `po_list` po
        INNER JOIN `users` u ON po.username = u.username
        WHERE po.SAPDocEntry IS NULL
          AND po.status <> 2
          AND po.date_created >= '2024-07-01'
        ORDER BY unix_timestamp(po.date_created) DESC");
    while ($row = $qry_pend->fetch_assoc()) {
        $pendientes_rows[] = $row;
    }
}

// --- Historial query (both types) ---
$historial_rows = array();
$qry_hist = $conn->query("SELECT po.*, CONCAT_WS(' ', u.firstname, u.lastname) as sname
    FROM `po_list` po
    INNER JOIN `users` u ON po.username = u.username
    ORDER BY unix_timestamp(po.date_created) DESC");
while ($row = $qry_hist->fetch_assoc()) {
    $historial_rows[] = $row;
}

// Helper to enrich a row with proveedor name and total
function enrich_row($row, $conn) {
    $prov = $conn->query("SELECT pro.name FROM proveedores pro
        JOIN order_items o ON pro.id = o.proveedor_id
        JOIN po_list p ON p.id = o.po_id
        WHERE p.id = '{$row['id']}' LIMIT 1")->fetch_assoc();
    $row['proveedor_name'] = $prov ? $prov['name'] : '';
    $row['item_count'] = $conn->query("SELECT * FROM order_items WHERE po_id = '{$row['id']}'")->num_rows;
    if ($row['total'] != null) {
        $row['total_amount'] = $row['total'];
    } else {
        $row['total_amount'] = $conn->query("SELECT SUM(quantity * unit_price) AS total FROM order_items WHERE po_id = '{$row['id']}'")->fetch_array()['total']
            + $row['tax_amount'] - $row['discount_amount'];
    }
    return $row;
}

function status_badge($status) {
    switch ($status) {
        case '1': return '<span class="badge badge-success">Aprobado</span>';
        case '2': return '<span class="badge badge-danger">Rechazado</span>';
        case '3': return '<b>Listo para aprobar</b>';
        default:  return '<span class="badge badge-secondary">Pendiente</span>';
    }
}
?>

<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif; ?>

<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title">Solicitudes de Compra</h3>
        <div class="card-tools">
            <a href="?page=purchase_orders/manage_po" class="btn btn-flat btn-primary">
                <span class="fas fa-plus"></span> Crear Nuevo
            </a>
        </div>
    </div>
    <div class="card-body">

        <?php
        // --- Table: POs pending this user's approval ---
        if (count($rows_to_display) > 0): ?>
        <h5>Las siguientes solicitudes requieren su aprobación</h5>
        <div class="container-fluid">
            <table class="table table-hover table-striped">
                <colgroup>
                    <col width="5%"><col width="10%"><col width="10%"><col width="8%">
                    <col width="10%"><col width="10%"><col width="10%"><col width="10%"><col width="10%">
                </colgroup>
                <thead>
                    <tr>
                        <th>#</th><th>Fecha Creación</th><th># Solicitud de Compra</th>
                        <th># SAP</th><th>Proveedor</th><th>Solicitante</th>
                        <th>Monto Total</th><th>Estado</th><th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ($rows_to_display as $row):
                    $row = enrich_row($row, $conn); ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td><?php echo date("M d,Y H:i", strtotime($row['date_created'])); ?></td>
                        <td><?php echo $row['po_no']; ?></td>
                        <td class="text-center"><?php echo $row['SAPDocEntry']; ?></td>
                        <td class="text-center"><?php echo $row['proveedor_name']; ?></td>
                        <td><?php echo $row['username']; ?></td>
                        <td class="text-right"><?php echo special_format($row['total_amount']); ?></td>
                        <td id="status_<?php echo $row['id']; ?>"><?php echo status_badge($row['status']); ?></td>
                        <td align="center">
                            <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                Acción <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <div class="dropdown-menu" role="menu">
                                <a class="dropdown-item" href="?page=purchase_orders/view_po&id=<?php echo $row['id'] ?>">
                                    <span class="fa fa-eye text-primary"></span> Ver
                                </a>
                                <?php if ($user_type == 1 || $user_type == 2): ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="?page=purchase_orders/manage_po&id=<?php echo $row['id'] ?>&edit=true">
                                    <span class="fa fa-edit text-primary"></span> Editar
                                </a>
                                <?php endif; ?>
                                <?php if ($user_type == 1 || $user_type == 2): ?>
                                <div class="dropdown-divider"></div>
                                <a id="aprobar_<?php echo $row['id'] ?>" class="dropdown-item cambiar_estado_aprobar" href="#">
                                    <span class="fa fa-check text-success"></span> Aprobar
                                </a>
                                <div class="dropdown-divider"></div>
                                <a id="rechazar_<?php echo $row['id'] ?>" class="dropdown-item cambiar_estado_aprobar" href="#">
                                    <span class="fa fa-ban text-danger"></span> Rechazar
                                </a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="?page=purchase_orders/manage_po&id=<?php echo $row['id'] ?>&duplicate=true">
                                    <span class="fa fa-copy text-warning"></span> Duplicar
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <h5>Su usuario <?php echo $username ?> no tiene solicitudes de compra por aprobar</h5>
        <?php endif; ?>

        <br>

        <?php
        // --- Buttons: compras gets both, approver gets historial only ---
        $show_pendientes_btn = ($user_type == 2);
        // For compras: pendientes is the default active view; for approver: historial is shown directly
        ?>

        <?php if ($show_pendientes_btn): ?>
        <button id="boton_pendientes" type="button" class="btn btn-success">Pendientes</button>
        <?php endif; ?>
        <button id="boton_historial" type="button" class="btn btn-outline-secondary">Historial</button>

        <?php
        // --- Pendientes section (compras only, visible by default) ---
        if ($user_type == 2): ?>
        <div id="solicitudes_pendientes">
            <h4>Solicitudes Pendientes</h4>
            <div class="container-fluid">
                <table class="table table-hover table-striped">
                    <colgroup>
                        <col width="5%"><col width="10%"><col width="10%"><col width="8%">
                        <col width="10%"><col width="10%"><col width="10%"><col width="10%"><col width="10%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>#</th><th>Fecha Creación</th><th># Solicitud de Compra</th>
                            <th># SAP</th><th>Proveedor</th><th>Solicitante</th>
                            <th>Monto Total</th><th>Estado</th><th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; foreach ($pendientes_rows as $row):
                        $row = enrich_row($row, $conn); ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td><?php echo date("M d,Y H:i", strtotime($row['date_created'])); ?></td>
                            <td><?php echo $row['po_no']; ?></td>
                            <td class="text-center"><?php echo $row['SAPDocEntry']; ?></td>
                            <td class="text-center"><?php echo $row['proveedor_name']; ?></td>
                            <td><?php echo $row['sname']; ?></td>
                            <td class="text-right"><?php echo special_format($row['total_amount']); ?></td>
                            <td><?php echo status_badge($row['status']); ?></td>
                            <td align="center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Acción <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item" href="?page=purchase_orders/view_po&id=<?php echo $row['id'] ?>">
                                        <span class="fa fa-eye text-primary"></span> Ver
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="?page=purchase_orders/manage_po&id=<?php echo $row['id'] ?>&edit=true">
                                        <span class="fa fa-edit text-primary"></span> Editar
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="?page=purchase_orders/manage_po&id=<?php echo $row['id'] ?>&duplicate=true">
                                        <span class="fa fa-copy text-warning"></span> Duplicar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php
        // --- Historial section ---
        // Always hidden on load; revealed by button click for both user types.
        $historial_style = ' style="display:none"';
        ?>
        <div id="todas_solicitudes"<?php echo $historial_style ?>>
            <h4>Historial de Solicitudes de Compra</h4>
            <br>
            <div class="container-fluid">
                <table class="table table-hover table-striped">
                    <colgroup>
                        <col width="5%"><col width="10%"><col width="10%"><col width="8%">
                        <col width="10%"><col width="10%"><col width="10%"><col width="10%"><col width="10%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>#</th><th>Fecha Creación</th><th># Solicitud de Compra</th>
                            <th># SAP</th><th>Proveedor</th><th>Solicitante</th>
                            <th>Monto Total</th><th>Estado</th><th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; foreach ($historial_rows as $row):
                        $row = enrich_row($row, $conn); ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td><?php echo date("M d,Y H:i", strtotime($row['date_created'])); ?></td>
                            <td><?php echo $row['po_no']; ?></td>
                            <td class="text-center"><?php echo $row['SAPDocEntry']; ?></td>
                            <td class="text-center"><?php echo $row['proveedor_name']; ?></td>
                            <td><?php echo $row['sname']; ?></td>
                            <td class="text-right"><?php echo special_format($row['total_amount']); ?></td>
                            <td><?php echo status_badge($row['status']); ?></td>
                            <td align="center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Acción <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item" href="?page=purchase_orders/view_po&id=<?php echo $row['id'] ?>">
                                        <span class="fa fa-eye text-primary"></span> Ver
                                    </a>
                                    <?php if ($user_type == 1 || $user_type == 2): ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="?page=purchase_orders/manage_po&id=<?php echo $row['id'] ?>&edit=true">
                                        <span class="fa fa-edit text-primary"></span> Editar
                                    </a>
                                    <?php endif; ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="?page=purchase_orders/manage_po&id=<?php echo $row['id'] ?>&duplicate=true">
                                        <span class="fa fa-copy text-warning"></span> Duplicar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /.card-body -->
</div><!-- /.card -->

<script>
$(document).ready(function () {

    <?php if ($user_type == 2): ?>
    // Compras: toggle between pendientes and historial
    $('#boton_pendientes').on('click', function () {
        $('#solicitudes_pendientes').show();
        $('#todas_solicitudes').hide();
        $(this).removeClass('btn-outline-secondary').addClass('btn-success');
        $('#boton_historial').removeClass('btn-success').addClass('btn-outline-secondary');
    });

    $('#boton_historial').on('click', function () {
        $('#todas_solicitudes').show();
        $('#solicitudes_pendientes').hide();
        $(this).removeClass('btn-outline-secondary').addClass('btn-success');
        $('#boton_pendientes').removeClass('btn-success').addClass('btn-outline-secondary');
    });
    <?php elseif ($user_type == 1): ?>
    // Approver: historial button toggles the table
    $('#boton_historial').on('click', function () {
        var visible = $('#todas_solicitudes').is(':visible');
        if (visible) {
            $('#todas_solicitudes').hide();
            $(this).removeClass('btn-success').addClass('btn-outline-secondary');
        } else {
            $('#todas_solicitudes').show();
            $(this).removeClass('btn-outline-secondary').addClass('btn-success');
        }
    });
    <?php endif; ?>

    // Approve / reject actions (type 1 only)
    <?php if ($user_type == 1 || $user_type == 2): ?>
    $('.cambiar_estado_aprobar').on('click', function (e) {
        e.preventDefault();
        var id     = $(this).attr('id');
        var status = id.split('_')[0];
        var real_id = id.split('_')[1];
        var status_number = 0;
        var proceder = false;

        if (status == 'aprobar') {
            proceder = window.confirm('¿Desea aprobar la solicitud de compra?');
            status_number = 1;
        }
        if (status == 'rechazar') {
            proceder = window.confirm('¿Desea rechazar la solicitud de compra?');
            status_number = 2;
        }

        if (proceder) {
            $.ajax({
                url: _base_url_ + 'classes/Master.php?f=change_po_status',
                method: 'POST',
                data: { status: status_number, id: real_id, user_id: <?php echo $user_id ?> },
                dataType: 'json',
                error: function (err) {
                    console.log(err);
                    alert_toast('Ocurrió un error', 'error');
                },
                success: function (resp) {
                    if (typeof resp == 'object' && resp.status == 'success') {
                        alert_toast('Estado cambiado correctamente.', 'success');
                        if (status == 'aprobar') {
                            $('#status_' + real_id).html('<span class="badge badge-success">Aprobado</span>');
                        } else {
                            $('#status_' + real_id).html('<span class="badge badge-danger">Rechazado</span>');
                        }
                    } else {
                        alert_toast('Ocurrió un error', 'error');
                        console.log(resp);
                    }
                }
            });
        }
    });
    <?php endif; ?>

    $('.table th, .table td').addClass('px-1 py-0 align-middle');
    $('.table').dataTable();
});
</script>
