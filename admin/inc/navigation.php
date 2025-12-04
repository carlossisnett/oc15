</style>
<!-- Main Sidebar Container -->
      <aside class="main-sidebar sidebar-dark-primary bg-black elevation-4 sidebar-no-expand">
        <!-- Brand Logo -->
        <a href="<?php echo base_url ?>admin" class="brand-link bg-danger text-sm">
        <img src="<?php echo validate_image($_settings->info('logo'))?>" alt="Store Logo" class="brand-image img-circle elevation-3" style="width: 1.7rem;height: 1.7rem;max-height: unset">
         <!-- <span class="brand-text font-weight-light"><?php /*echo $_settings->info('short_name')*/ ?></span>-->
         <span class="brand-text font-weight-light"><?php echo 'Menú Principal' ?></span>
        </a>
        <!-- Sidebar -->
        <div class="sidebar os-host os-theme-light os-host-overflow os-host-overflow-y os-host-resize-disabled os-host-transition os-host-scrollbar-horizontal-hidden">
          <div class="os-resize-observer-host observed">
            <div class="os-resize-observer" style="left: 0px; right: auto;"></div>
          </div>
          <div class="os-size-auto-observer observed" style="height: calc(100% + 1px); float: left;">
            <div class="os-resize-observer"></div>
          </div>
          <div class="os-content-glue" style="margin: 0px -8px; width: 249px; height: 646px;"></div>
          <div class="os-padding">
            <div class="os-viewport os-viewport-native-scrollbars-invisible" style="overflow-y: scroll;">
              <div class="os-content" style="padding: 0px 8px; height: 100%; width: 100%;">
                <!-- Sidebar user panel (optional) -->
                <div class="clearfix"></div>
                <!-- Sidebar Menu -->
                <nav class="mt-4">
                   <ul class="nav nav-pills nav-sidebar flex-column text-sm nav-compact nav-flat nav-child-indent nav-collapse-hide-child" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item dropdown">
                      <a href="./" class="nav-link nav-home">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>
                          Dashboard
                        </p>
                      </a>
                    </li> 
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=purchase_orders" class="nav-link nav-purchase_orders">
                        <i class="nav-icon fas fa-file-invoice"></i>
                        <p>
                          Mis Solicitudes de Compra
                        </p>
                      </a>
                    </li>
                    <?php if($_settings->userdata('type') == 1 or $_settings->userdata('type') == 2): ?>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=all_purchase_orders" class="nav-link nav-all_purchase_orders">
                        <i class="nav-icon fas fa-file-invoice"></i>
                        <p>
                          Todas las Solicitudes de Compra
                        </p>
                      </a>
                    </li>
                    <?php endif; ?>
                    <?php 
                    GLOBAL $conn;
                    $id_usuario = $_settings->userdata('id');
                    $qry = $conn->query("SELECT * from `users` where id = '$id_usuario' ");
                      $qry = $qry->fetch_array();
                      ?>
                        <?php if($qry['puede_cotizar'] == true): ?>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=cotizacion" class="nav-link nav-cotizacion">
                        <i class="nav-icon fas fa-book"></i>
                        <p>
                          Mis Cotizaciones de Compra
                        </p>
                      </a>
                    </li>

                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=all_cotizacion" class="nav-link nav-all-cotizacion">
                        <i class="nav-icon fas fa-archive"></i>
                        <p>
                          Todas las Cotizaciones de Compra
                        </p>
                      </a>
                    </li>
                    <?php endif; ?>

                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=inventario" class="nav-link nav-inventario">
                        <i class="nav-icon fas fa-boxes"></i>
                        <p>
                          Salida de Inventario
                      </p>
                      </a>
                    </li>
                    <?php if($_settings->userdata('type') == 1 or $_settings->userdata('type') == 3): ?>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=all_inventario" class="nav-link nav-all_inventario">
                        <i class="nav-icon fas fa-boxes"></i>
                        <p>
                          Todas las Salidas de Inventario
                      </p>
                      </a>
                    </li>
                    <?php endif; ?>
                   
                    <?php if($_settings->userdata('type') == 1 or $_settings->userdata('type') == 2): ?>
                    <li class="nav-header">Sistema</li>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=user/list" class="nav-link nav-user_list">
                        <i class="nav-icon fas fa-users"></i>
                        <p>
                          Lista Usuarios
                        </p>
                      </a>
                    </li>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=system_info" class="nav-link nav-system_info">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>
                          Configuración
                        </p>
                      </a>
                    </li>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=proveedores/manage_proveedor" class="nav-link nav-proveedores_manage_proveedor">
                        <i class="nav-icon fas fa-truck"></i>
                        <p>
                          Agregar Proveedor
                        </p>
                      </a>
                    </li>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=purchase_orders/update_approver" class="nav-link nav-purchase_orders_update_approver">
                        <i class="nav-icon fas fa-user-check"></i>
                        <p>
                          Actualizar Aprobadores
                        </p>
                      </a>
                    </li>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>sincronizar.php" class="nav-link nav-sincronizar" target="_blank">
                        <i class="nav-icon fas fa-sync"></i>
                        <p>
                          Sincronizar con SAP
                        </p>
                      </a>
                    </li>
                    <?php endif; ?>

                  </ul>
                </nav>
                <!-- /.sidebar-menu -->
              </div>
            </div>
          </div>
          <div class="os-scrollbar os-scrollbar-horizontal os-scrollbar-unusable os-scrollbar-auto-hidden">
            <div class="os-scrollbar-track">
              <div class="os-scrollbar-handle" style="width: 100%; transform: translate(0px, 0px);"></div>
            </div>
          </div>
          <div class="os-scrollbar os-scrollbar-vertical os-scrollbar-auto-hidden">
            <div class="os-scrollbar-track">
              <div class="os-scrollbar-handle" style="height: 55.017%; transform: translate(0px, 0px);"></div>
            </div>
          </div>
          <div class="os-scrollbar-corner"></div>
        </div>
        <!-- /.sidebar -->
      </aside>
      <script>
    $(document).ready(function(){
      var page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home' ?>';
      var s = '<?php echo isset($_GET['s']) ? $_GET['s'] : '' ?>';
      page = page.split('/');
      page = page[0];
      if(s!='')
        page = page+'_'+s;

      if($('.nav-link.nav-'+page).length > 0){
             $('.nav-link.nav-'+page).addClass('active')
        if($('.nav-link.nav-'+page).hasClass('tree-item') == true){
            $('.nav-link.nav-'+page).closest('.nav-treeview').siblings('a').addClass('active')
          $('.nav-link.nav-'+page).closest('.nav-treeview').parent().addClass('menu-open')
        }
        if($('.nav-link.nav-'+page).hasClass('nav-is-tree') == true){
          $('.nav-link.nav-'+page).parent().addClass('menu-open')
        }

      }
     
    })
  </script>