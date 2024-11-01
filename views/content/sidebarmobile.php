<?php
    defined( 'ABSPATH' ) or die( 'Are you trying to trick me?' );
?>
<aside class="asideMobile">
    <ul class="closeMenu">
    	<li class="back_from_editor">
            <a href="javascript:void(0)" class="clickActionSideBar" data-action="back_page">
                <span class="icon printqicon-backarrow"></span>
                <span class="title"><?php _e( 'Back', PQD_DOMAIN ) ?></span>
            </a>
        </li>
        <li class="close_sidebar clickActionSideBar" data-action="back_sidebar">
            <a href="javascript:void(0)">
                <span class="title"><?php _e( 'Back', PQD_DOMAIN ) ?></span>
                <span class="icon printqicon-cancel"></span>
            </a>
        </li>
    </ul>
    <ul class="mainMenu">
        <li class="group change_layout_group mainItem">
            <a href="javascript:void(0)" class="mainTrigger clickActionSideBar" data-action='changeState' data-state="shapes">
                <div class=" icon printqicon-shapes path1 path2">
                    <span class="title"><?php _e( 'Shapes', PQD_DOMAIN ) ?></span>
                </div>
            </a>
        </li>
        <li class="group add_page_group mainItem clickActionSideBar" data-action="add_page">
            <a href="javascript:void(0)" class="mainTrigger">
                <div class=" icon printqicon-addnewpage">
                    <span class="title"><?php _e( 'Add new Page', PQD_DOMAIN ) ?></span>
                </div>
            </a>
        </li>
        <li class="group delete_page_group mainItem clickActionSideBar" data-action="delete_page">
            <a href="javascript:void(0)" class="mainTrigger">
                <div class=" icon printqicon-deletepage">
                    <span class="title"><?php _e( 'Delete Page', PQD_DOMAIN ) ?></span>
                </div>
            </a>
        </li>
        <?php if ( ! $is_admin && is_user_logged_in() ) { ?>
            <li class="group saveProject_group mainItem clickActionSideBar" data-action='changeState' data-state="save_project">
            <a href="javascript:void(0)" class="mainTrigger">
                <div class=" icon printqicon-cloud-saveproject">
                    <span class="title"><?php echo esc_html__( 'Save Project', PQD_DOMAIN ) ?></span>
                </div>
            </a>
        </li>
            <li class="group loadProject_group mainItem clickActionSideBar" data-action='changeState' data-state="load_project">
            <a href="javascript:void(0)" class="mainTrigger">
                <div class=" icon printqicon-cloud-loadproject">
                    <span class="title"><?php echo esc_html__( 'Load Project', PQD_DOMAIN ) ?></span>
                </div>
            </a>
        </li>
        <?php } ?>
    </ul>

    <div id="saveProjectFrameMobile">
        <?php
            $project_name        = '';
            $project_description = '';
            if ( $project_id = $this->getData( 'project_id' ) ) {
                $project             = get_post( $project_id );
                $project_name        = get_the_title( $project );
                $project_description = $project->post_content;
            }
        ?>
        <div class="projectData">
            <input id="projectId" name="project_id" type="hidden" value="<?php echo esc_attr( $project_id ) ?>"/>
        </div>
        <ul>
            <li>
                <label for="projectName"><?php echo esc_html__( 'Project Name', PQD_DOMAIN ) ?>:</label>
                <input id="projectName" class="customer_save_project" name="project_name" value="<?php echo esc_attr( $project_name ) ?>"/>
            </li>
            <li>
                <label for="projectDescription"><?php echo esc_html__( 'Project Description', PQD_DOMAIN ) ?>:</label>
                <textarea id="projectDescription" name="project_description"><?php echo esc_attr( $project_description ) ?></textarea>
            </li>
            <li class="clickActionSideBar" data-action="save_project_action">
                <button id="save" class="button btn-cart" title="<?php echo esc_attr__( 'Save Project', PQD_DOMAIN ) ?>"
                        type="button">
                            <span>
                                <span><?php echo esc_html__( 'Save Project', PQD_DOMAIN ) ?></span>
                            </span>
                </button>
            </li>
            <li class="clickActionSideBar" data-action="save-new-project">
                <button id="save_as" class="button btn-cart clickActionSidebar"
                        data-tooltip="save-as-new-project-button"
                        data-action="save-new-project"
                        title="<?php esc_attr_e( 'Save Project', PQD_DOMAIN )
                        ?>"
                        type="button">
                    <span>
                        <span><?php esc_html_e( 'Save As New', PQD_DOMAIN ) ?></span>
                    </span>
                </button>
            </li>
        </ul>
    </div>
    <div id="loadProjectFrameMobile">
        <?php
            $product_id    = intval( $this->getData( 'post', 0 ) );
            $savedProjects = Printq_Helper_Design::getProductSavedProjects( get_current_user_id(), $product_id );
            if ( ! $savedProjects ) { ?>
                <div id="noProjects">
                    <?php esc_html_e( 'You haven\'t saved any project for this product', PQD_DOMAIN ); ?>
                </div>
            <?php }
            else { ?>
                <ul>
                    <?php foreach ( $savedProjects as $savedProject ) {?>
                        <li id="project_<?php echo esc_attr( $savedProject->ID ) ?>" class="body">
                            <div class="projectName"><span><?php echo esc_html( $savedProject->post_title ) ?></span></div>
                            <div class="projectDescription"> <span><?php echo esc_html( $savedProject->post_content ) ?></span> </div>
                            <div>
                                <input type="hidden" name="projectId" value="<?php echo esc_attr( $savedProject->ID ) ?>"/>
                                <button id="load" class="button clickActionSideBar" data-action="load_project" title="<?php esc_attr_e( 'Load', PQD_DOMAIN ) ?>" type="button">
                                    <span>
                                        <span><?php esc_html_e( 'Load', PQD_DOMAIN ) ?></span>
                                    </span>
                                </button>
                                <button id="delete" class="button btn-cart clickActionSideBar" data-action="delete_project" title="<?php esc_attr_e( 'Delete', PQD_DOMAIN ) ?>" type="button">
                                    <span>
                                        <span><?php esc_html_e( 'Delete', PQD_DOMAIN ) ?></span>
                                    </span>
                                </button>
                            </div>
                            <span class="span_border"></span>
                        </li>
                    <?php } ?>
                </ul>
            <?php }?>
    </div>
</aside>
