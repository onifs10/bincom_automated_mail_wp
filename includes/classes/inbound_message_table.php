<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
BMA()->load_files(BMA()->get_vars('PATH').'includes/classes/inbound_message.php');
class BMA_Inbound_Messages_List_Table extends WP_List_Table {
    public function __construct()
    {
            parent::__construct(
                [
                    'singular' => __('Message', 'bma'),
                    'plural' => __('Messages', 'bma'),
                    'ajax' => false
                ]
            );
    }
    public static function get_messages($per_page = 5, $page_number = 1){
        if(array_key_exists('orderby', $_REQUEST)){
            $orderby = esc_sql( $_REQUEST['orderby'] );
        }else{
            $orderby = null;
        }
        if(array_key_exists('order', $_REQUEST)){
            $order = esc_sql( $_REQUEST['order'] );
        }else{
            $order = null;
        }
        $args = array(
			'posts_per_page' => $per_page,
			'offset' => ( $page_number - 1 ) * $per_page,
			'orderby' => $orderby  ?? 'ID',
			'order' => $order  ?? 'ASC',
			'meta_key' => '',
			'meta_value' => '',
			'post_status' => 'any',
			'tax_query' => array(),
			'channel' => '',
			'channel_id' => 0,
			'hash' => '',
      );
        return  BMA_Inbound_Message::find($args);
     }
    public static function record_count() {
        $args = array(
          'post_status' => 'any',
        );
        return BMA_Inbound_Message::count($args);
    }
    public function no_items() {
        _e( 'No inbound message found', 'bma' );
    }

    public function column_subject( $item ) {

        // create a nonce
        $block_nonce = wp_create_nonce( 'bma_block_inbound_message' );
        $pending_nonce = wp_create_nonce( 'bma_pending_inbound_message' );
        $mailed_nonce = wp_create_nonce( 'bma_mailed_inbound_message' );
        $title = '<strong>' . $item->subject . '</strong>';
    
        $actions = [
        'block' => sprintf( '<a href="?page=%s&action=%s&message=%s&_wpnonce=%s">block</a>', esc_attr( $_REQUEST['page'] ), 'block', absint( $item->id() ), $block_nonce ),
        'pending' => sprintf( '<a href="?page=%s&action=%s&message=%s&_wpnonce=%s">pending</a>', esc_attr( $_REQUEST['page'] ), 'pending', absint( $item->id() ), $pending_nonce ),
        'mailed' => sprintf( '<a href="?page=%s&action=%s&message=%s&_wpnonce=%s">mark as mailed</a>', esc_attr( $_REQUEST['page'] ), 'mailed', absint( $item->id() ), $mailed_nonce )
        ];
    
        return $title . $this->row_actions( $actions );
    }
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
          case 'ID':
            return $item->id();
          case 'subject':
          case 'from_email':
          case 'from_name':
            return $item->$column_name;
          case 'input_check':
            return $item->$column_name ?? 'null';
          case 'fields':
            return $this->process_fields($item->fields);
          case 'contacted':
            return $item->details->$column_name ?? 'no' ;
          default:
            return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
      }
    public function column_cb( $item ) {
        return sprintf(
        '<input type="checkbox" name="bulk[]" value="%s" />', $item->id()
        );
    } 
    public function get_columns() {
        $columns = [
        'cb'      => '<input type="checkbox" />',
        'ID'  => __('id','bma'),
        'subject'    => __( 'Subject', 'bma' ),
        'from_email' => __( 'From Email', 'bma' ),
        'from_name'    => __( 'From Name', 'bma' ),
        'fields'    => __( 'Form Input  Fields', 'bma' ),
        'input_check' => __('Input Checked : <strong>'.BMASETTINGS['input_check']."</strong>",'bma'),
        'contacted' => __('Mail Sent','bma'),
      ];
        return $columns;
    }

    protected  function get_sortable_columns() {
        $sortable_columns = [
        'from_email'    => ['from_email', true ],
        // 'class_code' => array( 'class_code', true ),
        // 'class_days'    => array( 'class_days', true ),
        // 'class_starts'    => array( 'class_starts', true ),
        // 'class_time' => array('class_time',true),
        // 'class_link' => array('class_link',true),
        // 'class_duration' => array('class_duration',true)
        ];
    
        return $sortable_columns;
    }
    public function get_bulk_actions() {
        $actions = [
        'bulk-block' => 'Block',
        'bulk-mailed' => 'mailed',
        'bulk-pending' => 'pending'
        ];
    
        return $actions;
    }
    public function prepare_items() {


      $this->_column_headers = [$this->get_columns(),['ID'],$this->get_sortable_columns(),[]];
        // $this->_column_headers = $this->get_column_info();
    
        /** Process bulk action */
        $this->process_bulk_action();
    
        $per_page     = $this->get_items_per_page( 'classes_per_page', 5 );
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();
    
        $this->set_pagination_args( [
        'total_items' => $total_items, //WE have to calculate the total number of items
        'per_page'    => $per_page //WE have to determine how many items to show on a page
        ] );
    
    
        $this->items = self::get_messages( $per_page, $current_page );
    }



    public function process_fields($fields){
        ?>
<ul>
    <?php
        foreach($fields as $key => $field){
            ?>
    <li><?php echo "<strong>{$key}</strong> : {$field}" ?></li>
    <?php
        }
        ?>
</ul>
<?php
    }

    public function process_bulk_action() {

        //Detect when a bulk action is being triggered...
        if ( 'block' === $this->current_action() ) {
      
          // In our file that handles the request, verify the nonce.
          $nonce = esc_attr( $_REQUEST['_wpnonce'] );
      
          if ( ! wp_verify_nonce( $nonce, 'bma_block_inbound_message' ) ) {
            die( 'Go get a life script kiddies' );
          }
          else {
            self::block_inbound_message( absint( $_GET['message'] ) );
      
            // wp_safe_redirect( esc_url( add_query_arg() ) );
            // exit();
          }
      
        }
      
        // If the block bulk action is triggered
        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-block' )
             || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-block' )
        ) {
      
          $block_ids = esc_sql( $_POST['bulk'] );
      
          // loop over the array of record IDs and block them
          foreach ( $block_ids as $id ) {
            self::block_inbound_message( $id );
          }
      
          // wp_safe_redirect( esc_url( add_query_arg() ) );
          // exit();
        }
        if ( 'mailed' === $this->current_action() ) {
      
          // In our file that handles the request, verify the nonce.
          $nonce = esc_attr( $_REQUEST['_wpnonce'] );
      
          if ( ! wp_verify_nonce( $nonce, 'bma_mailed_inbound_message' ) ) {
            die( 'Go get a life script kiddies' );
          }
          else {
            self::mailed_inbound_message( absint( $_GET['message'] ) );
      
            // wp_safe_redirect( esc_url( add_query_arg() ) );
            // exit();
          }
      
        }
      
        // If the block bulk action is triggered
        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-mailed' )
             || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-mailed' )
        ) {
      
          $block_ids = esc_sql( $_POST['bulk'] );
      
          // loop over the array of record IDs and block them
          foreach ( $block_ids as $id ) {
            self::mailed_inbound_message( $id );
          }
      
          // wp_safe_redirect( esc_url( add_query_arg() ) );
          // exit();
        }
        if ( 'pending' === $this->current_action() ) {
      
          // In our file that handles the request, verify the nonce.
          $nonce = esc_attr( $_REQUEST['_wpnonce'] );
      
          if ( ! wp_verify_nonce( $nonce, 'bma_pending_inbound_message' ) ) {
            die( 'Go get a life script kiddies' );
          }
          else {
            self::pending_inbound_message( absint( $_GET['message'] ) );
      
            // wp_safe_redirect( esc_url( add_query_arg() ) );
            // exit();
          }
      
        }
      
        // If the block bulk action is triggered
        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-pending' )
             || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-pending' )
        ) {
      
          $block_ids = esc_sql( $_POST['bulk'] );
      
          // loop over the array of record IDs and block them
          foreach ( $block_ids as $id ) {
            self::pending_inbound_message( $id );
          }
      
          // wp_safe_redirect( esc_url( add_query_arg() ) );
          // exit();
        }
      }


    public static function block_inbound_message($id)
    {
        BMA_Inbound_Message::block($id);
        // die('block function called');
    }

    public static function mailed_inbound_message($id)
    {
        BMA_Inbound_Message::mailed($id);
        // die('block function called');
    }

    public static function pending_inbound_message($id)
    {
        BMA_Inbound_Message::pending($id);
        // die('block function called');
    }
}