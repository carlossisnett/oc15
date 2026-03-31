<?php
$item_id = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;

$create_view_sql = "
CREATE OR REPLACE VIEW vw_historial_salidas_articulo AS
SELECT
    ii.item_id,
    il.codSAP,
    il.name AS articulo_nombre,
    il.description AS articulo_descripcion,
    si.id AS solicitud_id,
    si.numero_solicitud,
    si.SAPDocEntry,
    si.date_created,
    si.status,
    si.estado_almacen,
    si.username,
    u.name AS solicitante_nombre,
    ii.quantity,
    ii.codigo_marca,
    ii.codigo_departamento
FROM inventory_items ii
INNER JOIN solicitud_de_inventario si ON si.id = ii.solicitud_id
INNER JOIN item_list il ON il.id = ii.item_id
LEFT JOIN users u ON u.username = si.username
";
$view_created = $conn->query($create_view_sql);
?>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Historial de Salidas por Artículo</h3>
        <div class="card-tools">
            <a href="?page=items" class="btn btn-flat btn-default">
                <span class="fa fa-arrow-left"></span> Volver a Artículos
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if($item_id <= 0): ?>
            <div class="alert alert-danger">No se recibió un artículo válido.</div>
        <?php else: ?>
            <?php
            $item_qry = $conn->query("SELECT id, codSAP, name, description, status FROM item_list WHERE id = '{$item_id}' LIMIT 1");
            ?>
            <?php if(!$item_qry || $item_qry->num_rows <= 0): ?>
                <div class="alert alert-danger">El artículo seleccionado no existe.</div>
            <?php else: ?>
                <?php $item = $item_qry->fetch_assoc(); ?>

                <?php if($view_created === false): ?>
                    <div class="alert alert-warning">
                        No se pudo crear/actualizar la vista <b>vw_historial_salidas_articulo</b>.
                        Error: <?php echo $conn->error; ?>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <h5 class="mb-1"><?php echo htmlspecialchars($item['description']); ?></h5>
                    <div><b>Código SAP:</b> <?php echo htmlspecialchars($item['codSAP']); ?></div>
               
                    <div>
                        <b>Estado:</b>
                        <?php if((int)$item['status'] === 1): ?>
                            <span class="badge badge-success">Activo</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inactivo</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                $history_sql = "
                SELECT
                    h.*,
                    CONCAT(ma.codigo_ccosto, ' ', ma.nombre_ccosto) AS marca_nombre,
                    CONCAT(de.codigo_ccosto, ' ', de.nombre_ccosto) AS departamento_nombre
                FROM vw_historial_salidas_articulo h
                LEFT JOIN centro_costo ma ON ma.codigo_ccosto = h.codigo_marca
                LEFT JOIN centro_costo de ON de.codigo_ccosto = h.codigo_departamento
                WHERE h.item_id = '{$item_id}'
                ORDER BY unix_timestamp(h.date_created) DESC, h.solicitud_id DESC
                ";
                $history_qry = $conn->query($history_sql);
                ?>

                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="history-table">
                        <colgroup>
                            <col width="8%">
                            <col width="12%">
                            <col width="12%">
                            <col width="8%">
                            <col width="12%">
                            <col width="8%">
                            <col width="10%">
                            <col width="10%">
                            <col width="10%">
                            <col width="10%">
                            <col width="10%">
                            <col width="8%">
                        </colgroup>
                        <thead>
                            <tr class="bg-navy disabled">
                                <th>ID de referencia</th>
                                <th>Fecha</th>
                                <th># Salida</th>
                                <th># SAP</th>
                                <th>Solicitante</th>
                                <th>Cantidad</th>
                                <th>Estado</th>
                                <th>Estado Almacén</th>
                                <th>Marca</th>
                                <th>Departamento</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($history_qry && $history_qry->num_rows > 0): ?>
                                <?php while($row = $history_qry->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-center"><?php echo (int)$row['solicitud_id']; ?></td>
                                        <td><?php echo date("Y-m-d H:i", strtotime($row['date_created'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['numero_solicitud']); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars((string)$row['SAPDocEntry']); ?></td>
                                        <td><?php echo htmlspecialchars($row['solicitante_nombre'] ?: $row['username']); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars((string)$row['quantity']); ?></td>
                                        <td>
                                            <?php
                                            switch ((string)$row['status']) {
                                                case '1':
                                                    echo '<span class="badge badge-success">Aprobado</span>';
                                                    break;
                                                case '2':
                                                    echo '<span class="badge badge-danger">Rechazado</span>';
                                                    break;
                                                case '3':
                                                    echo '<b>Listo para aprobar</b>';
                                                    break;
                                                default:
                                                    echo '<span class="badge badge-secondary">Pendiente</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            switch ((string)$row['estado_almacen']) {
                                                case '1':
                                                    echo '<span class="badge badge-success">Entregado</span>';
                                                    break;
                                                case '2':
                                                    echo '<span class="badge badge-danger">Rechazado</span>';
                                                    break;
                                                case '3':
                                                    echo '<b>Listo para entregar</b>';
                                                    break;
                                                case '4':
                                                    echo '<b>Enviado a SAP</b>';
                                                    break;
                                                default:
                                                    echo '<span class="badge badge-secondary">Pendiente</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars((string)$row['marca_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars((string)$row['departamento_nombre']); ?></td>
                                        <td class="text-center">
                                            <a class="btn btn-flat btn-default btn-sm" href="?page=inventario/view_si&id=<?php echo (int)$row['solicitud_id']; ?>">
                                                <span class="fa fa-eye text-primary"></span> Ver salida
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center">Este artículo no tiene historial de salidas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    $(document).ready(function(){
        $('#history-table').dataTable();
    });
</script>
