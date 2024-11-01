<?php
    defined( 'ABSPATH' ) or die( 'Are you trying to trick me?' );

    $shapes     = array_slice( glob( PRINTQ_SHAPES_DIR . '*.svg' ), 0, 30 );
    $categories = glob( PRINTQ_SHAPES_DIR . "*", GLOB_ONLYDIR );

    $is_admin = isset( $_REQUEST['is_admin'] );
    if ( ! $is_admin ) {
        //get template meta
        if( $project_id = $this->getData('project_id') ){
            $template_id = get_post_meta( $this->getData('post'), 'pqd_template', true);
        }
        else {
            $template_id = isset( $_REQUEST['pqd_template'] ) ? intval( $_REQUEST['pqd_template'] ) : 0;
        }
        $post_meta   = get_post_meta( $template_id, 'pqd_template_settings', true );
        if ( isset( $post_meta['block_options'] ) ) {
            $block_options = (array) $post_meta['block_options'];
            $move          = isset( $block_options['move'] ) && intval( $block_options['move'] ) == 1 ? 1 : 0;
            $resize        = isset( $block_options['resize'] ) && intval( $block_options['resize'] ) == 1 ? 1 : 0;
            $snap          = isset( $block_options['snap'] ) && intval( $block_options['snap'] ) == 1 ? 1 : 0;
            $rotate        = isset( $block_options['rotate'] ) && intval( $block_options['rotate'] ) == 1 ? 1 : 0;
        } else {
            $move = $resize = $snap = $rotate = 0;
        }
    } else {
        //enable all block options for admin
        $move = $resize = $snap = $rotate = 1;
    }
?>

<aside class="sidebar">
    <ul class="mainMenu">
        <?php if ( pqd_get_config( 'unsplash_id' ) ) { ?>
            <li class="mainItem" data-type="backgrounds">
                <a href="javascript:void(0)" class="mainTrigger">
                    <div class="inactive icon printqicon-background hasTooltip" data-tooltip="add_background">
                        <span class="title"><?php _e( 'Backgrounds', PQD_DOMAIN ) ?></span>
                        <span class="more icon printqicon-lefttriangle"></span>
                    </div>
                    <div class="active icon printqicon-background hasTooltip" data-tooltip="add_background">
                        <span class="title"><?php _e( 'Backgrounds', PQD_DOMAIN ) ?></span>
                        <span class="more icon printqicon-lefttriangle"></span>
                    </div>
                </a>
                <div class="preview_mask"></div>
                <div class="backgroundsGallery">
                    <div class="actions">
                        <input class="searchImage" type="text"/>
                        <div class="action_buttons">
                            <div class="button search_button">
                                <a class='search_unsplash_image' href="javascript:void(0)">
                                    <span><?php _e( 'Search', PQD_DOMAIN ) ?></span>
                                </a>
                            </div>
                            <div class="button reset_button">
                                <a class='search_unsplash_image' href="javascript:void(0)">
                                    <span><?php _e( 'Reset', PQD_DOMAIN ) ?></span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="backgroundsGeneralGallery">
                        <ul class="backgroundsGalleryList">

                        </ul>
                    </div>
                    <div class="backgroundsSearchGallery">
                        <ul class="backgroundsGallerySearchList">

                        </ul>
                    </div>
                </div>
                <div class="backgroundsGalleryMask"></div>
            </li>
        <?php } ?>
        <li class="mainItem" data-type="shapes">
            <a href="javascript:void(0)" class="mainTrigger">
                <div class="inactive icon printqicon-shapes path1 path2 hasTooltip" data-tooltip="add_shape">
                    <span class="title"><?php _e( 'Shapes', PQD_DOMAIN ) ?></span>
                    <span class="more icon printqicon-lefttriangle"></span>
                </div>
                <div class="active icon printqicon-shapes path1 path2 hasTooltip" data-tooltip="add_shape">
                    <span class="title"><?php _e( 'Shapes', PQD_DOMAIN ) ?></span>
                    <span class="more icon printqicon-lefttriangle"></span>
                </div>
            </a>
            <div class="preview_mask"></div>
            <div class="shapesGallery">
                <div class="topShapesActions">
                    <a href="javascript:void(0)" class="backShapesCategory clickActionSidebar" data-action="back-shapes-category">
                        <?php _e( 'Back', PQD_DOMAIN ) ?>
                    </a>
                </div>
                <div class="shapesCategoryContainer">
                    <ul class="shapesCategoryList">
                        <?php
                            foreach ( $categories as $key => $category ):?>
                                <li class="listItem" data-target="<?php echo esc_attr( basename( $category ) ); ?>">
                                    <a href="javascript:void(0)" class="container"
                                       style="<?php echo 'background-image: url(' . esc_url( PRINTQ_SHAPES_URL . basename( $category ) . '.png' ) . ')'; ?>">
                                        <u></u>
                                        <div class="category_title"><?php echo esc_attr( basename( $category ) ); ?></div>
                                    </a>
                                </li>
                            <?php endforeach;
                        ?>
                    </ul>
                </div>
                <div class="shapesGalleryContainer">
                    <ul class="shapesGalleryList"></ul>
                </div>
            </div>
            <div class="shapesGalleryMask"></div>
        </li>
        <li class="mainItem" data-type="curvedText">

            <a href="javascript:void(0)" class="mainTrigger">
                <div class="inactive icon printqicon-newtext-1 hasTooltip clickActionSidebar" data-tooltip='add_curvedtext'
                     data-action="addCurvedText">
                    <span class="title"><?php _e( 'Add Curved Text', PQD_DOMAIN ) ?></span>
                </div>
                <div class="active icon printqicon-newtext-1 hasTooltip clickActionSidebar" data-tooltip='add_curvedtext'
                     data-action="disableCurvedText">
                    <span class="title"><?php _e( 'Add Curved Text', PQD_DOMAIN ) ?></span>
                </div>
            </a>
            <div class="preview_mask"></div>
        </li>
        <li class="mainItem" data-type="textBox">
            <a href="javascript:void(0)" class="mainTrigger">
                <div class="inactive icon printqicon-newtext hasTooltip clickActionSidebar" data-tooltip='add_text' data-action="addTextBox">
                    <span class="title"><?php _e( 'Add TextBox', PQD_DOMAIN ) ?></span>
                </div>
                <div class="active icon printqicon-newtext hasTooltip clickActionSidebar" data-tooltip='add_text' data-action="disableAddTextBox">
                    <span class="title"><?php _e( 'Add TextBox', PQD_DOMAIN ) ?></span>
                </div>
            </a>
            <div class="preview_mask"></div>
        </li>
        <li class="mainItem">
            <a href="javascript:void(0)" class="mainTrigger">
                <div class="inactive icon printqicon-blockoptions hasTooltip" data-tooltip="block_options">
                    <span class="title"><?php _e( 'Blocks', PQD_DOMAIN ) ?></span>
                    <span class="more icon printqicon-lefttriangle"></span>
                </div>
                <div class="active icon printqicon-blockoptions hasTooltip" data-tooltip="block_options">
                    <span class="title"><?php _e( 'Blocks', PQD_DOMAIN ) ?></span>
                    <span class="more icon printqicon-lefttriangle"></span>
                </div>
            </a>
            <div class="submenu">
                <ul>
                    <li class="subItem hasTooltip block_option_move <?php echo $move ? 'active' : '' ?>" data-tooltip='enable_move'>
                        <a href="javascript:void(0)" class="subTrigger icon printqicon-movable clickActionSidebar"
                           data-action="movable_changer">
                            <span class="active icon printqicon-ok"></span>
                        </a>
                    </li>
                    <li class="subItem hasTooltip block_option_resize <?php echo $resize ? 'active' : '' ?>" data-tooltip='enable_resize'>
                        <a href="javascript:void(0)" class="subTrigger icon printqicon-resizable clickActionSidebar"
                           data-action="resize_changer">
                            <span class="active icon printqicon-ok"></span>
                        </a>
                    </li>
                    <li class="subItem hasTooltip block_option_snap <?php echo $snap ? 'active' : '' ?>" data-tooltip='enable_snap'>
                        <a href="javascript:void(0)" class="subTrigger icon printqicon-snap clickActionSidebar" data-action="snap_changer">
                            <span class="active icon printqicon-ok"></span>
                        </a>
                    </li>
                    <li class="subItem hasTooltip block_option_rotate <?php echo $rotate ? 'active' : '' ?>" data-tooltip='enable_rotate'>
                        <a href="javascript:void(0)" class="subTrigger icon printqicon-rotatable clickActionSidebar"
                           data-action="rotate_changer">
                            <span class="active icon printqicon-ok"></span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="preview_mask"></div>
        </li>
        <li class="mainItem">
            <a href="javascript:void(0)" class="mainTrigger">
                <div class="inactive icon printqicon-drawings freeDrawingOptions hasTooltip" data-tooltip="drawing">
                    <span class="title"><?php _e( 'Blocks', PQD_DOMAIN ) ?></span>
                    <span class="more icon printqicon-lefttriangle"></span>
                </div>
                <div class="active icon printqicon-drawings freeDrawingOptions hasTooltip" data-tooltip="drawing">
                    <span class="title"><?php _e( 'Blocks', PQD_DOMAIN ) ?></span>
                    <span class="more icon printqicon-lefttriangle"></span>
                </div>
            </a>
            <div class="submenu">
                <ul>
                    <li class="subItem hasTooltip" data-tooltip='draw_circle'>
                        <a href="javascript:void(0)" class="subTriggerDrawing icon printqicon-drawing_circles path1 path2  clickActionSidebar"
                           data-action="draw_circle">

                        </a>
                    </li>
                    <li class="subItem hasTooltip" data-tooltip='draw_square'>
                        <a href="javascript:void(0)" class="subTriggerDrawing icon printqicon-square path1 path2  clickActionSidebar"
                           data-action="draw_square">

                        </a>
                    </li>
                    <li class="subItem hasTooltip" data-tooltip='draw_free'>
                        <a href="javascript:void(0)" class="subTriggerDrawing icon printqicon-drawings  clickActionSidebar" data-action="draw_free">
                            <span class="active icon printqicon-ok"></span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="preview_mask"></div>
        </li>
        <?php if ( ! $is_admin && is_user_logged_in() ) { ?>
            <li class="mainItem save_load_group">
                <a href="javascript:void(0)" class="mainTrigger">
                    <div class="inactive icon printqicon-cloud-loadproject hasTooltip" data-tooltip="save_load_project">
                        <span class="title"><?php esc_html_e( 'Save/Load', PQD_DOMAIN ) ?></span>
                        <span class="more icon printqicon-lefttriangle"></span>
                    </div>
                    <div class="active icon printqicon-cloud-loadproject hasTooltip" data-tooltip="save_load_project">
                        <span class="title"><?php esc_html_e( 'Save/Load', PQD_DOMAIN ) ?></span>
                        <span class="more printqicon-lefttriangle"></span>
                    </div>
                </a>
                <div class="preview_mask"></div>
                <div class="submenu">
                    <ul>
                        <li class="subItem group saveProject_group hasTooltip" id="saveProject" data-tooltip="save_project">
                            <a href="javascript:void(0)" class="subTrigger icon printqicon-cloud-saveproject">
                            </a>
                        </li>
                        <li class="subItem group loadProject_group hasTooltip" id="loadProject" data-tooltip="load_project">
                            <a href="javascript:void(0)" class="subTrigger icon printqicon-cloud-loadproject">
                            </a>
                        </li>
                    </ul>
                </div>
                <div id="saveProjectFrame">
                        <?php
                            $project_name = '';
                            $project_description = '';
                            if ( $project_id = $this->getData( 'project_id' ) ) {
                                $project = get_post( $project_id);
                                $project_name = get_the_title($project);
                                $project_description = $project->post_content;
                            }
                        ?>
                    <div class="projectData">
                        <input id="projectId" name="project_id" type="hidden" value="<?php echo esc_attr( $project_id) ?>"/>
                    </div>
                    <ul>
                        <li>
                            <label for="projectName"><?php esc_html_e( 'Project Name', PQD_DOMAIN ) ?>:</label>
                            <input id="projectName" class="customer_save_project" name="project_name" type="text" value="<?php echo esc_attr( $project_name ) ?>"/>
                        </li>
                        <li>
                            <label for="projectDescription"><?php esc_html_e( 'Project Description', PQD_DOMAIN ) ?>:</label>
                            <textarea id="projectDescription" name="project_description"><?php echo esc_attr( $project_description ) ?></textarea>
                        </li>
                        <li>
                            <button id="save" class="button btn-cart clickActionSidebar hasTooltip"
                                    data-tooltip="save-project-button"
                                    data-action="save-project"
                                    title="<?php esc_attr_e( 'Save Project', PQD_DOMAIN ) ?>"
                                    type="button">
                                <span>
                                    <span><?php esc_html_e( 'Save', PQD_DOMAIN ) ?></span>
                                </span>
                            </button>
                            <button id="save_as" class="button btn-cart clickActionSidebar hasTooltip"
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

                <div id="loadProjectFrame">
                    <?php
                        $product_id    = intval( $this->getData('post', 0) );
                        $savedProjects = Printq_Helper_Design::getProductSavedProjects( get_current_user_id(), $product_id );
                        if ( ! $savedProjects ) { ?>
                            <div id="noProjects">
                                <?php esc_html_e( 'You haven\'t saved any project for this product', PQD_DOMAIN ); ?>
                            </div>
                            <ul>
                            </ul>
                        <?php } else { ?>
                            <ul>
                                <?php foreach ( $savedProjects as $savedProject ) { ?>
                                    <li id="project_<?php echo $savedProject->ID ?>" class="body">
                                        <div class="projectName">  <span><?php echo esc_html( $savedProject->post_title ) ?></span></div>
                                        <div class="projectDescription"> <span><?php echo esc_html( $savedProject->post_content ) ?></span> </div>
                                        <div>
                                            <input type="hidden" name="projectId" value="<?php echo esc_attr( $savedProject->ID ) ?>"/>
                                            <button id="load" class="button" title="<?php esc_attr_e( 'Load', PQD_DOMAIN ) ?>" type="button">
                                                <span>
                                                    <span><?php esc_html_e( 'Load', PQD_DOMAIN ) ?></span>
                                                </span>
                                            </button>
                                            <button id="delete" class="button btn-cart" title="<?php esc_attr_e( 'Delete', PQD_DOMAIN ) ?>" type="button">
                                                <span>
                                                    <span><?php esc_html_e( 'Delete', PQD_DOMAIN ) ?></span>
                                                </span>
                                            </button>
                                        </div>
                                        <span class="span_border"></span>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>

                </div>
                <div class="layoutsGalleryMask"></div>
            </li>
        <?php } ?>
        <li class="mainItem zoom_in_group">
            <a href="javascript:void(0)" class="zoomInContainer clickActionSidebar" data-action="zoomIn">
                <div class="inactive icon printqicon-zoom_in">
                    <span class="title"><?php esc_html_e( 'Zoom In', PQD_DOMAIN ) ?></span>
                </div>
            </a>
            <div class="preview_mask"></div>
        </li>
        <li class="mainItem zoom_text_group">
            <div class="preview_mask"></div>
            <span class="zoom_percent title">100%</span>
        </li>
        <li class="mainItem zoom_out_group">
            <a href="javascript:void(0)" class="zoomOutContainer clickActionSidebar" data-action="zoomOut">
                <div class="inactive icon printqicon-zoom_out ">
                    <span class="title"><?php _e( 'Zoom Out', PQD_DOMAIN ) ?></span>
                </div>
            </a>
            <div class="preview_mask"></div>
        </li>
    </ul>
</aside>


<!-- shapes -->
<ul id="shapePlaceholderSidebar">
	<li class="listItem empty">
        <a href="javascript:void(0)" class="container">
            <u></u>
            <div class="animation-container">
				<div class='loader-animation'>
					<?php for ( $al = 0; $al < 24; $al ++ ): ?>
                        <div></div>
                    <?php endfor; ?>
				</div>
			</div>
        </a>
	</li>
</ul>
