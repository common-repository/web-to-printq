<?php
    /**
     * Created by PhpStorm.
     * User: th
     * Date: 12 Iun 2017
     * Time: 16:20
     */

    $projects = Printq_Helper_Design::getUserSavedProjects( get_current_user_id() );
?>
<h2><?php echo esc_html__( 'Saved Projects', PQD_DOMAIN ); ?></h2>
<?php if ( ! count( $projects ) ) { ?>
    <div class="pqd_notice"><?php echo esc_html__( 'You haven\'t saved any project yet!', PQD_DOMAIN ) ?></div>
<?php } else { ?>
    <table class="shop_table shop_table_responsive my_account_orders">
        <thead>
            <tr>
                <th class="project_title"><span class="nobr"><?php echo esc_html__( 'Title' ); ?></span></th>
                <th class="project_description"><span class="nobr"><?php echo esc_html__( 'Description' ); ?></span></th>
                <th class="project_actions"><span class="nobr"><?php echo esc_html__( 'Actions' ); ?></span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $projects as $project ) { ?>
                <tr>
                    <td><?php echo esc_html__( get_the_title( $project ) ) ?></td>
                    <td><?php echo esc_html__( $project->post_content ) ?></td>
                    <td>
                        <?php

                            $loadUrl = add_query_arg( array(
                                                          'action'     => 'pqd_design',
                                                          'subaction'  => 'index',
                                                          'project_id' => $project->ID,
                                                          'pqd_nonce'  => wp_create_nonce( 'pqd_nonce' ),
                                                          'isAjax'     => 0
                                                      ), get_admin_url( null, 'admin-ajax.php' )
                            );

                            $deleteUrl = add_query_arg( array(
                                                            'action'     => 'pqd_projects',
                                                            'subaction'  => 'delete',
                                                            'project_id' => $project->ID,
                                                            'pqd_nonce'  => wp_create_nonce( 'pqd_nonce' ),
                                                            'isAjax'     => 0
                                                        ), get_admin_url( null, 'admin-ajax.php' )
                            );
                            ?>
                        <a href="<?php echo esc_attr( $loadUrl ) ?>" class="nobr"><?php echo esc_html__( 'Load' ); ?></a>
                        |
                        <a href="<?php echo esc_attr( $deleteUrl ) ?>" class="nobr"><?php echo esc_html__( 'Delete' ); ?></a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php } ?>
